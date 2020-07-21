<?php
require_once "includeAll.php";
$dupes = GetAllDuplicateCompanies();
$cnt = 0;
foreach ($dupes as $dupe) {
    if(!UpdateDupesToCorrectID($dupe["BaseURL"]))
        print "error\n";
    else
        $cnt++;
}
print $cnt;



function GetAllDuplicateCompanies(){
    $sql = "SELECT * FROM viewdupes";
    $conn = EDatabase::prepare($sql);
    $conn->execute();
    return $conn->fetchAll();
}

function UpdateDupesToCorrectID($url){
    $url = "%" . explode('/', $url)[4] . "/%";
    $newID = GetOriginalCompanyIDFromURL($url);
    if($newID === null)
        return false;
    $sql = "UPDATE experience SET companyid = :nID WHERE companyid IN (SELECT id from company WHERE BaseURL LIKE :u AND isNew = 1)";
    $conn = EDatabase::prepare($sql);
    return $conn->execute(["nID" => $newID, "u" => $url]);
}

function GetOriginalCompanyIDFromURL($url){
    $sql = "SELECT id FROM company WHERE BaseURL LIKE :u AND isNew = 0";
    $conn = EDatabase::prepare($sql);
    $conn->execute(["u" => $url]);
    return $conn->fetch()[0];
}

function cleanData(){
    $sql = "SELECT id, BaseURL FROM company WHERE isNew = 0";
    $conn = EDatabase::prepare($sql);
    $conn->execute();
}
