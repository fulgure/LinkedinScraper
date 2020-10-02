<?php
/**
 * Inserts a company in the database
 */
header('Content-type: application/json');
header('Access-Control-Allow-Origin: *');
require_once "..\\includeAll.php";
$result = [];
if(isset($_POST['json'])){
    $comp = json_decode(filter_input(INPUT_POST, 'json'));
}
if (isset($comp->linkedin)) {
    $linkedin = URLEncodeSpecialChars($comp->linkedin);
    $website = $comp->website;
    $phone = $comp->phone;
    $nbEmployees = $comp->nbEmployees;
    $industry = $comp->industry;
    $desc = $comp->desc;
    $type = $comp->compType;
    $year = $comp->year;
    $spec = $comp->spec;

    $id = GetCompanyIDFromURL($linkedin);
    if($industry !== "None")
        $industry = ECompany::$industryTranslations[$industry];
    if ($id !== false) {
        $conn = EDatabase::prepare("INSERT INTO 
                            `companyinfo`(`CompanyID`, `Website`, `Phone`, `NbEmployees`, `Industry`, `Description`, `Type`, `FoundedYear`, `Specialties`) 
                            VALUES (:id,:site,:phone,:employees,:industry,:desc,:type,:year,:specialties)");
        $conn->execute([
            "id" => $id,
            "site" => $website,
            "phone" => $phone,
            "employees" => $nbEmployees,
            "industry" => $industry,
            "desc" => $desc,
            "type" => $type,
            "year" => $year,
            "specialties" => $spec,
        ]);
        $result["ReturnCode"] = 1;
    } else {
        $result["ReturnCode"] = 0;
        $result["message"] = "ID is undefined";
    }
} else {
    $result["ReturnCode"] = 0;
    $result["message"] = var_dump($_POST);
}
echo json_encode($result);
die();
