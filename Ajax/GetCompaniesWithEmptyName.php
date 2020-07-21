<?php
/**
 * Returns all the companies with no name
 */
header('Content-type: application/json');
header('Access-Control-Allow-Origin: *');
require_once "..\\includeAll.php";
$conn = EDatabase::prepare('SELECT c.BaseURL FROM company as c, `companyinfo` as ci WHERE LENGTH(ci.Name) <= 2 AND c.ID = ci.CompanyID');
$conn->execute();
if($conn->rowCount() <= 0)
    return false;
$queryResult = $conn->fetchAll(PDO::FETCH_COLUMN);
echo json_encode($queryResult);