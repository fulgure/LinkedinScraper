<?php

/**
 * Represents a company, as well as its potential names
 */
class EPotentialURL
{

    /**
     * The minimum number of chars a word must have to be considered relevant to the search
     */
    public const MIN_WORD_LENGTH = 3;

    /**
     * The company's ID
     *
     * @var int
     */
    public $id;

    /**
     * List of common words in company names (SA, limited, etc...)
     * We filter these words out, because using those to match companies would give a lot of false positives
     * All strings should be lowercase
     * 
     * @var array
     */
    static public $COMMON_WORDS = [
        "international","internationale", "sa", "gmbh", "limited", "versicherungen", "switzerland", "régie", "groupe", "group", "ag", "co", "ltd", "ville", "group", "service", "services", "bank", "banque", "suisse", "management", "(switzerland)", "company", "sas"
    ];

    /**
     * Should the company name be split on '-'
     *
     * @var boolean
     */
    public $shouldSplit;

    /**
     * The company's full name
     *
     * @var string
     */
    public $FullName;

    /**
     * The company's name, split on "-"
     *
     * @var array[string]
     */
    public $SplitUnfilteredName;

    /**
     * The company's name, split on "-", and with common and short words filtered out
     *
     * @var array[string]
     */
    public $SplitFilteredName;

    /**
     * Creates an EPotentialURL with the specified name, then splits and filters the name, and puts it in $SplitFilteredName
     *
     * @param int $id
     * @param string $name
     * @param boolean $shouldSplit
     */
    public function __construct($id, $name, $shouldSplit = true)
    {
        $this->id = $id;
        $this->shouldSplit = $shouldSplit;
        $this->FullName = $name;
        $this->SplitUnfilteredName = ($this->shouldSplit) ? explode('-', $name) : $name;
        $this->SplitFilteredName = [];
        $this->SplitFilteredName = ($this->shouldSplit) ? EPotentialURL::filterNames($this->SplitUnfilteredName) : $name;
        if ($shouldSplit)
            usort($this->SplitFilteredName, 'sortByLength');
    }

    /**
     * Checks whether a value satisfies two conditions : Length >= MIN_WORD_LENGTH, and val not in the list of common words
     *
     * @param string $val
     * @return boolean
     */
    private static function SatisfyConditions($val)
    {
        return strlen($val) >= EPotentialURL::MIN_WORD_LENGTH && !in_array(strtolower($val), EPotentialURL::$COMMON_WORDS);
    }


    /**
     * Filters $FullName based on MIN_WORD_LENGTH and the list of common words $COMMON_WORDS. Fills $SplitFilteredName with the results
     *
     * @return void
     */
    public static function filterNames($unfilteredNames, $isSplit = true)
    {
        if (!$isSplit) {
            $tmp = [];
            foreach ($unfilteredNames as $w) {
                foreach (explode('-', $w["word"]) as $val) {
                    if (EPotentialURL::SatisfyConditions($val))
                        array_push($tmp, [$w["word"], strtolower($val), $w["id"]]);
                }
            }
            $unfilteredNames = $tmp;
        }
        $result = [];
        foreach ($unfilteredNames as $word) {
            if (is_array($word))
                $tmpWord = $word[1];
            else
                $tmpWord = $word;
            if (EPotentialURL::SatisfyConditions($tmpWord)) {
                $correctWord = (is_array($word)) ? [strtolower($word[1]), strtolower($word[0]), $word[2]] : strtolower($tmpWord);
                array_push($result, $correctWord);
            }
        }
        return $result;
    }
}

/**
 * Compare the length of two strings
 * Returns length(a) - length(b)
 *
 * @param string $a
 * @param string $b
 * @return int
 */
function sortByLength($a, $b)
{
    return strlen($b) - strlen($a);
}
