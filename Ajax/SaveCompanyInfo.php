<?php
/**
 * Inserts a company in the database
 */
header('Content-type: application/json');
header('Access-Control-Allow-Origin: *');
require_once "..\\includeAll.php";
$result = [];
if (
    isset($_GET['linkedin'])) {
    $linkedin = URLEncodeSpecialChars(filter_input(INPUT_GET, 'linkedin'));
    $website = filter_input(INPUT_GET, 'website');
    $phone = filter_input(INPUT_GET, 'phone');
    $nbEmployees = filter_input(INPUT_GET, 'nbEmployees');
    $industry = filter_input(INPUT_GET, 'industry');
    $desc = filter_input(INPUT_GET, 'desc');
    $type = filter_input(INPUT_GET, 'type');
    $year = filter_input(INPUT_GET, 'year');
    $spec = filter_input(INPUT_GET, 'spec');

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
    }
} else {
    $result["ReturnCode"] = 0;
}
echo json_encode($result);
die();
