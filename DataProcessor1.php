<?php
// DataProcessor1.php
// Calls OpenAI API to estimate bankruptcy likelihood for financial rows.

ini_set('display_errors', '1');
error_reporting(E_ALL);
set_time_limit(0);
ignore_user_abort(true);
ob_implicit_flush(true);

require_once __DIR__ . '/config.php';

define('FINANCIAL_CSV_FILE', __DIR__ . '/financials.csv');
define('FINANCIAL_CSV_FILE_SOLVENT', __DIR__ . '/financials_solvent.csv');
define('BATCH_SIZE', 50);

$TESTING_MODE = false; // Set to true to only run for one batch to test or set to false to process full dataset

$runningInCli = (php_sapi_name() === 'cli');
if (!$runningInCli) {
    header('Content-Type: text/html; charset=UTF-8');
    echo "<!doctype html><meta charset='utf-8'><style>body{background:#000;color:#0f0;font:14px/1.4 monospace;padding:16px}</style><pre>";
}

logmsg('Starting bankruptcy likelihood processing…');

function logmsg(string $msg): void {
    $ts = date('H:i:s');
    echo "[$ts] $msg\n";
    flush();
}

function read_csv(string $file): array {
    $rows = [];
    if (!($fh = fopen($file, 'r'))) return [[], []];
    $header = fgetcsv($fh, 0, ',', '"', '\\');
    while (($data = fgetcsv($fh, 0, ',', '"', '\\')) !== false) {
        $row = [];
        foreach ($header as $i => $col) {
            $row[$col] = $data[$i] ?? '';
        }
        $rows[] = $row;
    }
    fclose($fh);
    return [$header, $rows];
}

function write_csv(string $file, array $header, array $rows): void {
    $fh = fopen($file, 'w');
    fputcsv($fh, $header);
    foreach ($rows as $row) {
        $line = [];
        foreach ($header as $col) {
            $line[] = $row[$col] ?? '';
        }
        fputcsv($fh, $line);
    }
    fclose($fh);
}

function call_openai_batch(string $csv, string $apiKey, string $label, int $expectedCount): ?array {
    $rowCount = max(0, substr_count($csv, "\n") - 1);
    logmsg("  Calling AI for $label with $rowCount row(s)…");
    $systemPrompt = <<<EOD
# SETUP
You are a financial analyst.

# TASK
Given CSV financial data for multiple companies, rate the expected likelihood of bankruptcy for each row for the correspodingly next year **on a scale from 0 (very unlikely) to 100 (very likely)**.

# NOTES
There are exactly $expectedCount rows in the dataset. Respond with **only** a comma-separated list of $expectedCount bankruptcy-likelihood numbers, one per row, in the **exact** same order as the input rows.
Don't add anything else and also don't repeat/rewrite the whole row with all its already existing numbers, just return the new number for each row.

# EXAMPLE
Example of your output:
34, 28, 62, 98, 5, 49, 30, 3, 48, 61, 96, ..., 85, 77, 82, 32, 57
EOD;
    $payload = [
        'model' => 'gpt-5-mini',
        'input' => [
            [
                'role' => 'system',
                'content' => [ ['type' => 'input_text', 'text' => $systemPrompt] ]
            ],
            [
                'role' => 'user',
                'content' => [ ['type' => 'input_text', 'text' => $csv] ]
            ]
        ],
        'reasoning' => ['effort' => 'minimal'],
        'text' => ['verbosity' => 'low']
    ];
    $ch = curl_init('https://api.openai.com/v1/responses');
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $apiKey,
    ]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    $resp = curl_exec($ch);
    $status = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
    if ($resp === false) {
        logmsg('  cURL error: ' . curl_error($ch));
        curl_close($ch);
        logmsg("  AI HTTP status: $status");
        return null;
    }
    curl_close($ch);

    // Log the raw response for debugging purposes
    logmsg('  Raw AI response: ' . $resp);

    if ($status < 200 || $status >= 300) {
        logmsg('  HTTP error ' . $status . ': ' . $resp);
        return null;
    }
    $data = json_decode($resp, true);
    if ($data === null) {
        logmsg('  JSON decode error: ' . json_last_error_msg());
        logmsg('  Raw response: ' . $resp);
        return null;
    }
    if (isset($data['error'])) {
        $err = $data['error'];
        $msg = is_array($err) ? ($err['message'] ?? json_encode($err)) : $err;
        logmsg('  API error: ' . $msg);
        return null;
    }
    $content = '';
    if (isset($data['output_text'])) {
        $content = $data['output_text'];
    } elseif (isset($data['output'])) {
        foreach ($data['output'] as $chunk) {
            if (($chunk['type'] ?? '') === 'message' && isset($chunk['content'])) {
                foreach ($chunk['content'] as $c) {
                    if (isset($c['text'])) {
                        $content = $c['text'];
                        break 2;
                    }
                }
            }
        }
    } elseif (isset($data['choices'][0]['message']['content'])) {
        $content = $data['choices'][0]['message']['content'];
    } elseif (isset($data['choices'][0]['text'])) {
        $content = $data['choices'][0]['text'];
    }
    $content = trim($content);
    if ($content === '') {
        logmsg('  Empty response content: ' . $resp);
        return null;
    }
    $parts = preg_split('/[\s,]+/', trim($content));
    $nums = [];
    foreach ($parts as $p) {
        if ($p === '') continue;
        $v = floatval($p);
        $nums[] = is_nan($v) ? null : $v;
    }
    if (count($nums) !== $expectedCount) {
        logmsg('  Parsed ' . count($nums) . " score(s) for $label (expected $expectedCount).");
        if (count($nums) > $expectedCount) {
            $nums = array_slice($nums, 0, $expectedCount);
        } else {
            $nums = array_pad($nums, $expectedCount, null);
        }
    } else {
        logmsg('  Parsed ' . count($nums) . " score(s) for $label.");
    }
    return $nums;
}

