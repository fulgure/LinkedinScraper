<?php
/**
 * Updates the name of a company
 */
header('Content-type: application/json');
header('Access-Control-Allow-Origin: *');
require_once "..\\includeAll.php";
$result = [];
if (isset($_GET['linkedin'])) {
    $linkedin = URLEncodeSpecialChars(filter_input(INPUT_GET, 'linkedin'));
    $name = urldecode(filter_input(INPUT_GET, 'name'));

    $id = GetCompanyIDFromURL($linkedin);
    $conn = EDatabase::prepare("UPDATE companyinfo SET `Name` = :n WHERE `CompanyID` = :id");
    $conn->execute([
        "id" => $id,
        "n" => $name
    ]);
    $result["ReturnCode"] = 1;
} else {
    $result["ReturnCode"] = 0;
}
echo json_encode($result);
die();
