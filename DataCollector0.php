<?php
//
// DataCollector0.php
//
// Collects 8-K bankruptcy-related filings from SEC EDGAR Full-Text Search for YEAR,
// picks RANDOM_COMPANY_SAMPLE_SIZE random unique companies (by CIK), and appends rows
// to the correspoding CSV files. Streams progress in CLI or browser.
//
// Run over console:
//   php -d display_errors=1 -d error_reporting=E_ALL DataCollector0.php
// or over browser.

ini_set('display_errors','1');
error_reporting(E_ALL);
set_time_limit(0);
ignore_user_abort(true);
ob_implicit_flush(true);









// //////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// //////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// Configuration
// //////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

const USER_AGENT                  = 'CFDataBot/1.0 ([email protected])'; // include contact per SEC guidance
const SEARCH_ENDPOINT             = 'https://efts.sec.gov/LATEST/search-index';
const SUBMISSION_ENDPOINT         = 'https://data.sec.gov/submissions/';
const YEAR                        = 2024;
const PAGE_SIZE                   = 100;     // EF-TS page size
const POLITE_USLEEP               = 200000;  // 0.2s pause between filings
const POLITE_USLEEP_PAGE          = 300000;  // 0.3s pause between pages
const MAX_RETRIES                 = 5;
const BACKOFF_BASE_MS             = 500;
const RANDOM_COMPANY_SAMPLE_SIZE          = 1000;      // change for a different sample size, recommendation for testing code: 10, for final research sample: 1,000 (note: "Found 2570 unique companies (CIKs) with 8-K 'bankruptcy' hits in 2024.")
const RANDOM_COMPANY_SAMPLE_SIZE_SOLVENT  = 1000;      // change for a different sample size, recommendation for testing code: 10, for final research sample: 1,000
const RANDOM_SEED                          = null;    // set integer for reproducible sampling
const START_YEAR                           = YEAR - 10; // financial history range start
const MAIN_CSV_FILE                        = __DIR__ . '/main.csv';
const MAIN_CSV_FILE_SOLVENT                = __DIR__ . '/main_solvent.csv';
const FINANCIAL_CSV_FILE                   = __DIR__ . '/financials.csv';
const FINANCIAL_CSV_FILE_SOLVENT           = __DIR__ . '/financials_solvent.csv';
const REPORTS_CSV_FILE                     = __DIR__ . '/reports.csv';
const REPORTS_CSV_FILE_SOLVENT             = __DIR__ . '/reports_solvent.csv';
// const ANNUAL_REPORT_DIR           = __DIR__ . '/AnnualReports';
// const QUARTERLY_REPORT_DIR        = __DIR__ . '/QuarterlyReports';
const FINANCIAL_FIELD_MAP         = [
    'assets' => 'Assets',
    'CurrentAssets' => 'AssetsCurrent',
    'NoncurrentAssets' => 'AssetsNoncurrent',
    'liabilities' => 'Liabilities',
    'CurrentLiabilities' => 'LiabilitiesCurrent',
    'NoncurrentLiabilities' => 'LiabilitiesNoncurrent',
    'LiabilitiesAndStockholdersEquity' => 'LiabilitiesAndStockholdersEquity',
    'equity' => 'StockholdersEquity',
    'CommonStockValue' => 'CommonStockValue',
    'RetainedEarningsAccumulatedDeficit' => 'RetainedEarningsAccumulatedDeficit',
    'AccumulatedOtherComprehensiveIncomeLoss' => 'AccumulatedOtherComprehensiveIncomeLoss',
    'MinorityInterest' => 'MinorityInterest',
    'revenues' => 'Revenues',
    'SalesRevenueNet' => 'SalesRevenueNet',
    'CostOfGoodsSold' => 'CostOfGoodsSold',
    'GrossProfit' => 'GrossProfit',
    'OperatingExpenses' => 'OperatingExpenses',
    'SellingGeneralAndAdministrativeExpense' => 'SellingGeneralAndAdministrativeExpense',
    'ResearchAndDevelopmentExpense' => 'ResearchAndDevelopmentExpense',
    'OperatingIncomeLoss' => 'OperatingIncomeLoss',
    'InterestExpense' => 'InterestExpense',
    'IncomeBeforeIncomeTaxes' => 'IncomeBeforeIncomeTaxes',
    'IncomeTaxExpenseBenefit' => 'IncomeTaxExpenseBenefit',
    'NetIncomeLoss' => 'NetIncomeLoss',
    'PreferredStockDividendsAndOtherAdjustments' => 'PreferredStockDividendsAndOtherAdjustments',
    'NetIncomeLossAvailableToCommonStockholdersBasic' => 'NetIncomeLossAvailableToCommonStockholdersBasic',
    'EarningsPerShareBasic' => 'EarningsPerShareBasic',
    'EarningsPerShareDiluted' => 'EarningsPerShareDiluted',
    'WeightedAverageNumberOfSharesOutstandingBasic' => 'WeightedAverageNumberOfSharesOutstandingBasic',
    'WeightedAverageNumberOfDilutedSharesOutstanding' => 'WeightedAverageNumberOfDilutedSharesOutstanding',
    'NetCashProvidedByUsedInOperatingActivities' => 'NetCashProvidedByUsedInOperatingActivities',
    'NetCashProvidedByUsedInInvestingActivities' => 'NetCashProvidedByUsedInInvestingActivities',
    'NetCashProvidedByUsedInFinancingActivities' => 'NetCashProvidedByUsedInFinancingActivities',
    'CashAndCashEquivalentsPeriodIncreaseDecrease' => 'CashAndCashEquivalentsPeriodIncreaseDecrease',
    'CashAndCashEquivalentsAtCarryingValue' => 'CashAndCashEquivalentsAtCarryingValue',
    'PaymentsToAcquirePropertyPlantAndEquipment' => 'PaymentsToAcquirePropertyPlantAndEquipment',
    'ProceedsFromIssuanceOfCommonStock' => 'ProceedsFromIssuanceOfCommonStock',
    'PaymentsOfDividends' => 'PaymentsOfDividends',
    'RepaymentsOfDebt' => 'RepaymentsOfDebt',
    'ProceedsFromIssuanceOfDebt' => 'ProceedsFromIssuanceOfDebt',
    'DepreciationAndAmortization' => 'DepreciationAndAmortization',
    'InventoryNet' => 'InventoryNet',
    'AccountsReceivableNetCurrent' => 'AccountsReceivableNetCurrent',
    'AccountsPayableCurrent' => 'AccountsPayableCurrent',
    'Goodwill' => 'Goodwill',
    'IntangibleAssetsNetExcludingGoodwill' => 'IntangibleAssetsNetExcludingGoodwill',
    'PropertyPlantAndEquipmentNet' => 'PropertyPlantAndEquipmentNet',
    'LongTermDebtNoncurrent' => 'LongTermDebtNoncurrent',
    'ShortTermBorrowings' => 'ShortTermBorrowings',
    'IncomeTaxesPayableCurrent' => 'IncomeTaxesPayableCurrent',
    'EntityRegistrantName' => 'EntityRegistrantName',
    'EntityCentralIndexKey' => 'EntityCentralIndexKey',
    'TradingSymbol' => 'TradingSymbol',
    'EntityIncorporationStateCountryCode' => 'EntityIncorporationStateCountryCode',
    'EntityFilerCategory' => 'EntityFilerCategory',
    'DocumentPeriodEndDate' => 'DocumentPeriodEndDate',
    'DocumentFiscalPeriodFocus' => 'DocumentFiscalPeriodFocus',
    'DocumentFiscalYearFocus' => 'DocumentFiscalYearFocus',
    'DocumentType' => 'DocumentType',
    'AmendmentFlag' => 'AmendmentFlag',
    'CurrentFiscalYearEndDate' => 'CurrentFiscalYearEndDate',
];






















