<?php
header('Content-type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

require_once "includeAll.php";
$pageNum = 0;
if(isset($_GET["page"]))
    $pageNum = filter_input(INPUT_GET, "page");

$companies = GetInfoFromAllCompanies($pageNum);
echo json_encode($companies);