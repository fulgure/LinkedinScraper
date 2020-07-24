<?php

/**
 * Récupère l'id de l'entreprise liée à l'url
 *
 * @param [String] $url L'url du profil linkedin de l'entreprise
 * @return String l'ID de l'entreprise
 */
function GetCompanyIDFromURL($url)
{
    $sql = "SELECT `ID` FROM `company` WHERE `BaseURL` = :u";
    $conn = EDatabase::prepare($sql);
    $conn->execute(["u" => $url]);
    if ($conn->rowCount() <= 0)
        return false;
    return $conn->fetch()["ID"];
}

/**
 * URLEncode les caractères spéciaux dans un string
 * Certaines des URL possèdent des caractères accentués, ce qui pose des problèmes d'encodage. Pour régler cela, les caractères accentués ont été URLEncodé
 * Cependant, seuls les caractères accentués ont été encodés, ainsi on ne peut pas juste appeler URLEncode sur tout le string pour matcher une URL entre PHP et MySQL
 *
 * @param [string] $url, l'url à remplacer
 * @return [string] $url, l'url sans caractères accentués
 */
function URLEncodeSpecialChars($url)
{
    static $charsToEncode = [
        "ô", "é", "è", "&", "ü", "–", "â"
    ];
    foreach ($charsToEncode as $letter) {
        if (strpos($url, $letter) !== -1)
            $url = str_replace($letter, urlencode($letter), $url);
    }
    return $url;
}

/**
 * Retourne toutes les entreprises dans la base de données, sous forme d'ECompany
 *
 * @param integer $sheetNum Le numéro de page (Zoho spreadsheet affiche les résultats 100 par 100)
 * @return Array[ECompany]
 */
function GetInfoFromAllCompanies($sheetNum = 0)
{
    /**
	 * La base de donnée du site et celle de Zoho ne correspondent pas à 100%, car des entreprises sont régulièrement ajoutées à Zoho
	 * Pour contrer cela, on affiche 20% de résultats de plus (10% de résultats avant, et 10% après) que les 100 par pages de zoho
	 */
    $offset = ($sheetNum * CONSTANTS::ZOHO_RESULTS_PER_SHEET) * 0.9;
    $limit = CONSTANTS::ZOHO_RESULTS_PER_SHEET * 1.1;
    $sql = "SELECT c.ID, c.BaseURL, ci.Name, ci.`Website`, ci.`Phone`, ci.`NbEmployees`, ci.`Industry`, ci.`Description`, ci.`Type`, ci.`FoundedYear`, ci.`Specialties` FROM `company` as c, `companyinfo` as ci WHERE c.ID = ci.`CompanyID` ORDER BY c.BaseURL asc";
    $sql .= " LIMIT ". $limit ." OFFSET " . $offset;
    $conn = EDatabase::prepare($sql);
    $conn->execute();
    $queryResult = $conn->fetchAll();
    $result = array();
    foreach ($queryResult as $row) {
        $comp = new ECompany($row['ID']);
        $comp->linkedIn = $row['BaseURL'];
        $comp->name = $row['Name'];
        $comp->website = $row['Website'];
        if ($row['Phone'] === 'None')
            $comp->phone = '';
        else {
            $comp->phone = $row['Phone'];
        }
        $comp->nbEmployees = $row['NbEmployees'];
        $comp->industry = $row['Industry'];
        $comp->desc = $row['Description'];
        $comp->type = $row['Type'];
        $comp->year = $row['FoundedYear'];
        $comp->specialties = $row['Specialties'];
        array_push($result, $comp);
    }
    return $result;
}



/**
 * Returns the closest matches from companies with a linkedin
 *
 * @param EPotentialURL $comp
 * @return array[string] The 3 closest matches
 */
