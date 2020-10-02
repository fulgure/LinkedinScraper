<?php

/**
 * Fixes a broken company linkedin.
 * Input : Broken -> CONSTANTS::$Name, Fixed -> CONSTANTS::$FixedName
 * Replaces all instances of [CONSTANTS::$Name] by [CONSTANTS::$FixedName]
 */
header('Content-type: application/json');
header('Access-Control-Allow-Origin: *');
require_once "..\\includeAll.php";
if (isset($_GET[AJAX_ARGS::SHOULD_UNASSIGN]))
    $unassign = (filter_input(INPUT_GET, AJAX_ARGS::SHOULD_UNASSIGN) == "true");
else
    $unassign = false;
    

if (isset($_GET[AJAX_ARGS::BROKEN_ID]))
    $brokenID = filter_input(INPUT_GET, AJAX_ARGS::BROKEN_ID);

if (isset($_GET[AJAX_ARGS::CORRECT_ID]))
    $correctID = filter_input(INPUT_GET, AJAX_ARGS::CORRECT_ID);

if ($unassign && isset($brokenID)) {
    if(UnassignCompany($brokenID)){
        $result["Code"] = 1;
        $result["Message"] = "OK";
    }else{
        $result["Code"] = 0;
        $result["Message"] = "Failed";
    }
} else if (isset($correctID) && isset($brokenID)) {
    if (FixBrokenName($brokenID, $correctID)) {
        $result["Code"] = 1;
        $result["Message"] = "OK";
    } else {
        $result["Code"] = 0;
        $result["Message"] = "Failed";
    }
} else {
    $result["Code"] = 0;
    $result["Message"] = "Missing args";
}

echo json_encode($result);
die();

/**
 * Updates all experiences by replacing every occurence of $brokenID with $correctID
 *
 * @param int $correctID
 * @param int $brokenID
 * @return bool Was the query succesful
 */
function FixBrokenName($brokenID, $correctID)
{
    $sql = "UPDATE experience SET companyid = :correct WHERE companyid = :broken";
    $conn = EDatabase::prepare($sql);
    return $conn->execute(["correct" => $correctID, "broken" => $brokenID]);
}

function UnassignCompany($id){
    $sql = "UPDATE company SET hasnomatch = 1 WHERE id = :id";
    $conn = EDatabase::prepare($sql);
    return $conn->execute(["id" => $id]);
}