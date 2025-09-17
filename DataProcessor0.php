<?php
// DataProcessor0.php
// Adds financial ratios and distress scores to financials.csv and financials_solvent.csv

ini_set('display_errors','1');
error_reporting(E_ALL);
set_time_limit(0);
ignore_user_abort(true);
ob_implicit_flush(true);

define('FINANCIAL_CSV_FILE', __DIR__ . '/financials.csv');
define('FINANCIAL_CSV_FILE_SOLVENT', __DIR__ . '/financials_solvent.csv');

$runningInCli = (php_sapi_name() === 'cli');
if (!$runningInCli) {
    header('Content-Type: text/html; charset=UTF-8');
    echo "<!doctype html><meta charset='utf-8'><style>body{background:#000;color:#0f0;font:14px/1.4 monospace;padding:16px}</style><pre>";
}
logmsg('Starting financial data processing…');

function logmsg(string $msg): void {
    $ts = date('H:i:s');
    echo "[$ts] $msg\n";
    flush();
}

function read_csv(string $file): array {
    $rows = [];
    if (!($fh = fopen($file, 'r'))) return [[], []];
    $header = fgetcsv($fh);
    while (($data = fgetcsv($fh)) !== false) {
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

function safeDiv(float|int $num, float|int $den): string|float {
    if ($den == 0) return '';
    return $num / $den;
}

function avg(float $a, float $b): float {
    return ($a + $b) / 2;
}

function process_file(string $file): void {
    logmsg("Processing $file …");
    [$header, $rows] = read_csv($file);
    $newCols = [
        'TL_TA','Debt_Assets','EBIT_InterestExpense','EBITDA_InterestExpense','CFO_Liabilities',
        'CFO_DebtService','CurrentRatio','QuickRatio','WC_TA','ROA','OperatingMargin',
        'DaysAR','DaysINV','DaysAP','CashConversionCycle','Accruals','DividendOmission',
        'DebtIssuanceSpike','DebtRepaymentSpike','AltmanZPrime','AltmanZDoublePrime',
        'OhlsonOScore','OhlsonOScoreProb','ZmijewskiXScore','SpringateSScore','TafflerZScore',
        'FulmerHScore','GroverGScore','BeneishMScore','PiotroskiFScore'
    ];
    foreach ($newCols as $c) {
        if (!in_array($c, $header, true)) $header[] = $c;
    }
    $rowsByCik = [];
    foreach ($rows as $r) $rowsByCik[$r['CIK']][] = $r;
    $outRows = [];
    foreach ($rowsByCik as $cik => $grp) {
        usort($grp, fn($a,$b) => intval($a['year']) <=> intval($b['year']));
        $prev = null;
        foreach ($grp as $row) {
            $ca = (float)($row['CurrentAssets'] ?? 0);
            $cl = (float)($row['CurrentLiabilities'] ?? 0);
            $assets = (float)($row['assets'] ?? 0);
            $liabilities = (float)($row['liabilities'] ?? 0);
            $inventory = (float)($row['InventoryNet'] ?? 0);
            $ltDebt = (float)($row['LongTermDebtNoncurrent'] ?? 0);
            $stBorrow = (float)($row['ShortTermBorrowings'] ?? 0);
            $operIncome = ($row['OperatingIncomeLoss'] !== '' ? (float)$row['OperatingIncomeLoss'] : ((float)$row['NetIncomeLoss'] + (float)$row['InterestExpense'] + (float)$row['IncomeTaxExpenseBenefit']));
            $dep = (float)($row['DepreciationAndAmortization'] ?? 0);
            $interest = (float)($row['InterestExpense'] ?? 0);
            $cfo = (float)($row['NetCashProvidedByUsedInOperatingActivities'] ?? 0);
            $sales = (float)($row['SalesRevenueNet'] ?? 0);
            $cogs = (float)($row['CostOfGoodsSold'] ?? 0);
            $ar = (float)($row['AccountsReceivableNetCurrent'] ?? 0);
            $ap = (float)($row['AccountsPayableCurrent'] ?? 0);
            $ni = (float)($row['NetIncomeLoss'] ?? 0);
            $retEarn = (float)($row['RetainedEarningsAccumulatedDeficit'] ?? 0);
            $equity = (float)($row['equity'] ?? 0);
            $ffo = $ni + $dep;
            $incomeBeforeTax = (float)($row['IncomeBeforeIncomeTaxes'] ?? 0);
            $proceedsDebt = (float)($row['ProceedsFromIssuanceOfDebt'] ?? 0);
            $repayDebt = (float)($row['RepaymentsOfDebt'] ?? 0);
            $divPaid = (float)($row['PaymentsOfDividends'] ?? 0);
            $wc = $ca - $cl;
            $totalDebt = $ltDebt + $stBorrow;
            $quickAssets = $ca - $inventory;

            $row['TL_TA'] = $assets>0 ? safeDiv($liabilities,$assets) : '';
            $row['Debt_Assets'] = $assets>0 ? safeDiv($ltDebt+$stBorrow,$assets) : '';
            $row['EBIT_InterestExpense'] = $interest!=0 ? safeDiv($operIncome,$interest) : '';
            $row['EBITDA_InterestExpense'] = $interest!=0 ? safeDiv($operIncome+$dep,$interest) : '';
            $row['CFO_Liabilities'] = safeDiv($cfo,$liabilities);
            $row['CFO_DebtService'] = safeDiv($cfo,$interest+$repayDebt);
            $row['CurrentRatio'] = safeDiv($ca,$cl);
            $row['QuickRatio'] = safeDiv($quickAssets,$cl);
            $row['WC_TA'] = safeDiv($wc,$assets);
            $row['ROA'] = safeDiv($ni,$assets);
            $row['OperatingMargin'] = safeDiv($operIncome,$sales);
            $row['DaysAR'] = safeDiv(365*$ar,$sales);
            $row['DaysINV'] = safeDiv(365*$inventory,$cogs);
            $row['DaysAP'] = safeDiv(365*$ap,$cogs);
            $row['CashConversionCycle'] = ($row['DaysAR']!=='' && $row['DaysINV']!=='' && $row['DaysAP']!=='') ? $row['DaysAR'] + $row['DaysINV'] - $row['DaysAP'] : '';
            $avgAssets = ($prev && $prev['assets']!=='') ? avg($assets,(float)$prev['assets']) : 0;
            $row['Accruals'] = ($avgAssets>0) ? ($ni - $cfo)/$avgAssets : '';
            $row['DividendOmission'] = ($prev && (float)$prev['PaymentsOfDividends']>0 && $divPaid<=0) ? 1 : 0;
            $row['DebtIssuanceSpike'] = ($prev && (float)$prev['ProceedsFromIssuanceOfDebt']>0 && $proceedsDebt>=3*(float)$prev['ProceedsFromIssuanceOfDebt']) ? 1 : 0;
            $row['DebtRepaymentSpike'] = ($prev && (float)$prev['RepaymentsOfDebt']>0 && $repayDebt>=3*(float)$prev['RepaymentsOfDebt']) ? 1 : 0;

            if ($assets>0) {
                $X1 = (float)safeDiv($wc,$assets);
                $X2 = (float)safeDiv($retEarn,$assets);
                $X3 = (float)safeDiv($operIncome,$assets);
                $X4 = (float)safeDiv($equity,$liabilities);
                $X5 = (float)safeDiv($sales,$assets);
                $row['AltmanZPrime'] = 0.717*$X1 + 0.847*$X2 + 3.107*$X3 + 0.420*$X4 + 0.998*$X5;
                $row['AltmanZDoublePrime'] = 6.56*$X1 + 3.26*$X2 + 6.72*$X3 + 1.05*$X4;
            } else {
                $row['AltmanZPrime'] = '';
                $row['AltmanZDoublePrime'] = '';
            }

            if ($assets>0 && $ca>0 && $cl>0 && $liabilities>0) {
                $TA=$assets; $TL=$liabilities; $WC=$wc; $CL=$cl; $CA=$ca;
                $size = -0.407*log($TA);
                $tl_ta = 6.03*safeDiv($TL,$TA);
                $wc_ta = -1.43*safeDiv($WC,$TA);
                $cl_ca = 0.0757*safeDiv($CL,$CA);
                $tl_gt_ta = ($TL>$TA)? -1.72 : 0;
                $ni_ta = -2.37*safeDiv($ni,$TA);
                $ffo_tl = -1.83*safeDiv($ffo,$TL);
                $neg_earn = ($prev && (float)$prev['NetIncomeLoss']<0 && $ni<0) ? 0.285 : 0;
                $delta_ni = ($prev && ($ni!=0 || (float)$prev['NetIncomeLoss']!=0)) ? -0.521*(($ni-(float)$prev['NetIncomeLoss'])/(abs($ni)+abs((float)$prev['NetIncomeLoss']))) : 0;
                $T = -1.32 + $size + $tl_ta + $wc_ta + $cl_ca + $tl_gt_ta + $ni_ta + $ffo_tl + $neg_earn + $delta_ni;
                $row['OhlsonOScore'] = $T;
                $row['OhlsonOScoreProb'] = 1/(1+exp(-$T));
            } else {
                $row['OhlsonOScore']='';
                $row['OhlsonOScoreProb']='';
            }

            $row['ZmijewskiXScore'] = -4.3 - 4.5*(float)safeDiv($ni,$assets) + 5.7*(float)safeDiv($liabilities,$assets) + 0.004*(float)safeDiv($ca,$cl);
            $A = (float)safeDiv($wc,$assets);
            $B = (float)safeDiv($operIncome,$assets);
            $C = (float)safeDiv($incomeBeforeTax,$cl);
            $D = (float)safeDiv($sales,$assets);
            $row['SpringateSScore'] = 1.03*$A + 3.07*$B + 0.66*$C + 0.40*$D;
            $x1 = (float)safeDiv($incomeBeforeTax,$cl);
            $x2 = (float)safeDiv($ca,$liabilities);
            $x3 = (float)safeDiv($cl,$assets);
            $dailyOpEx = ($sales - $incomeBeforeTax - $dep)/365;
            $x4 = (float)safeDiv($quickAssets - $cl, $dailyOpEx);
            $row['TafflerZScore'] = 3.20 + 12.18*$x1 + 2.50*$x2 - 10.68*$x3 + 0.029*$x4;
            $avgRetEarn = $prev ? avg($retEarn,(float)$prev['RetainedEarningsAccumulatedDeficit']) : $retEarn;
            $avgAssets2 = $prev ? avg($assets,(float)$prev['assets']) : $assets;
            $avgTotalDebt = $prev ? avg($totalDebt,(float)$prev['LongTermDebtNoncurrent']+(float)$prev['ShortTermBorrowings']) : $totalDebt;
            $X1 = (float)safeDiv($avgRetEarn,$avgAssets2);
            $X2 = (float)safeDiv($sales,$avgAssets2);
            $X3 = (float)safeDiv($operIncome,$equity);
            $X4 = (float)safeDiv($cfo,$avgTotalDebt);
            $X5 = (float)safeDiv($avgTotalDebt,$equity);
            $X6 = (float)safeDiv($cl,$avgAssets2);
            $tangible = $assets - (float)($row['Goodwill'] ?? 0) - (float)($row['IntangibleAssetsNetExcludingGoodwill'] ?? 0);
            $tangiblePrev = $prev ? ((float)$prev['assets'] - (float)$prev['Goodwill'] - (float)$prev['IntangibleAssetsNetExcludingGoodwill']) : $tangible;
            $avgTangible = $prev ? avg($tangible,$tangiblePrev) : $tangible;
            $X7 = $avgTangible>0 ? log($avgTangible) : '';
            $X8 = ($avgTotalDebt>0) ? safeDiv($wc,$avgTotalDebt) : '';
            $X9 = ($operIncome>0 && $interest!=0) ? safeDiv(log($operIncome),$interest) : '';
            $row['FulmerHScore'] = ($X7!=='' && $X8!=='' && $X9!=='') ? (5.528*(float)$X1 + 0.212*(float)$X2 + 0.73*(float)$X3 + 1.27*(float)$X4 -0.12*(float)$X5 + 2.335*(float)$X6 + 0.575*(float)$X7 + 1.083*(float)$X8 + 0.894*(float)$X9 -6.075) : '';
            $row['GroverGScore'] = 1.650*$A + 3.404*$B - 0.016*(float)safeDiv($ni,$assets) + 0.057;
            if ($prev) {
                $ar_prev = (float)$prev['AccountsReceivableNetCurrent'];
                $sales_prev = (float)$prev['SalesRevenueNet'];
                $dsri = ($sales!=0 && $sales_prev!=0) ? safeDiv($ar/$sales, $ar_prev/$sales_prev) : '';
                $gmi = ($sales_prev!=0 && $sales!=0) ? safeDiv(($sales_prev-(float)$prev['CostOfGoodsSold'])/$sales_prev, ($sales-$cogs)/$sales) : '';
                $ppent = (float)$row['PropertyPlantAndEquipmentNet'];
                $ppent_prev = (float)$prev['PropertyPlantAndEquipmentNet'];
                $aqi = ($assets!=0 && (float)$prev['assets']!=0) ? safeDiv(1-($ca+$ppent)/$assets,1-((float)$prev['CurrentAssets']+$ppent_prev)/(float)$prev['assets']) : '';
                $sgi = ($sales_prev!=0) ? safeDiv($sales,$sales_prev) : '';
                $dep_prev = (float)$prev['DepreciationAndAmortization'];
                $depi = (($ppent_prev+$dep_prev)!=0 && ($ppent+$dep)!=0) ? safeDiv($dep_prev/($ppent_prev+$dep_prev), $dep/($ppent+$dep)) : '';
                $sgai = ($sales_prev!=0 && $sales!=0) ? safeDiv((float)$row['SellingGeneralAndAdministrativeExpense']/$sales, (float)$prev['SellingGeneralAndAdministrativeExpense']/$sales_prev) : '';
                $lvgi = ($assets!=0 && (float)$prev['assets']!=0) ? safeDiv(($cl+$ltDebt)/$assets, ((float)$prev['CurrentLiabilities']+(float)$prev['LongTermDebtNoncurrent'])/(float)$prev['assets']) : '';
                $tata = $assets!=0 ? ($ni-$cfo)/$assets : '';
                $row['BeneishMScore'] = -4.84 + 0.92*(float)$dsri + 0.528*(float)$gmi + 0.404*(float)$aqi + 0.892*(float)$sgi + 0.115*(float)$depi - 0.172*(float)$sgai + 4.679*(float)$tata - 0.327*(float)$lvgi;
            } else {
                $row['BeneishMScore'] = '';
            }
            $f = 0;
            if ($assets>0 && $ni>0) $f++;
            if ($cfo>0) $f++;
            if ($prev) {
                $roa_prev = safeDiv((float)$prev['NetIncomeLoss'], (float)$prev['assets']);
                $roa_curr = safeDiv($ni,$assets);
                if ($roa_prev!=='' && $roa_curr!=='' && $roa_curr>$roa_prev) $f++;
            }
            if ($cfo > $ni) $f++;
            if ($prev) {
                $lev_prev = safeDiv((float)$prev['LongTermDebtNoncurrent'], (float)$prev['assets']);
                $lev_curr = safeDiv($ltDebt,$assets);
                if ($lev_prev!=='' && $lev_curr!=='' && $lev_curr < $lev_prev) $f++;
                $cr_prev = safeDiv((float)$prev['CurrentAssets'], (float)$prev['CurrentLiabilities']);
                $cr_curr = safeDiv($ca,$cl);
                if ($cr_prev!=='' && $cr_curr!=='' && $cr_curr > $cr_prev) $f++;
                $shares_prev = (float)$prev['WeightedAverageNumberOfSharesOutstandingBasic'];
                $shares_curr = (float)$row['WeightedAverageNumberOfSharesOutstandingBasic'];
                if ($shares_curr <= $shares_prev) $f++;
                $gm_prev = $sales_prev!=0 ? safeDiv((float)$prev['GrossProfit'],$sales_prev) : '';
                $gm_curr = $sales!=0 ? safeDiv((float)$row['GrossProfit'],$sales) : '';
                if ($gm_prev!=='' && $gm_curr!=='' && $gm_curr>$gm_prev) $f++;
                $at_prev = safeDiv($sales_prev,(float)$prev['assets']);
                $at_curr = safeDiv($sales,$assets);
                if ($at_prev!=='' && $at_curr!=='' && $at_curr>$at_prev) $f++;
            }
            $row['PiotroskiFScore'] = $f;

            $prev = $row;
            $outRows[] = $row;
        }
    }
    write_csv($file,$header,$outRows);
    logmsg("Processed " . count($outRows) . " row(s). Done.");
}

process_file(FINANCIAL_CSV_FILE);
process_file(FINANCIAL_CSV_FILE_SOLVENT);
logmsg('All files processed.');
?>