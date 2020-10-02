<?php
/**
 * Wrapper around SOLR search, meant to be called quickly through ajax
 */
header('Content-type: application/json');
header('Access-Control-Allow-Origin: *');
require_once "..\\includeAll.php";
$result = [];
if (isset($_GET[AJAX_ARGS::QUERY])){
    $searchTerms = FILTER_INPUT(INPUT_GET, AJAX_ARGS::QUERY);
    $words = SplitCompanyName($searchTerms);
}

if (isset($words) && count($words) > 0) {
    $url = sprintf(SOLR::QUERY_FORMAT, SOLR::IP, SOLR::PORT, SOLR::CORE_NAME, SOLR::SEARCH_ENDPOINT);
    $query = SOLR::FIELD_URL . ":";

    $separator = "~" . SOLR::FUZZY_SEARCH_MAX_DISTANCE . " OR ";
    $query .= implode($separator, $words) . "~50";
    
    //$query .= implode(' ', $words) . '~' . 100 . ' AND ';
    $query .= " AND " . SOLR::FIELD_IS_NEW . ":0";

    $url .= urlencode($query);
    $queryResult = file_get_contents($url);

    if (strlen($queryResult) > 0) {
        $result["data"] = $queryResult;
        $result["query"] = $url;
        $result["terms"] = $words;
        $result["message"] = "Success";
        $result["code"] = 1;
    } else {
        $result["message"] = "Missing argument : " . AJAX_ARGS::QUERY;
        $result["code"] = 0;
    }
} else {
    $result["message"] = "Missing argument : " . AJAX_ARGS::QUERY;
    $result["code"] = 0;
}
echo json_encode($result);

/**
 * Splits a company name by space and removes every word smaller than $minWordLength
 *
 * @param [string] The company's name
 * @param [int] The minimum amount of characters a word must have to be returned
 * @return string[] The company name, split by space
 */
function SplitCompanyName($name, $minWordLength = SOLR::DEFAULT_MINIMUM_WORD_LENGTH) {
    $name = str_replace('-', ' ', $name);
    $parts = explode(' ', $name);
    $result = [];

    foreach ($parts as $part) {
        if(strlen($part) >= $minWordLength)
            array_push($result, $part);
    }
    
    return $result;
}