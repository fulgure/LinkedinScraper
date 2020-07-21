<?php
/**
 * Inserts a contact in the DB
 */
header('Content-type: application/json');
header('Access-Control-Allow-Origin: *');
require_once "..\\includeAll.php";
$result = [];

if (!isset($_POST[AJAX_ARGS::JSON])) {
    $result["message"] = "MISSING PARAM : " . AJAX_ARGS::JSON;
    $result["code"] = 0;
    echo json_encode($result);
    die();
}
$employees = json_decode($_POST[AJAX_ARGS::JSON], true);
if ($employees == null) {
    echo json_last_error_msg();
    die();
}

foreach ($employees as $emp) {
    EDatabase::beginTransaction();
    if (AddEmployeeInfoToDB($emp))
        EDatabase::commit();
    else
        EDatabase::rollBack();
}

/**
 * Adds an employee to the database
 *
 * @param array $employee
 * @return bool Returns true if all requests executed successfully
 */
function AddEmployeeInfoToDB($employee)
{
    if(!AddSkillsToDB($employee[AJAX_ARGS::SKILLS]))
        return false;

    foreach ($employee[AJAX_ARGS::SKILLS] as $skill) {
        if (!AddSkillToEmployee($employee[AJAX_ARGS::CONTACT_ID], $skill))
            return false;
    }
    /**
     * The first experience will always be the current one, because of how the jobs are scraped
     */
    $isCurrentJob = true;
    foreach ($employee[AJAX_ARGS::EXPERIENCES] as $exp) {
        $compID = AddCompanyAndReturnID($exp[AJAX_ARGS::EXP_COMPANY][AJAX_ARGS::COMPANY_URL]);
        if ($compID == false)
            return false;

        $currExperience = array_shift($employee[AJAX_ARGS::EXPERIENCES]);
        $timeArray = $currExperience[AJAX_ARGS::EXP_TIME_FRAME];
        $timeFrame = new ETimeFrame($timeArray[AJAX_ARGS::TIME_START], $timeArray[AJAX_ARGS::TIME_END], $timeArray[AJAX_ARGS::TIME_LENGTH]);
        $exp = new EExperience($compID, $employee[AJAX_ARGS::CONTACT_ID], $timeFrame, $currExperience[AJAX_ARGS::EXP_TITLE],
                                $currExperience[AJAX_ARGS::EXP_REGION], $currExperience[AJAX_ARGS::EXP_DESC], $isCurrentJob);

        if (!AddExperienceToDB($exp))
            return false;

        /* The first job in the array is the only current one */
        $isCurrentJob = false;
    }
    if (!AddDescToEmployee($employee[AJAX_ARGS::CONTACT_ID], $employee[AJAX_ARGS::DESCRIPTION]))
        return false;
    if (!SetEmployeeUpdated($employee[AJAX_ARGS::CONTACT_ID]))
        return false;
    return true;
}

/**
 * Adds a description to an employee
 *
 * @param int $empID The employee's ID
 * @param string $desc The employee's description
 * @return bool Was the query successful
 */
function AddDescToEmployee($empID, $desc)
{
    $sql = "UPDATE contact SET description = :d WHERE id = :id";
    return EDatabase::prepare($sql)->execute(["d" => $desc, "id" => $empID]);
}

/**
 * Sets the updated column of an employee in DB to true
 *
 * @param int $empID The employee's id
 * @return bool Was the query successful
 */
function SetEmployeeUpdated($empID)
{
    $sql = "UPDATE contact SET updated = TRUE WHERE id = :id";
    return EDatabase::prepare($sql)->execute(["id" => $empID]);
}

/**
 * Adds an experience (job) to the database
 *
 * @param EExperience The experience
 * @return bool Was the query successful
 */
function AddExperienceToDB($exp)
{
    if ($exp->isCurrent !== true)
        $exp->isCurrent = intval(0);
    $sql = "INSERT INTO experience(`companyid`, `contactid`, `title`, `region`, `description`, `startdate`, `enddate`, `length`, `iscurrent`) VALUES(:compID, :empID, :title, :region, :desc, :start, :end, :len, :curr)";
    $time = $exp->timeFrame;
    $conn = EDatabase::prepare($sql);
    return $conn->execute([
        "compID" => $exp->companyID,
        "empID" => $exp->contactID,
        "title" => $exp->title,
        "region" => $exp->region,
        "desc" => $exp->description,
        "start" => $time->start,
        "end" => $time->end,
        "len" => $time->length,
        "curr" => $exp->isCurrent
    ]);
}

/**
 * Takes a company's BaseURl (linkedin) and returns its ID
 *
 * @param string $url
 * @return int The company's ID
 */
function GetCompanyIDFromLinkedin($url)
{
    $sql = "SELECT id FROM company WHERE BaseURL = :u";
    $conn = EDatabase::prepare($sql);
    $conn->execute(["u" => $url]);
    try {
        return $conn->fetch(PDO::FETCH_ASSOC)["id"];
    } catch (Exception $ex) {
        return false;
    }
}

/**
 * Adds a skill to an employee
 *
 * @param int $empID The employee's ID
 * @param string $skill The skill
 * @return bool Was the query successful
 */
function AddSkillToEmployee($empID, $skill)
{
    $sql = "INSERT INTO contact_skills(contactid, skillid) SELECT :emp, id from skills WHERE name = :s";
    return EDatabase::prepare($sql)->execute(["emp" => $empID, "s" => $skill]);
}

/**
 * Inserts a company in the databse, and returns its ID
 *
 * @param string $compURL The company's linkedin
 * @return int The company's ID
 */
function AddCompanyAndReturnID($compURL)
{
    if (strpos($compURL, "\/search\/") !== false) {
        $compNamePos = strpos($compURL, '=') + 1;
        $compURL = substr($compURL, $compNamePos);
    }
    $compID = GetCompanyIDFromURL($compURL);
    if ($compID !== false)
        return $compID;
    else{
        if(!InsertCompany($compURL))
            return false;
        
        return EDatabase::lastInsertId();
    }
}

/**
 * Inserts a company in the Database
 *
 * @param string $url The company's linkedin
 * @return bool Was the query successful
 */
function InsertCompany($url)
{
    $sql = "INSERT IGNORE INTO company(BaseURL, isNew) VALUES(:url, TRUE)";
    $conn = EDatabase::prepare($sql);
    return $conn->execute(["url" => $url]);
}

/**
 * Adds an array of skills to the database
 * Creates a new column for each skill
 *
 * @param string[] $skills
 * @return bool Was the query successful
 */
function AddSkillsToDB($skills)
{
    $sql = "INSERT IGNORE INTO `skills`(`name`) VALUES(:s)";
    foreach ($skills as $skill) {
        if(!EDatabase::prepare($sql)->execute(["s" => $skill]))
            return false;
    }
    return true;
}