// /////////////////////////////////////////////////////////////////////////////////////////////////////////////////// Output header (HTML with <pre>)
$runningInCli = (php_sapi_name() === 'cli');
if (!$runningInCli) {
    header('Content-Type: text/html; charset=UTF-8');
    echo "<!doctype html><meta charset='utf-8'><style>body{background:#000;color:#0f0;font:14px/1.4 monospace;padding:16px}</style><pre>";
}
logmsg("Starting EDGAR collection for " . YEAR . " (random " . RANDOM_COMPANY_SAMPLE_SIZE . " companies)…");

// /////////////////////////////////////////////////////////////////////////////////////////////////////////////////// Helper functions

// Safe getter: tries multiple keys (snake/camel), works for both _source and fields[*].
// If the value is an array-of-one, returns the first element.
function g(array $arr, string ...$keys) {
    foreach ($keys as $k) {
        if (array_key_exists($k, $arr)) {
            $v = $arr[$k];
            if (is_array($v)) {
                // return first scalar if it's a list-like array
                if (array_key_exists(0, $v) && !is_array($v[0])) {
                    return $v[0];
                }
            }
            return $v;
        }
    }
    return '';
}

function logmsg(string $msg): void {
    $ts = date('H:i:s');
    echo "[$ts] $msg\n";
    flush();
}

