<?php
header('Content-type: application/json');
header('Access-Control-Allow-Origin: *');
require_once "..\\includeAll.php";

if(isset($_GET[AJAX_ARGS::COMPANY_NAME]))
    $txt = filter_input(INPUT_GET, AJAX_ARGS::COMPANY_NAME);

$result = [];
if(strlen($txt) > 0){
    $sql = "SELECT id, baseurl FROM company WHERE baseurl LIKE CONCAT('%', :val, '%') AND LENGTH(zohoid) > 0 LIMIT 3";
    $conn = EDatabase::prepare($sql);
    $conn->execute(["val" => $txt]);

    $data = $conn->fetchAll();
    $result['code'] = 1;
    $result['message'] = "Success";

    $formattedData = [];
    foreach ($data as $row) {
        array_push($formattedData, [$row['id'], $row['baseurl']]);
    }
    $result['data'] = $formattedData;

}else{
    $result['code'] = 0;
    $result['message'] = "Missing arg : " . AJAX_ARGS::COMPANY_NAME;
}
echo json_encode($result);
die();