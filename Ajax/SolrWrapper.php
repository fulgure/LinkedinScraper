<?php
/**
 * Wrapper around SOLR search, meant to be called quickly through ajax
 */
header('Content-type: application/json');
header('Access-Control-Allow-Origin: *');
require_once "..\\includeAll.php";
$result = [];
if (isset($_GET[AJAX_ARGS::QUERY]))
    $words = FILTER_INPUT(INPUT_GET, AJAX_ARGS::QUERY);

if (isset($words) && strlen($words) >= 3) {
    $query = sprintf(SOLR::QUERY_FORMAT, SOLR::IP, SOLR::PORT, SOLR::CORE_NAME, SOLR::SEARCH_ENDPOINT);
    $query .= urlencode(sprintf("%s:%s~100 AND isnew:0", SOLR::FIELD_URL, $words));
    $queryResult = file_get_contents($query);
    if (strlen($queryResult) > 0) {
        $result["data"] = $queryResult;
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
