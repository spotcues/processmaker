<?php
use ProcessMaker\Model\Delegation;

/**
 * Authentication check for session. If not logged in, return json error
 */
if (!isset($_SESSION['USER_LOGGED'])) {
    $responseObject = new stdclass();
    $responseObject->error = G::LoadTranslation('ID_LOGIN_AGAIN');
    $responseObject->success = true;
    $responseObject->lostSession = true;
    print G::json_encode($responseObject);
    die();
}


/**
 * Do input filtering, although filtering should be done on the frontend rendering, not here
 */
$filter = new InputFilter();
$_GET = $filter->xssFilterHard($_GET);
$_REQUEST = $filter->xssFilterHard($_REQUEST);
$_SESSION['USER_LOGGED'] = $filter->xssFilterHard($_SESSION['USER_LOGGED']);

// Callback in the UI to utilize
$callback = isset($_REQUEST["callback"]) ? $_REQUEST["callback"] : "stcCallback1001";

// Sort column
$sort = isset($_REQUEST["sort"]) ? $_REQUEST["sort"] : "APP_NUMBER";
// Sort direction
$dir = isset($_REQUEST["dir"]) ? $_REQUEST["dir"] : "DESC";

// Pagination control
$start = !empty($_REQUEST["start"]) ? $_REQUEST["start"] : 0;
$limit = !empty($_REQUEST["limit"]) ? $_REQUEST["limit"] : 25;

// Our search filter
$filter = isset($_REQUEST["filter"]) ? $_REQUEST["filter"] : "";

// What process
$process = isset($_REQUEST["process"]) ? $_REQUEST["process"] : "";

// What category
$category = isset($_REQUEST["category"]) ? $_REQUEST["category"] : "";

// What status
$status = isset($_REQUEST["status"]) ? strtoupper($_REQUEST["status"]) : "";
$filterStatus = isset($_REQUEST["filterStatus"]) ? strtoupper($_REQUEST["filterStatus"]) : "";

// What user
$user = isset($_REQUEST["user"]) ? $_REQUEST["user"] : "";

// What keywords to search
$search = isset($_REQUEST["search"]) ? $_REQUEST["search"] : "";

// What kind of action
$action = isset($_GET["action"]) ? $_GET["action"] : (isset($_REQUEST["action"]) ? $_REQUEST["action"] : "todo");

// What kind of search
$type = isset($_GET["type"]) ? $_GET["type"] : (isset($_REQUEST["type"]) ? $_REQUEST["type"] : "extjs");

// Date ranges
$dateFrom = isset($_REQUEST["dateFrom"]) ? substr($_REQUEST["dateFrom"], 0, 10) : "";
$dateTo = isset($_REQUEST["dateTo"]) ? substr($_REQUEST["dateTo"], 0, 10) : "";

// First? No idea
$first = isset($_REQUEST["first"]) ? true : false;


$openApplicationUid = (isset($_REQUEST['openApplicationUid']) && $_REQUEST['openApplicationUid'] != '') ?
    $_REQUEST['openApplicationUid'] : null;
$search = (!is_null($openApplicationUid)) ? $openApplicationUid : $search;
$columnSearch = isset($_REQUEST["columnSearch"]) ? strtoupper($_REQUEST["columnSearch"]) : "";

if ($sort == 'CASE_SUMMARY' || $sort == 'CASE_NOTES_COUNT') {
    $sort = 'APP_NUMBER';//DEFAULT VALUE
}
if ($sort == 'APP_STATUS_LABEL') {
    $sort = 'APP_STATUS';
}

try {
    $userUid = (isset($_SESSION["USER_LOGGED"]) && $_SESSION["USER_LOGGED"] != "") ? $_SESSION["USER_LOGGED"] : null;
    $result = [];

    switch ($action) {
        case "search":
        case "to_reassign":
            if ($first) {
                $result['totalCount'] = 0;
                $result['data'] = array();
                $result = G::json_encode($result);
                echo $result;
                return;
            }
            $user = ($user == "CURRENT_USER") ? $userUid : $user;
            $userUid = $user;
            break;
        default:
            break;
    }

    if ($action == 'search') {
        $data = Delegation::search(
            $userUid,
            $start,
            $limit,
            $search,
            $process,
            $filterStatus,
            $dir,
            $sort,
            $category,
            $dateFrom,
            $dateTo,
            $columnSearch
        );
    } else {
        $data = Delegation::search(
            $userUid,
            $start,
            $limit,
            $action,
            $filter,
            $search,
            $process,
            $filterStatus,
            $type,
            $dateFrom,
            $dateTo,
            $callback,
            $dir,
            (strpos($sort, ".") !== false) ? $sort : "APP_CACHE_VIEW." . $sort,
            $category
        );
    }

    $data['data'] = \ProcessMaker\Util\DateTime::convertUtcToTimeZone($data['data']);
    $result = G::json_encode($data);
    echo $result;
} catch (Exception $e) {
    $msg = array("error" => $e->getMessage());
    echo G::json_encode($msg);
}