function load_companies(array &$rows, int $source): array {
    $byCik = [];
    foreach ($rows as $idx => &$r) {
        $r['__source'] = $source;
        $r['__index'] = $idx;
        $byCik[$r['CIK']][] =& $r;
    }
    $companies = [];
    foreach ($byCik as $cik => $grp) {
        usort($grp, fn($a,$b) => intval($a['year']) <=> intval($b['year']));
        $companies[] = ['cik'=>$cik, 'rows'=>$grp, 'source'=>$source];
    }
    return $companies;
}

function collect_unprocessed(array &$rows): array {
    $out = [];
    foreach ($rows as &$r) {
        // Old behaviour: only add new stuff
        // $base = $r['AIExpectedLikelihoodOfBankruptcyBase'] ?? '';
        // $ext = $r['AIExpectedLikelihoodOfBankruptcyExtended'] ?? '';
        // if ($base === '' || $ext === '') {
        //     $out[] =& $r;
        // }
        // New behaviour: just overwrite
        $out[] =& $r;
    }
    return $out;
}

function build_layers(array $companies): array {
    $layers = [];
    shuffle($companies);
    foreach ($companies as $comp) {
        foreach ($comp['rows'] as $i => $row) {
            $layers[$i][] = $row;
        }
    }
    ksort($layers);
    foreach ($layers as &$layer) {
        shuffle($layer);
    }
    return $layers;
}

function pick_rows_for_batch(array &$layers, int &$layerIdx, int $maxCount, array &$usedCik): array {
    $selected = [];
    $layerCount = count($layers);
    while (count($selected) < $maxCount && $layerIdx < $layerCount) {
        if (empty($layers[$layerIdx])) {
            $layerIdx++;
            continue;
        }
        $foundKey = null;
        foreach ($layers[$layerIdx] as $k => $row) {
            if (!isset($usedCik[$row['CIK']])) {
                $foundKey = $k;
                break;
            }
        }
        if ($foundKey === null) {
            $layerIdx++;
            continue;
        }
        $row = $layers[$layerIdx][$foundKey];
        unset($layers[$layerIdx][$foundKey]);
        $selected[] = $row;
        $usedCik[$row['CIK']] = true;
    }
    while ($layerIdx < $layerCount && (empty($layers[$layerIdx]) || !isset($layers[$layerIdx]))) {
        $layerIdx++;
    }
    return $selected;
}

$ratioCols = [
    'TL_TA','Debt_Assets','EBIT_InterestExpense','EBITDA_InterestExpense','CFO_Liabilities',
    'CFO_DebtService','CurrentRatio','QuickRatio','WC_TA','ROA','OperatingMargin',
    'DaysAR','DaysINV','DaysAP','CashConversionCycle','Accruals','DividendOmission',
    'DebtIssuanceSpike','DebtRepaymentSpike','AltmanZPrime','AltmanZDoublePrime',
    'OhlsonOScore','OhlsonOScoreProb','ZmijewskiXScore','SpringateSScore','TafflerZScore',
    'FulmerHScore','GroverGScore','BeneishMScore','PiotroskiFScore'
];

logmsg('Reading CSV files …');
[$header1, $rows1] = read_csv(FINANCIAL_CSV_FILE);
[$header2, $rows2] = read_csv(FINANCIAL_CSV_FILE_SOLVENT);

