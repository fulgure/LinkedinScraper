<?php
/**
 * Returns all the contact that have to be scraped
 */
header('Content-type: application/json');
header('Access-Control-Allow-Origin: *');
require_once "..\\includeAll.php";
$limit = CONSTANTS::DEFAULT_LIMIT;
$offset = CONSTANTS::DEFAULT_OFFSET;
$result = [];

if (isset($_GET[AJAX_ARGS::LIMIT]))
    $limit = filter_input(INPUT_GET, AJAX_ARGS::LIMIT);

if (isset($_GET[AJAX_ARGS::OFFSET]))
    $offset = filter_input(INPUT_GET, AJAX_ARGS::OFFSET);

$sql = "SELECT id, linkedin from contact WHERE linkedin like '%linkedin.com/in%' AND updated IS FALSE ORDER BY linkedin ASC LIMIT :l OFFSET :o";
$conn = EDatabase::prepare($sql);
$conn->bindParam(":l", intval($limit), PDO::PARAM_INT);
$conn->bindParam(":o", intval($offset), PDO::PARAM_INT);
$conn->execute();

$data = $conn->fetchAll();
echo json_encode(arrayToUtf8($data));
die();

/**
 * Takes a string array, converts all the strings to UTF-8 and returns it
 *
 * @param string[] $array The array to be re-encoded
 * @return string[] The array with strings as utf-8
 */
function arrayToUtf8($array)
{
    array_walk_recursive($array, function (&$item, $key) {
        if (!mb_detect_encoding($item, 'utf-8', true)) {
            $item = utf8_encode($item);
        }
    });

    return $array;
}