function http_get_json(string $url, ?int &$status = null): array|null {
    [$body, $status] = http_get($url, 'application/json');
    if ($status !== 200 || $body === null) return null;
    $data = json_decode($body, true);
    return is_array($data) ? $data : null;
}

function http_get_text(string $url, ?int &$status = null): string {
    [$body, $status] = http_get($url, '*/*'); // accept anything
    return $body ?? '';
}

function http_get(string $url, string $accept, int $retries = MAX_RETRIES): array {
    $attempt = 0;
    $status  = 0;
    while (true) {
        $attempt++;
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'User-Agent: ' . USER_AGENT,
                'Accept: ' . $accept,
                'Accept-Encoding: gzip, deflate'
            ],
            CURLOPT_ENCODING => '',
            CURLOPT_CONNECTTIMEOUT => 20,
            CURLOPT_TIMEOUT => 60,
        ]);
        $response = curl_exec($ch);
        $status   = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err      = curl_error($ch);
        curl_close($ch);

        if ($status === 200 && $response !== false) {
            return [$response, $status];
        }

        if ($attempt <= $retries && in_array($status, [0, 408, 429, 500, 502, 503, 504], true)) {
            $sleepMs = BACKOFF_BASE_MS * (1 << ($attempt - 1));
            logmsg("HTTP $status on GET: $url  (attempt $attempt/$retries). Backing off ~{$sleepMs}ms … $err");
            usleep($sleepMs * 1000);
            continue;
        }

        logmsg("Failed GET (HTTP $status): $url  $err");
        return [null, $status];
    }
}

// Extract bankruptcy details from filing text (very lightweight heuristics).
function parseBankruptcyDetails(string $text): array {
    $details = [
        'BankruptcyDate'     => '',
        'BankruptcyChapter'  => '',
        'CourtName'          => '',
        'CaseNumber'         => ''
    ];

    if (preg_match('/Chapter\s+(7|11|13|15)/i', $text, $m)) {
        $details['BankruptcyChapter'] = 'Chapter ' . strtoupper($m[1]);
    }
    if (preg_match('/United States Bankruptcy Court[^\n\.]*[^\n]*/i', $text, $m)) {
        $details['CourtName'] = trim($m[0]);
    }
    if (preg_match('/Case\s+No\.?\s*([A-Za-z0-9\-]+)/i', $text, $m)) {
        $details['CaseNumber'] = $m[1];
    }
    if (preg_match('/\b(January|February|March|April|May|June|July|August|September|October|November|December)\s+\d{1,2},\s+(2023|2024|2025|2026)\b/i', $text, $m)) {
        $details['BankruptcyDate'] = date('Y-m-d', strtotime($m[0]));
    }
    return $details;
}

// Company metadata (exchange, SIC, etc.)
function getCompanyMetadata(string $cik): array {
    if (!$cik) return [];
    $cikPadded = str_pad($cik, 10, '0', STR_PAD_LEFT);
    $url = SUBMISSION_ENDPOINT . 'CIK' . $cikPadded . '.json';
    $status = 0;
    $data = http_get_json($url, $status);
    if (!$data) return [];
    $addr   = $data['addresses']['business'] ?? [];
    $street = trim(($addr['street1'] ?? '') . ' ' . ($addr['street2'] ?? ''));
    $house  = '';
    $streetName = $street;
    if ($street) {
        if (preg_match('/^([^\s]+)\s+(.*)$/', $street, $m)) {
            $house = $m[1];
            $streetName = $m[2];
        }
    }
    return [
        'CompanyName'         => $data['name'] ?? '',
        'exchange'            => $data['exchanges'][0] ?? '',
        'IndustrySICCode'     => $data['sic'] ?? '',
        'FiscalYearEnd'       => $data['fye'] ?? '',
        'BusinessAddressState' => $addr['state'] ?? '',
        'BusinessAddressZIPCode' => $addr['zip'] ?? '',
        'BusinessAddressCity' => $addr['city'] ?? '',
        'BusinessAddressStreet' => $streetName,
        'BusinessAddressHouseNumber' => $house,
        'StateOfIncorporation' => $data['stateOfIncorporation'] ?? '',
        'Website'             => $data['website'] ?? '',
        'InvestorWebsite'     => $data['investorWebsite'] ?? '',
        'SIC'                 => $data['sic'] ?? '',
        'SICDescription'      => $data['sicDescription'] ?? '',
    ];
}

