<?php
// DataProcessor2.php
// Selects random subsets of 100 bankrupt and 100 solvent companies (year 2024 with annual reports)
// and writes them to the correspoding files: `main_subset.csv`, `financials_subset.csv`, `reports_subset.csv`, `main_solvent_subset.csv`, `financials_solvent_subset.csv` and `reports_solvent_subset.csv`.

ini_set('display_errors','1');
error_reporting(E_ALL);
set_time_limit(0);
ignore_user_abort(true);
ob_implicit_flush(true);
ini_set('memory_limit','-1');

// Input files
const MAIN_FILE = __DIR__ . '/main.csv';
const FINANCIAL_FILE = __DIR__ . '/financials.csv';
const REPORT_FILE = __DIR__ . '/reports.csv';
const MAIN_SOLVENT_FILE = __DIR__ . '/main_solvent.csv';
const FINANCIAL_SOLVENT_FILE = __DIR__ . '/financials_solvent.csv';
const REPORT_SOLVENT_FILE = __DIR__ . '/reports_solvent.csv';

// Output files
const MAIN_SUBSET = __DIR__ . '/main_subset.csv';
const FINANCIAL_SUBSET = __DIR__ . '/financials_subset.csv';
const REPORT_SUBSET = __DIR__ . '/reports_subset.csv';
const MAIN_SOLVENT_SUBSET = __DIR__ . '/main_solvent_subset.csv';
const FINANCIAL_SOLVENT_SUBSET = __DIR__ . '/financials_solvent_subset.csv';
const REPORT_SOLVENT_SUBSET = __DIR__ . '/reports_solvent_subset.csv';

$runningInCli = (php_sapi_name() === 'cli');
if (!$runningInCli) {
    header('Content-Type: text/html; charset=UTF-8');
    echo "<!doctype html><meta charset='utf-8'><style>body{background:#000;color:#0f0;font:14px/1.4 monospace;padding:16px}</style><pre>";
}

logmsg('Starting subset selection…');

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

function build_maps(array $rows, string $key1, ?string $key2 = null): array {
    $map = [];
    foreach ($rows as $r) {
        if ($key2 === null) {
            $map[$r[$key1]] = $r;
        } else {
            $map[$r[$key1]][$r[$key2]] = $r;
        }
    }
    return $map;
}

function pick_ciks(array $reports, int $count): array {
    $eligible = [];
    foreach ($reports as $r) {
        if (($r['year'] ?? '') === '2024' && trim($r['AnnualReportLink'] ?? '') !== '') {
            $eligible[] = $r['CIK'];
        }
    }
    $eligible = array_values(array_unique($eligible));
    shuffle($eligible);
    return array_slice($eligible, 0, min($count, count($eligible)));
}

function subset(array $mainMap, array $finMap, array $repMap, array $ciks, int $limit): array {
    $mainRows = [];
    $finRows = [];
    $repRows = [];
    foreach ($ciks as $cik) {
        if (!isset($mainMap[$cik]) || !isset($finMap[$cik]['2024']) || !isset($repMap[$cik]['2024'])) continue;
        $mainRows[] = $mainMap[$cik];
        $finRows[] = $finMap[$cik]['2024'];
        $repRows[] = $repMap[$cik]['2024'];
        if (count($mainRows) >= $limit) break;
    }
    return [$mainRows, $finRows, $repRows];
}
logmsg('Reading CSV files…');
[$hdrMain, $rowsMain] = read_csv(MAIN_FILE);
[$hdrFin, $rowsFin] = read_csv(FINANCIAL_FILE);
[$hdrRep, $rowsRep] = read_csv(REPORT_FILE);
[$hdrMainSolv, $rowsMainSolv] = read_csv(MAIN_SOLVENT_FILE);
[$hdrFinSolv, $rowsFinSolv] = read_csv(FINANCIAL_SOLVENT_FILE);
[$hdrRepSolv, $rowsRepSolv] = read_csv(REPORT_SOLVENT_FILE);

$mainMap = build_maps($rowsMain, 'CIK');
$finMap = build_maps($rowsFin, 'CIK', 'year');
$repMap = build_maps($rowsRep, 'CIK', 'year');
$mainSolvMap = build_maps($rowsMainSolv, 'CIK');
$finSolvMap = build_maps($rowsFinSolv, 'CIK', 'year');
$repSolvMap = build_maps($rowsRepSolv, 'CIK', 'year');

logmsg('Selecting bankrupt companies…');
$ciksBankrupt = pick_ciks($rowsRep, 200);
[$mainSub, $finSub, $repSub] = subset($mainMap, $finMap, $repMap, $ciksBankrupt, 100);
logmsg('Selected ' . count($mainSub) . ' bankrupt company row(s).');

logmsg('Selecting solvent companies…');
$ciksSolvent = pick_ciks($rowsRepSolv, 200);
[$mainSolvSub, $finSolvSub, $repSolvSub] = subset($mainSolvMap, $finSolvMap, $repSolvMap, $ciksSolvent, 100);
logmsg('Selected ' . count($mainSolvSub) . ' solvent company row(s).');

logmsg('Writing subset CSV files…');
write_csv(MAIN_SUBSET, $hdrMain, $mainSub);
write_csv(FINANCIAL_SUBSET, $hdrFin, $finSub);
write_csv(REPORT_SUBSET, $hdrRep, $repSub);
write_csv(MAIN_SOLVENT_SUBSET, $hdrMainSolv, $mainSolvSub);
write_csv(FINANCIAL_SOLVENT_SUBSET, $hdrFinSolv, $finSolvSub);
write_csv(REPORT_SOLVENT_SUBSET, $hdrRepSolv, $repSolvSub);

logmsg('Done.');
?>