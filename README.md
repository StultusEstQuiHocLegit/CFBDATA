# CFDATA

This project gathers bankruptcy related 8‑K filings from the SEC EDGAR system and enriches them with company metadata and historical financial data.

Three CSV files are produced (and reports as attachments can be saved too if the correspoding part is commented in / activated) for bankrupt companies when running `DataCollector0.php`: `main.csv`, `financials.csv` and `reports.csv`.
In addition, we also select solvent/healthy/non-bankrupt companies and save their information in: `main_solvent.csv`, `financials_solvent.csv` and `reports_solvent.csv`, whereas in `main_solvent.csv` of course not all columns are filled out (only the columns regarding general company information).

## main.csv

One row per company containing the first bankruptcy filing found in the scanned year.

| Column | Description |
| --- | --- |
| BankruptcyDate | date extracted from the filing text |
| FilingDate | date the 8‑k was filed |
| FilingType | form type of the filing |
| BankruptcyChapter | bankruptcy chapter if detected |
| CourtName | court handling the bankruptcy |
| CaseNumber | bankruptcy case number |
| AccessionNumber | sec accession number |
| FilingURL | url of the filing |
| CIK | central index key |
| CompanyName | company name |
| exchange | primary exchange of the company |
| IndustrySICCode | industry sic code |
| FiscalYearEnd | company's fiscal year end |
| BusinessAddressState | business address state |
| BusinessAddressZIPCode | business address zip code |
| BusinessAddressCity | business address city |
| BusinessAddressStreet | business address street |
| BusinessAddressHouseNumber | business address house number (if available) |
| StateOfIncorporation | state of incorporation |
| Website | company website |
| InvestorWebsite | investor relations website |
| SIC | sic code |
| SICDescription | description of the sic code |