// Append a row to a CSV file (adds header if file is new)
function appendToCsv(string $file, array $row): void {
    $isNew = !file_exists($file);
    $fh = fopen($file, 'a');
    if (!$fh) throw new RuntimeException("Cannot write to $file");
    if ($isNew) {
        fputcsv($fh, array_keys($row));
    }
    fputcsv($fh, array_map(fn($v) => $v ?? '', $row));
    fclose($fh);
}

// Fetch company facts (XBRL data); returns HTTP status via reference.
function getCompanyFacts(string $cik, ?int &$status = null): array {
    if (!$cik) { $status = 0; return []; }
    $cikPadded = str_pad($cik, 10, '0', STR_PAD_LEFT);
    $url = 'https://data.sec.gov/api/xbrl/companyfacts/CIK' . $cikPadded . '.json';
    $status = 0;
    $data = http_get_json($url, $status);
    return $data['facts'] ?? [];
}

// Find value for concept/year within facts
function findFactValue(array $facts, string $concept, int $year) {
    foreach ($facts as $taxonomy => $concepts) {
        if (!isset($concepts[$concept]['units'])) continue;
        foreach ($concepts[$concept]['units'] as $unit => $items) {
            foreach ($items as $item) {
                if ((int)($item['fy'] ?? 0) === $year) {
                    return $item['val'] ?? null;
                }
            }
        }
    }
    return null;
}

// Save financial history for a company and returns false if company facts are missing.
function saveFinancialHistory(string $cik, string $file = FINANCIAL_CSV_FILE): bool {
    static $idpk = [];
    if (!isset($idpk[$file])) $idpk[$file] = 0;
    $status = 0;
    $facts = getCompanyFacts($cik, $status);
    if ($status !== 200) {
        return false;
    }
    if (!$facts) return true;
    for ($y = START_YEAR; $y <= YEAR; $y++) {
        $row = ['idpk' => ++$idpk[$file], 'CIK' => $cik, 'year' => $y];
        foreach (FINANCIAL_FIELD_MAP as $col => $concept) {
            $row[$col] = findFactValue($facts, $concept, $y);
        }
        $hasData = false;
        foreach (FINANCIAL_FIELD_MAP as $col => $_) {
            if ($row[$col] !== null && $row[$col] !== '') { $hasData = true; break; }
        }
        if ($hasData) {
            appendToCsv($file, $row);
        }
    }
    return true;
}

// Fetch recent filings (10-K, 10-Q, etc.) for a company
function getCompanyFilings(string $cik): array {
    if (!$cik) return [];
    $cikPadded = str_pad($cik, 10, '0', STR_PAD_LEFT);
    $url = SUBMISSION_ENDPOINT . 'CIK' . $cikPadded . '.json';
    $status = 0;
    $data = http_get_json($url, $status);
    if (!$data) return [];
    $recent = $data['filings']['recent'] ?? [];
    $filings = [];
    if (!empty($recent['form'])) {
        $count = count($recent['form']);
        for ($i = 0; $i < $count; $i++) {
            $filings[] = [
                'form' => $recent['form'][$i] ?? '',
                'filingDate' => $recent['filingDate'][$i] ?? '',
                'reportDate' => $recent['reportDate'][$i] ?? '',
                'accessionNumber' => $recent['accessionNumber'][$i] ?? '',
                'primaryDocument' => $recent['primaryDocument'][$i] ?? '',
            ];
        }
    }
    return $filings;
}

/*
// Save annual report HTML filings to subfolder
function saveAnnualReports(string $cik, array $filings): void {
    if (!is_dir(ANNUAL_REPORT_DIR)) mkdir(ANNUAL_REPORT_DIR, 0777, true);
    $cikTrim = ltrim($cik, '0');
    $saved = [];
    foreach ($filings as $f) {
        $form = strtoupper($f['form'] ?? '');
        if ($form !== '10-K' && $form !== '10-K/A') continue;
        $date = $f['reportDate'] ?: $f['filingDate'];
        if (!$date) continue;
        $year = (int)substr($date, 0, 4);
        if ($year < START_YEAR || $year > YEAR) continue;
        if (isset($saved[$year])) continue;
        $acc = str_replace('-', '', $f['accessionNumber'] ?? '');
        $doc = $f['primaryDocument'] ?? '';
        if (!$acc || !$doc) continue;
        $url = "https://www.sec.gov/Archives/edgar/data/$cikTrim/$acc/$doc";
        $status = 0;
        $html = http_get_text($url, $status);
        if ($status === 200 && $html) {
            file_put_contents(ANNUAL_REPORT_DIR . "/{$cik}_{$year}.html", $html);
            $saved[$year] = true;
        }
    }
}

// Save quarterly report HTML filings to subfolder
*/

