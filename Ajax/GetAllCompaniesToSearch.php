<?php
/**
 * Returns all the companies that have to be scraped
 */
header('Content-type: application/json');
header('Access-Control-Allow-Origin: *');
require_once "..\\includeAll.php";
$conn = EDatabase::prepare("SELECT BaseURL FROM `company` WHERE ID NOT IN (SELECT CompanyID FROM companyinfo) AND BaseURL LIKE '%linkedin%'");
$conn->execute();
if($conn->rowCount() <= 0)
    return false;
$queryResult = $conn->fetchAll(PDO::FETCH_COLUMN);
$result = array();
foreach ($queryResult as $elem) {
    array_push($result, mb_convert_encoding($elem, "UTF-8", "UTF-8"));
}
echo json_encode($result);