Example for general company data:
- [Apple](https://data.sec.gov/submissions/CIK0000320193.json)

## financials.csv

Historical financial metrics for each company from `YEAR - 10` through `YEAR` (2014‑2024 when YEAR=2024). Each row represents one year.

| Column | Description |
| --- | --- |
| idpk | sequential row identifier |
| CIK | central index key matching main.csv |
| year | fiscal year of the data |
| assets | total assets |
| CurrentAssets | current assets |
| NoncurrentAssets | non‑current assets |
| liabilities | total liabilities |
| CurrentLiabilities | current liabilities |
| NoncurrentLiabilities | non‑current liabilities |
| LiabilitiesAndStockholdersEquity | liabilities and stockholders' equity |
| equity | stockholders' equity |
| CommonStockValue | value of common stock |
| RetainedEarningsAccumulatedDeficit | retained earnings or accumulated deficit |
| AccumulatedOtherComprehensiveIncomeLoss | aoci |
| MinorityInterest | minority interest |
| revenues | total revenues |
| SalesRevenueNet | net sales revenue |
| CostOfGoodsSold | cost of goods sold |
| GrossProfit | gross profit |
| OperatingExpenses | operating expenses |
| SellingGeneralAndAdministrativeExpense | sg&a expense |
| ResearchAndDevelopmentExpense | research and development expense |
| OperatingIncomeLoss | operating income or loss |
| InterestExpense | interest expense |
| IncomeBeforeIncomeTaxes | income before taxes |
| IncomeTaxExpenseBenefit | income tax expense or benefit |
| NetIncomeLoss | net income or loss |
| PreferredStockDividendsAndOtherAdjustments | preferred stock dividends and other adjustments |
| NetIncomeLossAvailableToCommonStockholdersBasic | net income available to common stockholders (basic) |
| EarningsPerShareBasic | basic earnings per share |
| EarningsPerShareDiluted | diluted earnings per share |
| WeightedAverageNumberOfSharesOutstandingBasic | weighted average shares outstanding (basic) |
| WeightedAverageNumberOfDilutedSharesOutstanding | weighted average shares outstanding (diluted) |
| NetCashProvidedByUsedInOperatingActivities | net cash provided by or used in operating activities |
| NetCashProvidedByUsedInInvestingActivities | net cash provided by or used in investing activities |
| NetCashProvidedByUsedInFinancingActivities | net cash provided by or used in financing activities |
| CashAndCashEquivalentsPeriodIncreaseDecrease | increase/decrease in cash and cash equivalents |
| CashAndCashEquivalentsAtCarryingValue | cash and cash equivalents at carrying value |
| PaymentsToAcquirePropertyPlantAndEquipment | capital expenditures |
| ProceedsFromIssuanceOfCommonStock | proceeds from issuing common stock |
| PaymentsOfDividends | dividend payments |
| RepaymentsOfDebt | debt repayments |
| ProceedsFromIssuanceOfDebt | proceeds from issuing debt |
| DepreciationAndAmortization | depreciation and amortization |
| InventoryNet | net inventory |
| AccountsReceivableNetCurrent | net current accounts receivable |
| AccountsPayableCurrent | current accounts payable |
| Goodwill | goodwill |
| IntangibleAssetsNetExcludingGoodwill | intangible assets excluding goodwill |
| PropertyPlantAndEquipmentNet | property, plant and equipment (net) |
| LongTermDebtNoncurrent | non‑current long‑term debt |
| ShortTermBorrowings | short‑term borrowings |
| IncomeTaxesPayableCurrent | current income taxes payable |
| EntityRegistrantName | entity registrant name |
| EntityCentralIndexKey | central index key (repeated) |
| TradingSymbol | trading symbol |
| EntityIncorporationStateCountryCode | state or country of incorporation |
| EntityFilerCategory | filer category |
| DocumentPeriodEndDate | document period end date |
| DocumentFiscalPeriodFocus | document fiscal period focus |
| DocumentFiscalYearFocus | document fiscal year focus |
| DocumentType | document type |
| AmendmentFlag | amendment flag |
| CurrentFiscalYearEndDate | current fiscal year end date |

Example for company details data:
- [Apple](https://data.sec.gov/api/xbrl/companyfacts/CIK0000320193.json)

## reports.csv

Historical reports for each company from `YEAR - 10` through `YEAR` (2014‑2024 when YEAR=2024). Each row represents one year.

| Column | Description |
| --- | --- |
| idpk | sequential row identifier |
| CIK | central index key matching main.csv |
| year | fiscal year of the data |
| AnnualReportLink | link to the HTML file with the annual report |
| QuarterlyReportLinkQ1 | link to the HTML file with the quarterly report for Q1 |
| QuarterlyReportLinkQ2 | link to the HTML file with the quarterly report for Q2 |
| QuarterlyReportLinkQ3 | link to the HTML file with the quarterly report for Q3 |
| QuarterlyReportLinkQ4 | link to the HTML file with the quarterly report for Q4 |

## ./AnnualReports/

Historical annual reports for each company from `YEAR - 10` through `YEAR` (2014‑2024 when YEAR=2024).
Saved in the format: `[CIK]_[YEAR]`.html

Example for annual report company data:
- [Apple](https://www.sec.gov/Archives/edgar/data/320193/000032019324000123/aapl-20240928.htm)

## ./QuarterlyReports/

Historical quarterly reports for each company from `YEAR - 10` through `YEAR` (2014‑2024 when YEAR=2024).
Saved in the format: `[CIK]_[YEAR]_["Q1" or "Q2" or "Q3" or "Q4"]`.html

Example for quarterly report company data:
- [Apple](https://www.sec.gov/Archives/edgar/data/320193/000032019325000057/aapl-20250329.htm)

### USA Federal Holidays for Year 2024

On the following days and on weekends, of course no bankruptcies happened.

| Date        | Day of Week | Holiday Name                               |
| ----------- | ----------- | ------------------------------------------ |
| January 1   | Monday      | New Year’s Day                             |
| January 15  | Monday      | Birthday of Martin Luther King, Jr.        |
| February 19 | Monday      | Washington’s Birthday (aka Presidents Day) |
| May 27      | Monday      | Memorial Day                               |
| June 19     | Wednesday   | Juneteenth National Independence Day       |
| July 4      | Thursday    | Independence Day                           |
| September 2 | Monday      | Labor Day                                  |
| October 14  | Monday      | Columbus Day                               |
| November 11 | Monday      | Veterans Day                               |
| November 28 | Thursday    | Thanksgiving Day                           |
| December 25 | Wednesday   | Christmas Day                              |

## Financial Ratio Columns

The `DataProcessor0.php` script enriches `financials.csv` and `financials_solvent.csv` with additional ratios and distress scores. The table below lists all columns now present in these files, old and new ones.

| Column | Description | Explanation |
| --- | --- | --- |
| idpk | sequential row identifier | |
| CIK | central index key matching main.csv | |
| year | fiscal year of the data | |
| assets | total assets | |
| CurrentAssets | current assets | |
| NoncurrentAssets | non‑current assets | |
| liabilities | total liabilities | |
| CurrentLiabilities | current liabilities | |
| NoncurrentLiabilities | non‑current liabilities | |
| LiabilitiesAndStockholdersEquity | liabilities and stockholders' equity | |
| equity | stockholders' equity | |
| CommonStockValue | value of common stock | |
| RetainedEarningsAccumulatedDeficit | retained earnings or accumulated deficit | |
| AccumulatedOtherComprehensiveIncomeLoss | aoci | |
| MinorityInterest | minority interest | |
| revenues | total revenues | |
| SalesRevenueNet | net sales revenue | |
| CostOfGoodsSold | cost of goods sold | |
| GrossProfit | gross profit | |
| OperatingExpenses | operating expenses | |
| SellingGeneralAndAdministrativeExpense | sg&a expense | |
| ResearchAndDevelopmentExpense | research and development expense | |
| OperatingIncomeLoss | operating income or loss | |
| InterestExpense | interest expense | |
| IncomeBeforeIncomeTaxes | income before taxes | |
| IncomeTaxExpenseBenefit | income tax expense or benefit | |
| NetIncomeLoss | net income or loss | |
| PreferredStockDividendsAndOtherAdjustments | preferred stock dividends and other adjustments | |
| NetIncomeLossAvailableToCommonStockholdersBasic | net income available to common stockholders (basic) | |
| EarningsPerShareBasic | basic earnings per share | |
| EarningsPerShareDiluted | diluted earnings per share | |
| WeightedAverageNumberOfSharesOutstandingBasic | weighted average shares outstanding (basic) | |
| WeightedAverageNumberOfDilutedSharesOutstanding | weighted average shares outstanding (diluted) | |
| NetCashProvidedByUsedInOperatingActivities | net cash provided by or used in operating activities | |
| NetCashProvidedByUsedInInvestingActivities | net cash provided by or used in investing activities | |
| NetCashProvidedByUsedInFinancingActivities | net cash provided by or used in financing activities | |
| CashAndCashEquivalentsPeriodIncreaseDecrease | increase/decrease in cash and cash equivalents | |
| CashAndCashEquivalentsAtCarryingValue | cash and cash equivalents at carrying value | |
| PaymentsToAcquirePropertyPlantAndEquipment | capital expenditures | |
| ProceedsFromIssuanceOfCommonStock | proceeds from issuing common stock | |
| PaymentsOfDividends | dividend payments | |
| RepaymentsOfDebt | debt repayments | |
| ProceedsFromIssuanceOfDebt | proceeds from issuing debt | |
| DepreciationAndAmortization | depreciation and amortization | |
| InventoryNet | net inventory | |
| AccountsReceivableNetCurrent | net current accounts receivable | |
| AccountsPayableCurrent | current accounts payable | |
| Goodwill | goodwill | |
| IntangibleAssetsNetExcludingGoodwill | intangible assets excluding goodwill | |
| PropertyPlantAndEquipmentNet | property, plant and equipment (net) | |
| LongTermDebtNoncurrent | non‑current long‑term debt | |
| ShortTermBorrowings | short‑term borrowings | |
| IncomeTaxesPayableCurrent | current income taxes payable | |
| EntityRegistrantName | entity registrant name | |
| EntityCentralIndexKey | central index key (repeated) | |
| TradingSymbol | trading symbol | |
| EntityIncorporationStateCountryCode | state or country of incorporation | |
| EntityFilerCategory | filer category | |
| DocumentPeriodEndDate | document period end date | |
| DocumentFiscalPeriodFocus | document fiscal period focus | |
| DocumentFiscalYearFocus | document fiscal year focus | |
| DocumentType | document type | |
| AmendmentFlag | amendment flag | |
| CurrentFiscalYearEndDate | current fiscal year end date | |
| TL_TA | leverage (total liabilities / total assets) | measures overall leverage by comparing total liabilities to total assets, formula: `liabilities / assets`. |
| Debt_Assets | debt to assets | uses long‑term and short‑term borrowings as a proxy for total debt, formula: `(LongTermDebtNoncurrent + ShortTermBorrowings) / assets`. |
| EBIT_InterestExpense | EBIT over interest expense | interest coverage using `OperatingIncomeLoss` as an EBIT proxy, formula: `OperatingIncomeLoss / InterestExpense`. |
| EBITDA_InterestExpense | EBITDA over interest expense | adds `DepreciationAndAmortization` to operating income to approximate EBITDA, formula: `(OperatingIncomeLoss + DepreciationAndAmortization) / InterestExpense`. |
| CFO_Liabilities | cash flow to liabilities | gauges ability to cover total liabilities with operating cash flow, formula: `NetCashProvidedByUsedInOperatingActivities / liabilities`. |
| CFO_DebtService | cash flow to interest and debt payments | measures cash flow sufficiency for servicing interest and principal, formula: `NetCashProvidedByUsedInOperatingActivities / (InterestExpense + RepaymentsOfDebt)`. |
| CurrentRatio | current ratio | liquidity metric of short‑term assets over short‑term obligations, formula: `CurrentAssets / CurrentLiabilities`. |
| QuickRatio | quick ratio | acid‑test ratio excluding inventory from current assets, formula: `(CurrentAssets - InventoryNet) / CurrentLiabilities`. |
| WC_TA | working capital to assets | working capital relative to total assets, formula: `(CurrentAssets - CurrentLiabilities) / assets`. |
| ROA | return on assets | profitability ratio showing net income generated per dollar of assets, formula: `NetIncomeLoss / assets`. |
| OperatingMargin | operating margin | operating profitability per sales dollar, formula: `OperatingIncomeLoss / SalesRevenueNet`. |
| DaysAR | days accounts receivable | average collection period for receivables, formula: `365 * AccountsReceivableNetCurrent / SalesRevenueNet`. |
| DaysINV | days inventory | average days inventory is held, formula: `365 * InventoryNet / CostOfGoodsSold`. |
| DaysAP | days accounts payable | average time to pay suppliers, formula: `365 * AccountsPayableCurrent / CostOfGoodsSold`. |
| CashConversionCycle | cash conversion cycle | operating cycle net of payables, formula: `DaysAR + DaysINV - DaysAP`. |
| Accruals | Sloan accruals | measures accrual quality using average assets in the denominator, formula: `(NetIncomeLoss - NetCashProvidedByUsedInOperatingActivities) / average(assets)`. |
| DividendOmission | dividend dropped to zero after being positive | indicator equals 1 if `PaymentsOfDividends` falls to zero after previously being positive |
| DebtIssuanceSpike | large spike in debt issuance | flags when `ProceedsFromIssuanceOfDebt` is at least three times the prior year, indicator equals 1 if condition met |
| DebtRepaymentSpike | large spike in debt repayments | flags when `RepaymentsOfDebt` is at least three times the prior year, indicator equals 1 if condition met |
| AltmanZPrime | Altman Z′ score | bankruptcy risk model for private firms, formula: `0.717*(CurrentAssets - CurrentLiabilities)/assets + 0.847*RetainedEarningsAccumulatedDeficit/assets + 3.107*OperatingIncomeLoss/assets + 0.420*equity/liabilities + 0.998*SalesRevenueNet/assets` |
| AltmanZDoublePrime | Altman Z″ score | variant for non‑manufacturers, formula: `6.56*(CurrentAssets - CurrentLiabilities)/assets + 3.26*RetainedEarningsAccumulatedDeficit/assets + 6.72*OperatingIncomeLoss/assets + 1.05*equity/liabilities`. |
| OhlsonOScore | Ohlson distress score T | logit‑based distress score incorporating size, leverage, liquidity and performance, formula: `-1.32 - 0.407*ln(assets) + 6.03*liabilities/assets - 1.43*(CurrentAssets - CurrentLiabilities)/assets + 0.0757*CurrentLiabilities/CurrentAssets - 1.72*I(liabilities > assets) - 2.37*NetIncomeLoss/assets - 1.83*(NetIncomeLoss + DepreciationAndAmortization)/liabilities + 0.285*I(NetIncomeLoss<0 & prev NetIncomeLoss<0) - 0.521*(NetIncomeLoss - prev NetIncomeLoss)/(abs(NetIncomeLoss)+abs(prev NetIncomeLoss))`. |
| OhlsonOScoreProb | probability of distress | converts OhlsonOScore to probability, formula: `exp(OhlsonOScore) / (1 + exp(OhlsonOScore))`. |
| ZmijewskiXScore | Zmijewski X-score | probit model using profitability, leverage and liquidity, formula: `-4.3 - 4.5*(NetIncomeLoss/assets) + 5.7*(liabilities/assets) + 0.004*(CurrentAssets/CurrentLiabilities)`. |
| SpringateSScore | Springate S-score | four‑ratio failure model, formula: `1.03*(CurrentAssets - CurrentLiabilities)/assets + 3.07*OperatingIncomeLoss/assets + 0.66*IncomeBeforeIncomeTaxes/CurrentLiabilities + 0.40*SalesRevenueNet/assets`. |
| TafflerZScore | Taffler z-score | a model emphasizing short‑term funding capacity, formula: `3.20 + 12.18*IncomeBeforeIncomeTaxes/CurrentLiabilities + 2.50*CurrentAssets/liabilities - 10.68*CurrentLiabilities/assets + 0.029*((CurrentAssets - InventoryNet - CurrentLiabilities)/((SalesRevenueNet - IncomeBeforeIncomeTaxes - DepreciationAndAmortization)/365))`. |
| FulmerHScore | Fulmer H-score | nine‑variable failure model using multi‑year averages, formula: `5.528*avg(RetainedEarningsAccumulatedDeficit)/avg(assets) + 0.212*SalesRevenueNet/avg(assets) + 0.73*OperatingIncomeLoss/equity + 1.27*NetCashProvidedByUsedInOperatingActivities/avg(TotalDebt) - 0.12*avg(TotalDebt)/equity + 2.335*CurrentLiabilities/avg(assets) + 0.575*ln(avg(assets - Goodwill - IntangibleAssetsNetExcludingGoodwill)) + 1.083*avg(CurrentAssets - CurrentLiabilities)/avg(TotalDebt) + 0.894*ln(OperatingIncomeLoss)/InterestExpense - 6.075`, where `TotalDebt = ShortTermBorrowings + LongTermDebtNoncurrent`. |
| GroverGScore | Grover G-score | three‑ratio discriminant model, formula: `1.650*(CurrentAssets - CurrentLiabilities)/assets + 3.404*OperatingIncomeLoss/assets - 0.016*NetIncomeLoss/assets + 0.057`. |
| BeneishMScore | Beneish M-score | flags potential earnings manipulation using eight year‑over‑year indices (DSRI, GMI, AQI, SGI, DEPI, SGAI, TATA, LVGI), formula: `-4.84 + 0.92*DSRI + 0.528*GMI + 0.404*AQI + 0.892*SGI + 0.115*DEPI - 0.172*SGAI + 4.679*TATA - 0.327*LVGI`. |
| PiotroskiFScore | Piotroski F-score (0‑9) | sum of nine binary signals on profitability, leverage, liquidity and operating efficiency, each signal equals 1 when an improvement condition is met, else 0. |

## DataProcessor1.php

`DataProcessor1.php` calls the OpenAI API to rate the expected likelihood of bankruptcy for each company for each year in `financials.csv` and `financials_solvent.csv`. It runs two rounds per row per batch: the base round uses only the original financial columns, while the extended round also includes the derived ratios and scores. Before sending data to the model, `idpk` is removed and `CIK` is renamed to `CompanyID` to decrease payload size and increase clarity. The resulting percentages are stored in the rows `AIExpectedLikelihoodOfBankruptcyBase` and `AIExpectedLikelihoodOfBankruptcyExtended`, which are added to both CSV files correspondingly.

**Note:** For replication, before running the script, edit `config.php` and replace `SAMEPLACEHOLDERFORTHEAPIKEY` with your actual API key.

The initial AI-based approach did not succeed. During fine-tuning and prompt refinement, the batch size was reduced from 200 to 50, and the model was upgraded from `gpt-5-nano` to `gpt-5-mini`. This adjustment was made because, in the second round of each batch (the extended round with ratios and scores), the outputs often consisted of unrealistically small numbers across both bankrupt and solvent companies. A likely explanation is that many of the derived ratios and distress scores naturally yield small numerical values, and the AI adapted to this scale.  

As a result, the workload per batch was reduced and model strength increased. The outputs then began to stretch across the full scale instead of collapsing into single-digit values. In hindsight, this behavior was probably the first warning sign. It is also possible that the issue was partially user-induced by tweaking prompts until the results "looked right" to the human eye. However, it can reasonably be argued that outputs such as `"83, 3, 2, 2, 5, 2, ..., 2, 2, 2, 3, 2, 2, 2, 2, 1, 2, 2, 2"`, `"0, 0, 0, 0, ..., 0, 0"`, or `"1, 2, 3, ..., 199, 200"` are obviously wrong, and rejecting them was appropriate.  

The consistently lower values for `AIExpectedLikelihoodOfBankruptcyExtended` compared to `AIExpectedLikelihoodOfBankruptcyBase` may still stem from this scaling issue, with the many small ratios nudging the AI towards the bottom of the scale.  

Overall, the results resembled “a human guessing after glancing at each row for five seconds, without performing calculations or analyzing the numbers in depth.” To improve this, stronger models will now be used with increased processing time. For cost reasons, subsamples of 100 bankrupt companies (from 2024) and 100 solvent companies (also from 2024) are selected for evaluation.

## DataProcessor2.php

`DataProcessor2.php` selects 100 bankrupt and 100 solvent companies from the 2024 data that have an `AnnualReportLink` (because this will be needed later for text analyses) randomly and writes them to `main_subset.csv`, `financials_subset.csv`, `reports_subset.csv` and the corresponding `main_solvent_subset.csv`, `financials_solvent_subset.csv` and `reports_solvent_subset.csv` files for focused experiments.

## DataProcessor3.md

Again, just like in `DataProcessor1.php`, batches were built and evaluated by AI, but now the correspoding data was retrieved from `financials_subset.csv` and `financials_solvent_subset.csv` and the process is performed manually. This was necessary because of of the need to keep costs low, in theory this would of course also be possible with the whole set and in an automated way, just like in `DataProcessor1.php`. Again, before sending data to the model, `idpk` was removed and `CIK` was renamed to `CompanyID` to decrease payload size and increase clarity. Also `AIExpectedLikelihoodOfBankruptcyBase` and `AIExpectedLikelihoodOfBankruptcyExtended` were removed. The resulting new percentages and now also explanations were stored in the columns `AIExpectedLikelihoodOfBankruptcyBaseStrongerModelGPT`, `AIExpectedLikelihoodOfBankruptcyBaseStrongerModelGPTExplanation`, `AIExpectedLikelihoodOfBankruptcyExtendedStrongerModelGPT` and `AIExpectedLikelihoodOfBankruptcyExtendedStrongerModelGPTExplanation`, which were added to both CSV files correspondingly. The new batch size was 40 and the model was `gpt-5` with maximum reasoning effort and extensive processing time granted. After that, the same was done with `grok-4` from xAI and thus correspodingly the columns `AIExpectedLikelihoodOfBankruptcyBaseStrongerModelGROK`, `AIExpectedLikelihoodOfBankruptcyBaseStrongerModelGROKExplanation`, `AIExpectedLikelihoodOfBankruptcyExtendedStrongerModelGROK` and `AIExpectedLikelihoodOfBankruptcyExtendedStrongerModelGROKExplanation` were added.