// Save report links (annual and quarterly) to CSV
function saveReportLinks(string $cik, array $filings, string $file = REPORTS_CSV_FILE): void {
    static $idpk = [];
    if (!isset($idpk[$file])) $idpk[$file] = 0;
    $cikTrim = ltrim($cik, '0');
    $byYear = [];
    foreach ($filings as $f) {
        $form = strtoupper($f['form'] ?? '');
        $date = $f['reportDate'] ?: $f['filingDate'];
        if (!$date) continue;
        $year = (int)substr($date, 0, 4);
        if ($year < START_YEAR || $year > YEAR) continue;
        $acc = str_replace('-', '', $f['accessionNumber'] ?? '');
        $doc = $f['primaryDocument'] ?? '';
        if (!$acc || !$doc) continue;
        $url = "https://www.sec.gov/Archives/edgar/data/$cikTrim/$acc/$doc";
        if ($form === '10-K' || $form === '10-K/A') {
            if (!isset($byYear[$year]['AnnualReportLink'])) {
                $byYear[$year]['AnnualReportLink'] = $url;
            }
        } elseif ($form === '10-Q' || $form === '10-Q/A') {
            $month = (int)substr($date, 5, 2);
            $quarter = $month <= 3 ? 'Q1' : ($month <= 6 ? 'Q2' : ($month <= 9 ? 'Q3' : 'Q4'));
            $key = 'QuarterlyReportLink' . $quarter;
            if (!isset($byYear[$year][$key])) {
                $byYear[$year][$key] = $url;
            }
        }
    }
    foreach ($byYear as $year => $links) {
        $row = [
            'idpk' => ++$idpk[$file],
            'CIK' => $cik,
            'year' => $year,
            'AnnualReportLink' => $links['AnnualReportLink'] ?? '',
            'QuarterlyReportLinkQ1' => $links['QuarterlyReportLinkQ1'] ?? '',
            'QuarterlyReportLinkQ2' => $links['QuarterlyReportLinkQ2'] ?? '',
            'QuarterlyReportLinkQ3' => $links['QuarterlyReportLinkQ3'] ?? '',
            'QuarterlyReportLinkQ4' => $links['QuarterlyReportLinkQ4'] ?? '',
        ];
        appendToCsv($file, $row);
    }
}

// Build an EDGAR full-text search URL with provided query params.
function buildSearchUrl(array $params): string {
    $q = http_build_query($params);
    return SEARCH_ENDPOINT . '?' . $q;
}

// Return true if hit looks like an 8-K (using multiple fields).
function isEightK(array $hit): bool {
    $src = $hit['_source'] ?? [];
    $fld = $hit['fields']  ?? [];

    $ft = strtolower((string)(g($src, 'formType', 'form_type') ?: g($fld, 'formType', 'form_type')));
    if ($ft === '8-k') return true;

    $root = $src['root_forms'] ?? ($fld['root_forms'] ?? null);
    if (is_string($root)) {
        return strtoupper($root) === '8-K';
    }
    if (is_array($root)) {
        foreach ($root as $r) {
            if (strtoupper((string)$r) === '8-K') return true;
        }
    }
    return false;
}

// Coerce best-effort accession number from various places.
function extractAccession(array $hit): string {
    $src = $hit['_source'] ?? [];
    $fld = $hit['fields']  ?? [];
    $acc = g($src, 'accNo','accessionNo','accessionNumber','accession_number')
        ?: g($fld, 'accNo','accessionNo','accessionNumber','accession_number');
    if ($acc) return (string)$acc;

    // Try from _id (pattern: <acc>:<filename>)
    $id = $hit['_id'] ?? '';
    if ($id && strpos($id, ':') !== false) {
        return substr($id, 0, strpos($id, ':'));
    }
    return '';
}