function GetClosestMatchingCompanies($comp)
{
	$sql = "SELECT id, BaseURL FROM Company";
	$condition = " WHERE isNew = 0 AND ";
	$limit = " LIMIT 3";
	$conditions = [];
	foreach ($comp->SplitFilteredName as $word) {
		array_push($conditions, "BaseURL LIKE '%" . $word . "%'");
	}
	for ($i = 0; $i < count($conditions); $i++) {
		if ($i > 0)
			$condition .= " OR ";
		$condition .= $conditions[$i];
	}
	$fullQuery = $sql . $condition . $limit;

	$conn = EDatabase::prepare($fullQuery);
	$conn->execute();
	return $conn->fetchAll();
}

/**
 * Returns the strings that match the closest with the EPotentialURL
 *
 * @param EPotentialURL $comp
 * @param array[string] $list
 * @return array[[int, string]]
 */
function GetClosestMatchingCompaniesFromList($comp, $list)
{
	$result = [];
	$matchPercentage = 0;
	if ($comp->shouldSplit) {
		foreach ($comp->SplitFilteredName as $word) {
			foreach ($list as $globalWord) {
				if (is_array($globalWord))
					$w = $globalWord[1];
				else
					$w = $globalWord;
                $diff = similar_text($word, $w, $matchPercentage);
				array_push($result, ["perc" => $matchPercentage, "fixedName" => $globalWord, "badName" => [$word, $comp->FullName, $comp->id]]);
			}
		}
	} else {
		foreach ($list as $word) {
			$diff = similar_text($comp->FullName, $word, $p);
			array_push($result, [$matchPercentage, $word, $comp->FullName]);
		}
	}
	usort($result, "SortByDiffInArrayTuple");
	if (count($result) > 0)
		return array_slice($result, CONSTANTS::NB_FIRST_MATCH_TO_SKIP, CONSTANTS::NB_MATCH_TO_RETURN);
	else
		return null;
}
/**
 * Compares values in two Tuples(int,string)
 * Returns 1    if a[0] > b[0]
 * Returns 0    if a[0] = b[0]
 * Returns -1   if a[0] < b[0]
 *
 * @param array[int,string] $a
 * @param array[int,string] $b
 * @return int
 */
function SortByDiffInArrayTuple($a, $b)
{
	if ($a["perc"] == $b["perc"])
		return 0;
	return ($a["perc"] < $b["perc"]) ? 1 : -1;
}

/**
 * Returns the name of all the companies that are matched to zoho
 *
 * @param boolean $shouldSplit
 * @return string The name of all the companies
 */
function GetAllNamesFromCorrectCompanies($shouldSplit = true)
{
	$sql = "SELECT id, BaseURL FROM company WHERE isNew = 0 AND id <> ".CONSTANTS::UNASSIGNED_COMPANY_ID." AND id <> ".CONSTANTS::UNEMPLOYED_COMPANY_ID;
	$conn = EDatabase::prepare($sql);
	$conn->execute();
	$queryResult = $conn->fetchAll();
	$result = [];
	for ($i = 0; $i < count($queryResult); $i++) {
		$result[$i]["id"] = $queryResult[$i]["id"];
		$result[$i]["word"] = explode('/', $queryResult[$i]["BaseURL"])[4];
	}
	return ($shouldSplit) ? EPotentialURL::filterNames($result, false) : $result;
}

/**
 * Returns the URL of all the companies who aren't matched with a valid linkedin
 *
 * @return string[] Companies
 */
function GetAllNonMatchedCompanies()
{
	$sql = "SELECT id, BaseURL FROM " . CONSTANTS::NON_MATCHED_VIEW_NAME . " WHERE id <> ".CONSTANTS::UNASSIGNED_COMPANY_ID;
	$sql .= " AND id <> ".CONSTANTS::UNEMPLOYED_COMPANY_ID." AND hasnomatch = 0";
	$conn = EDatabase::prepare($sql);
	$conn->execute();
	$results = $conn->fetchAll();
	$result = [];
	foreach ($results as $url) {
		if(strpos($url["BaseURL"], "=") !== false)
			$u = explode("=", $url["BaseURL"])[1];
		else
			$u = explode("/", $url["BaseURL"])[4];

		array_push($result, ["id" => $url["id"], "url" => $u]);
	}
	return $result;
}

