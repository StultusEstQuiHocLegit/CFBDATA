# OVERALL PLAN
Just like in `DataProcessor1.php`, batches are built with randomly selected and shuffled data and then evaluated by AI. The new batch size is 40 and the model is `gpt-5` with maximum reasoning effort and extensive processing time granted. After that, the same was done with `grok-4` from xAI. For each batch processing, a new chat was opened and the system cache, system memory and browser cache cleared. In total: 200 total rows in both files / 40 rows per batch = 5, 5 * 2 AIs = 10, 10 * 2 versions (stripped and extended) = 20 were run.

# STEPS

## FIRST ROUND

The results are added in the new columns `AIExpectedLikelihoodOfBankruptcyBaseStrongerModelGPT` and `AIExpectedLikelihoodOfBankruptcyBaseStrongerModelGPTExplanation` in the correspoding rows of the files `financials_subset.csv` and `financials_solvent_subset.csv`, matched by `CIK` (`CompanyID` was coded back to original `CIK`) manually. As it is also run with `grok-4`, also the columns `AIExpectedLikelihoodOfBankruptcyBaseStrongerModelGROK` and `AIExpectedLikelihoodOfBankruptcyBaseStrongerModelGROKExplanation`  are added correspodingly.

## SECOND ROUND

The results are added in the new columns `AIExpectedLikelihoodOfBankruptcyExtendedStrongerModelGPT` and `AIExpectedLikelihoodOfBankruptcyExtendedStrongerModelGPTExplanation` in the correspoding rows of the files `financials_subset.csv` and `financials_solvent_subset.csv`, matched by `CIK` (`CompanyID` was coded back to original `CIK`) manually. As it is also run with `grok-4`, also the columns `AIExpectedLikelihoodOfBankruptcyExtendedStrongerModelGROK` and `AIExpectedLikelihoodOfBankruptcyExtendedStrongerModelGROKExplanation` are added correspodingly.

# BASE SYSTEM PROMPT

    Please use reasoning and think ultrahard about this:
    # SETUP
    You are a financial analyst.

    # TASK
    Given CSV financial data for multiple companies, rate the expected likelihood of bankruptcy for each row for the correspodingly next year **on a scale from 0 (very     unlikely) to 100 (very likely)**. Also add a short explanation after each value.

    # NOTES
    There are exactly 40 rows in the dataset. Respond with **only** a comma-separated list of $expectedCount bankruptcy-likelihood numbers, one per row, in the     **exact** same order as the input rows. Use the format: "[CorrespodingCompanyID] -> [ExpectedLikelihoodOfBankruptcy] ([EnterYourExplanationHere])".
    Don't add anything else.

    # EXAMPLE
    Example of your output:
    36272617 -> 34 (SomeExplanationHere), 261836129 -> 28 (SomeExplanationHere), 3487293 -> 62 (SomeExplanationHere), 348710 -> 98 (SomeExplanationHere), 103892 -> 5 (SomeExplanationHere), 3201983 -> 49 (SomeExplanationHere), 312010 -> 30 (SomeExplanationHere), 20192002 -> 3 (SomeExplanationHere), 172910 -> 48 (SomeExplanationHere), 1028930 -> 61 (SomeExplanationHere), 49201 -> 96 (SomeExplanationHere), ..., 192032 -> 85 (SomeExplanationHere), 2830201 -> 77 (SomeExplanationHere), 93829 -> 82 (SomeExplanationHere), 19372 -> 32 (SomeExplanationHere), 2919032 -> 57 (SomeExplanationHere)

(Note: The leading "Please use reasoning and think ultrahard about this:" is just a trick to circumvent the processing time limit, which OpenAI normally uses to save costs on their side)

# RAW RESULTS

## GPT 5

[processing took: 2 minutes and 54 seconds]

    LEARNING: AI is calculating ratios itself if they aren't given (but apparently not the scores).

[processing took: 3 minutes and 37 seconds]

[processing took: 3 minutes and 17 seconds]

[processing took: 3 minutes and 13 seconds]

[processing took: 3 minutes and 8 seconds]

[processing took: 2 minutes and 55 seconds]

[processing took: 3 minutes and 42 seconds]

[processing took: 2 minutes and 22 seconds]

[processing took: 2 minutes and 33 seconds]

[processing took: 4 minutes and 9 seconds]


## GROK-4

[processing took: 2 minutes and 12 seconds]

[processing took: 2 minutes and 21 seconds]

[processing took: 3 minutes and 52 seconds]

[processing took: 2 minutes and 45 seconds]

[processing took: 4 minutes and 52 seconds]

[processing took: 3 minutes and 25 seconds]

[processing took: 4 minutes and 28 seconds]

[processing took: 3 minutes and 17 seconds]

[processing took: 5 minutes and 55 seconds]

[processing took: 5 minutes and 6 seconds]