// Fetch all "bankruptcy" hits for the full YEAR, paging through EF-TS.
// Returns a flat array of raw "hits" items (we will filter to 8-K later).
function fetchAllYearHits(string $year): array {
    $startdt = $year . '-01-01';
    $enddt   = $year . '-12-31';
    $from    = 0;
    $all     = [];

    while (true) {
        $params = [
            'q'        => 'bankruptcy',
            // EF-TS filters are inconsistent across indices. Ask for both keys; filter locally too.
            'formType' => '8-K',
            'forms'    => '8-K',
            'startdt'  => $startdt,
            'enddt'    => $enddt,
            'from'     => $from,
            'size'     => PAGE_SIZE,
            'sort'     => 'filedAt',
            'order'    => 'asc',
            'fields'   => implode(',', [
                // ids/keys
                'accNo','accessionNo','accessionNumber','accession_number',
                // dates
                'filedAt','file_date','fileDate',
                // entity info
                'cik','ciks','entityName','companyName','company_names','displayNames','display_names',
                // forms
                'formType','form_type','root_forms',
                // links
                'linkToFiling','link_to_filing','linkToHtml','link_to_html','linkToTxt','link_to_txt'
            ])
        ];

        $url    = buildSearchUrl($params);
        $status = 0;
        $json   = http_get_json($url, $status);
        if (!$json) {
            logmsg("  ERROR: search failed HTTP $status (year scan). Stopping.");
            break;
        }

        $hits = $json['hits']['hits'] ?? [];
        $totalVal = $json['hits']['total']['value'] ?? 0;
        logmsg("  Year-scan page from=$from size=" . PAGE_SIZE . " (total ~$totalVal)");
        if (empty($hits)) break;

        foreach ($hits as $h) $all[] = $h;

        $from += PAGE_SIZE;
        if ($from >= $totalVal) break;
        usleep(POLITE_USLEEP_PAGE);
    }

    return $all;
}

// Fetch list of all CIKs from SEC ticker file for solvent sampling
function fetchAllCiks(): array {
    $url = 'https://www.sec.gov/include/ticker.txt';
    $status = 0;
    $text = http_get_text($url, $status);
    if (!$text) return [];
    $ciks = [];
    foreach (explode("\n", trim($text)) as $line) {
        $line = trim($line);
        if (!$line) continue;
        if (strpos($line, '|') !== false) {
            [, $cik] = explode('|', $line, 2);
        } else {
            $parts = preg_split('/\s+/', $line);
            $cik = $parts[1] ?? null;
        }
        if ($cik !== null && $cik !== '') {
            $ciks[] = ltrim($cik, '0');
        }
    }
    return $ciks;
}