$allHeader = array_unique(array_merge($header1, $header2));
$baseHeader = [];
$extHeader = [];
foreach ($allHeader as $col) {
    if ($col === 'idpk') continue;
    if ($col === 'CIK') {
        $baseHeader[] = 'CompanyID';
        $extHeader[] = 'CompanyID';
        continue;
    }
    if (!in_array($col, $ratioCols, true)) {
        $baseHeader[] = $col;
    }
    $extHeader[] = $col;
}

function build_csv(array &$rowRefs, array $header): string {
    $fh = fopen('php://temp', 'r+');
    fputcsv($fh, $header);
    foreach ($rowRefs as &$row) {
        $line = [];
        foreach ($header as $col) {
            if ($col === 'CompanyID') {
                $line[] = $row['CIK'];
            } else {
                $line[] = $row[$col] ?? '';
            }
        }
        fputcsv($fh, $line);
    }
    rewind($fh);
    return stream_get_contents($fh);
}

$unproc1 = collect_unprocessed($rows1);
$unproc2 = collect_unprocessed($rows2);
$companies1 = load_companies($unproc1, 1);
$companies2 = load_companies($unproc2, 2);
$layers1 = build_layers($companies1);
$layers2 = build_layers($companies2);
$idx1 = 0;
$idx2 = 0;
$batch = 1;

while (true) {
    $usedCik = [];
    $sel1 = pick_rows_for_batch($layers1, $idx1, BATCH_SIZE / 2, $usedCik);
    $sel2 = pick_rows_for_batch($layers2, $idx2, BATCH_SIZE / 2, $usedCik);
    $selectedRowRefs = array_merge($sel1, $sel2);

    if (empty($selectedRowRefs)) {
        logmsg('No unprocessed rows remain.');
        break;
    }

    $total = count($selectedRowRefs);
    if ($total < BATCH_SIZE) {
        $needed = BATCH_SIZE - $total;
        if (count($sel1) < BATCH_SIZE / 2) {
            $extra = pick_rows_for_batch($layers1, $idx1, min($needed, BATCH_SIZE / 2 - count($sel1)), $usedCik);
            $sel1 = array_merge($sel1, $extra);
            $selectedRowRefs = array_merge($sel1, $sel2);
            $total = count($selectedRowRefs);
            $needed = BATCH_SIZE - $total;
        }
        if ($needed > 0 && count($sel2) < BATCH_SIZE / 2) {
            $extra = pick_rows_for_batch($layers2, $idx2, $needed, $usedCik);
            $sel2 = array_merge($sel2, $extra);
            $selectedRowRefs = array_merge($sel1, $sel2);
        }
    }

    shuffle($selectedRowRefs);
    $rowOrder = [];
    foreach ($selectedRowRefs as $r) {
        $rowOrder[] = ['source' => $r['__source'], 'index' => $r['__index']];
    }
    logmsg('Selected ' . count($selectedRowRefs) . ' row(s) for processing (batch ' . $batch . ').');

    $baseCsv = build_csv($selectedRowRefs, $baseHeader);
    $extCsv = build_csv($selectedRowRefs, $extHeader);

    $expected = count($selectedRowRefs);
    $baseScores = call_openai_batch($baseCsv, $apiKey, 'base features', $expected) ?? [];
    $extScores = call_openai_batch($extCsv, $apiKey, 'extended features', $expected) ?? [];

    if (count($baseScores) !== $expected) {
        logmsg('  Warning: expected ' . $expected . ' base score(s) but got ' . count($baseScores));
    }
    if (count($extScores) !== $expected) {
        logmsg('  Warning: expected ' . $expected . ' extended score(s) but got ' . count($extScores));
    }

    foreach ($rowOrder as $i => $pos) {
        if ($pos['source'] === 1) {
            $row =& $rows1[$pos['index']];
        } else {
            $row =& $rows2[$pos['index']];
        }
        $row['AIExpectedLikelihoodOfBankruptcyBase'] = $baseScores[$i] ?? '';
        $row['AIExpectedLikelihoodOfBankruptcyExtended'] = $extScores[$i] ?? '';
    }

    foreach ([&$header1, &$header2] as &$hdr) {
        if (!in_array('AIExpectedLikelihoodOfBankruptcyBase', $hdr, true)) $hdr[] = 'AIExpectedLikelihoodOfBankruptcyBase';
        if (!in_array('AIExpectedLikelihoodOfBankruptcyExtended', $hdr, true)) $hdr[] = 'AIExpectedLikelihoodOfBankruptcyExtended';
    }

    logmsg('Writing CSV files …');
    write_csv(FINANCIAL_CSV_FILE, $header1, $rows1);
    write_csv(FINANCIAL_CSV_FILE_SOLVENT, $header2, $rows2);
    logmsg('Batch ' . $batch . ' completed.');

    if ($TESTING_MODE) break;
    $batch++;
}

logmsg('Done.');