// Process a single EF-TS hit: extract fields, fetch text, parse, write CSV rows.
// Returns true on success, false if company data should be skipped.
function processSingleHit(array $hit): bool {
    $src = $hit['_source'] ?? [];
    $fld = $hit['fields']  ?? [];

    $acc   = extractAccession($hit);
    $filed = g($src, 'filedAt','file_date','fileDate','filed') ?: g($fld, 'filedAt','file_date','fileDate','filed');

    // CIK: prefer singular key, otherwise first from ciks
    $cik   = g($src, 'cik') ?: g($fld, 'cik');
    if (!$cik) {
        $ciks = $src['ciks'] ?? ($fld['ciks'] ?? []);
        if (is_array($ciks) && isset($ciks[0]) && !is_array($ciks[0])) {
            $cik = (string)$ciks[0];
        }
    }
    $cik = (string)$cik;

    // Company name (snake/camel)
    $name  = g($src, 'displayNames','display_names','entityName','companyName','company_names')
          ?: g($fld, 'displayNames','display_names','entityName','companyName','company_names');
    if (is_array($name)) { $name = $name[0] ?? ''; }

    // Form type (or root form)
    $formType = g($src, 'formType','form_type') ?: g($fld, 'formType','form_type');
    if (!$formType) {
        $root = $src['root_forms'] ?? ($fld['root_forms'] ?? null);
        if (is_array($root) && isset($root[0])) $formType = (string)$root[0];
        elseif (is_string($root)) $formType = $root;
    }

    // Links
    $txtUrl    = g($src, 'linkToTxt','link_to_txt')  ?: g($fld, 'linkToTxt','link_to_txt');
    $htmlUrl   = g($src, 'linkToHtml','link_to_html') ?: g($fld, 'linkToHtml','link_to_html');
    $filingUrl = g($src, 'linkToFiling','link_to_filing') ?: g($fld, 'linkToFiling','link_to_filing');

    logmsg("    Accession $acc | $name | filed $filed");

    // Pull the filing text (accept any content-type)
    $textStatus = 0;
    $text = '';
    if ($txtUrl || $htmlUrl) {
        $text = $txtUrl ? http_get_text($txtUrl, $textStatus) : http_get_text($htmlUrl, $textStatus);
    }
    // If EF-TS didn’t give us a link, try the canonical archive path
    if (!$text && $acc && $cik) {
        $cikNum = ltrim((string)$cik, '0');
        $accNoDashless = str_replace('-', '', (string)$acc);
        $guessTxt = "https://www.sec.gov/Archives/edgar/data/$cikNum/$accNoDashless/$acc.txt";
        $text = http_get_text($guessTxt, $textStatus);
        if ($text) { $txtUrl = $guessTxt; }
    }

    // Parse details & company metadata
    $details = parseBankruptcyDetails($text);
    $meta    = getCompanyMetadata((string)$cik);

    $row = [
        'BankruptcyDate'            => $details['BankruptcyDate'] ?: null,
        'FilingDate'                => $filed ? substr((string)$filed, 0, 10) : null,
        'FilingType'                => $formType ?: null,
        'BankruptcyChapter'         => $details['BankruptcyChapter'] ?: null,
        'CourtName'                 => $details['CourtName'] ?: null,
        'CaseNumber'                => $details['CaseNumber'] ?: null,
        'AccessionNumber'           => $acc ?: null,
        'FilingURL'                 => $filingUrl ?: ($htmlUrl ?: $txtUrl ?: null),
        'CIK'                       => $cik ?: null,
        'CompanyName'               => $name ?: null,
        'exchange'                  => $meta['exchange'] ?? null,
        'IndustrySICCode'           => $meta['IndustrySICCode'] ?? null,
        'FiscalYearEnd'             => $meta['FiscalYearEnd'] ?? null,
        'BusinessAddressState'      => $meta['BusinessAddressState'] ?? null,
        'BusinessAddressZIPCode'    => $meta['BusinessAddressZIPCode'] ?? null,
        'BusinessAddressCity'       => $meta['BusinessAddressCity'] ?? null,
        'BusinessAddressStreet'     => $meta['BusinessAddressStreet'] ?? null,
        'BusinessAddressHouseNumber'=> $meta['BusinessAddressHouseNumber'] ?? null,
        'StateOfIncorporation'      => $meta['StateOfIncorporation'] ?? null,
        'Website'                   => $meta['Website'] ?? null,
        'InvestorWebsite'           => $meta['InvestorWebsite'] ?? null,
        'SIC'                       => $meta['SIC'] ?? null,
        'SICDescription'            => $meta['SICDescription'] ?? null,
    ];

    // Skip clearly empty/unusable rows
    if (!$row['AccessionNumber'] && !$row['FilingDate']) {
        logmsg("      Skipped: missing accession/date (unexpected schema).");
        return false;
    }

    if (!saveFinancialHistory((string)$cik)) {
        logmsg("      Skipped: company facts unavailable for CIK $cik.");
        return false;
    }

    appendToCsv(MAIN_CSV_FILE, $row);
    $filings = getCompanyFilings((string)$cik);
    // saveAnnualReports((string)$cik, $filings);
    // saveQuarterlyReports((string)$cik, $filings);
    saveReportLinks((string)$cik, $filings);
    usleep(POLITE_USLEEP);
    return true;
}

// Process random solvent companies (non-bankrupt) and save their data
function processSolventCompanies(array $excludeCiks): void {
    $all = fetchAllCiks();
    if (empty($all)) {
        logmsg("No CIK list available for solvent sampling.");
        return;
    }
    $pool = array_values(array_diff($all, $excludeCiks));
    if (RANDOM_SEED !== null) mt_srand((int)RANDOM_SEED);
    shuffle($pool);
    $sample = array_slice($pool, 0, min(RANDOM_COMPANY_SAMPLE_SIZE_SOLVENT, count($pool)));
    logmsg("Sampled " . count($sample) . " solvent CIKs: " . implode(', ', $sample));
    $processed = 0;
    foreach ($sample as $cik) {
        logmsg("Processing solvent CIK $cik …");
        $meta = getCompanyMetadata((string)$cik);
        if (empty($meta)) {
            logmsg("  Metadata unavailable for CIK $cik");
        }
        $row = [
            'BankruptcyDate' => null,
            'FilingDate' => null,
            'FilingType' => null,
            'BankruptcyChapter' => null,
            'CourtName' => null,
            'CaseNumber' => null,
            'AccessionNumber' => null,
            'FilingURL' => null,
            'CIK' => $cik,
            'CompanyName' => $meta['CompanyName'] ?? '',
            'exchange' => $meta['exchange'] ?? '',
            'IndustrySICCode' => $meta['IndustrySICCode'] ?? '',
            'FiscalYearEnd' => $meta['FiscalYearEnd'] ?? '',
            'BusinessAddressState' => $meta['BusinessAddressState'] ?? '',
            'BusinessAddressZIPCode' => $meta['BusinessAddressZIPCode'] ?? '',
            'BusinessAddressCity' => $meta['BusinessAddressCity'] ?? '',
            'BusinessAddressStreet' => $meta['BusinessAddressStreet'] ?? '',
            'BusinessAddressHouseNumber' => $meta['BusinessAddressHouseNumber'] ?? '',
            'StateOfIncorporation' => $meta['StateOfIncorporation'] ?? '',
            'Website' => $meta['Website'] ?? '',
            'InvestorWebsite' => $meta['InvestorWebsite'] ?? '',
            'SIC' => $meta['SIC'] ?? '',
            'SICDescription' => $meta['SICDescription'] ?? '',
        ];
        appendToCsv(MAIN_CSV_FILE_SOLVENT, $row);
        saveFinancialHistory((string)$cik, FINANCIAL_CSV_FILE_SOLVENT);
        $filings = getCompanyFilings((string)$cik);
        saveReportLinks((string)$cik, $filings, REPORTS_CSV_FILE_SOLVENT);
        usleep(POLITE_USLEEP);
        $processed++;
    }
    logmsg("Processed $processed solvent company(ies).");
}


































// //////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// //////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// Main part
// //////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// Main (year scan and selection of random companies)

// 1) Fetch all hits for YEAR
$allHits = fetchAllYearHits((string)YEAR);
if (empty($allHits)) {
    logmsg("No hits found for YEAR=" . YEAR . ". Done.");
    if (!$runningInCli) echo "</pre>";
    exit;
}
logmsg("Collected " . count($allHits) . " hits across the year.");

// 2) Filter to 8-K and build CIK=>array_of_hits map
$byCik = [];
$filteredCount = 0;
$missingCikWarned = false;

foreach ($allHits as $hit) {
    if (!isEightK($hit)) continue;
    $src = $hit['_source'] ?? [];
    $fld = $hit['fields']  ?? [];

    $cik = g($src, 'cik') ?: g($fld, 'cik');
    if (!$cik) {
        $ciks = $src['ciks'] ?? ($fld['ciks'] ?? []);
        if (is_array($ciks) && isset($ciks[0]) && !is_array($ciks[0])) {
            $cik = (string)$ciks[0];
        }
    }

    if (!$cik) {
        if (!$missingCikWarned) {
            $missingCikWarned = true;
            logmsg("WARNING: hit without CIK key; will ignore such hits. Example (truncated): " . substr(print_r($hit, true), 0, 800));
        }
        continue;
    }

    $filteredCount++;
    $byCik[(string)$cik][] = $hit;
}

$uniqueCiks = array_keys($byCik);
logmsg("Filtered to 8-K: $filteredCount hits. Found " . count($uniqueCiks) . " unique companies (CIKs) with 8-K 'bankruptcy' hits in " . YEAR . ".");

// 3) Randomly process up to RANDOM_COMPANY_SAMPLE_SIZE CIKs, skipping those without company facts
if (RANDOM_SEED !== null) mt_srand((int)RANDOM_SEED);
shuffle($uniqueCiks);
logmsg("Shuffled CIKs; targeting " . RANDOM_COMPANY_SAMPLE_SIZE . " company(ies).");

$processed = 0;
$selectedCiks = [];
foreach ($uniqueCiks as $cik) {
    if ($processed >= RANDOM_COMPANY_SAMPLE_SIZE) break;
    $hits = $byCik[$cik] ?? [];
    // Stable order by filed date
    usort($hits, function ($a, $b) {
        $sa = g($a['_source'] ?? [], 'filedAt','file_date','fileDate','filed') ?: g($a['fields'] ?? [], 'filedAt','file_date','fileDate','filed');
        $sb = g($b['_source'] ?? [], 'filedAt','file_date','fileDate','filed') ?: g($b['fields'] ?? [], 'filedAt','file_date','fileDate','filed');
        return strcmp((string)$sa, (string)$sb);
    });

    logmsg("Processing CIK $cik with " . count($hits) . " hit(s) …");
    if (!empty($hits) && processSingleHit($hits[0])) {
        $processed++;
        $selectedCiks[] = $cik;
    }
}
processSolventCompanies($uniqueCiks);
logmsg("Done. Processed $processed filing(s) across " . count($selectedCiks) . " companies.");
if (!$runningInCli) {
    echo "</pre>";
}