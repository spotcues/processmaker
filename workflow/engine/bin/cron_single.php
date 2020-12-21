<?php

/**
 * cron_single.php
 *
 * @see workflow/engine/bin/cron.php
 * @see workflow/engine/bin/messageeventcron.php
 * @see workflow/engine/bin/timereventcron.php
 * @see workflow/engine/bin/ldapcron.php
 * @see workflow/engine/bin/sendnotificationscron.php
 * @see workflow/engine/methods/setup/cron.php
 * 
 * @link https://wiki.processmaker.com/3.2/Executing_cron.php
 */

use Illuminate\Foundation\Http\Kernel;

require_once __DIR__ . '/../../../gulliver/system/class.g.php';
require_once __DIR__ . '/../../../bootstrap/autoload.php';
require_once __DIR__ . '/../../../bootstrap/app.php';

use ProcessMaker\Core\JobsManager;
use ProcessMaker\Core\System;
use ProcessMaker\Plugins\PluginRegistry;

register_shutdown_function(function () {
    if (class_exists("Propel")) {
        Propel::close();
    }
});

try {
    //Verify data
    if (count($argv) < 7) {
        throw new Exception('Error: Invalid number of arguments');
    }

    for ($i = 1; $i <= 3; $i++) {
        $argv[$i] = base64_decode($argv[$i]);

        if (!is_dir($argv[$i])) {
            throw new Exception('Error: The path "' . $argv[$i] . '" is invalid');
        }
    }

    //Set variables
    $osIsLinux = strtoupper(substr(PHP_OS, 0, 3)) != 'WIN';

    $pathHome = $argv[1];
    $pathTrunk = $argv[2];
    $pathOutTrunk = $argv[3];
    $cronName = $argv[4];
    $workspace = $argv[5];
    $now = $argv[6]; //date
    //Defines constants
    define('PATH_SEP', ($osIsLinux) ? '/' : '\\');

    define('PATH_HOME', $pathHome);
    define('PATH_TRUNK', $pathTrunk);
    define('PATH_OUTTRUNK', $pathOutTrunk);

    define('PATH_CLASSES', PATH_HOME . 'engine' . PATH_SEP . 'classes' . PATH_SEP);

    define('SYS_LANG', 'en');

    require_once(PATH_HOME . 'engine' . PATH_SEP . 'config' . PATH_SEP . 'paths.php');
    require_once(PATH_TRUNK . 'framework' . PATH_SEP . 'src' . PATH_SEP . 'Maveriks' . PATH_SEP . 'Util' . PATH_SEP . 'ClassLoader.php');

    // Class Loader - /ProcessMaker/BusinessModel
    $classLoader = \Maveriks\Util\ClassLoader::getInstance();
    $classLoader->add(PATH_TRUNK . 'framework' . PATH_SEP . 'src' . PATH_SEP, 'Maveriks');
    $classLoader->add(PATH_TRUNK . 'workflow' . PATH_SEP . 'engine' . PATH_SEP . 'src' . PATH_SEP, 'ProcessMaker');
    $classLoader->add(PATH_TRUNK . 'workflow' . PATH_SEP . 'engine' . PATH_SEP . 'src' . PATH_SEP);

    // Add vendors to autoloader
    $classLoader->addClass('Bootstrap', PATH_TRUNK . 'gulliver' . PATH_SEP . 'system' . PATH_SEP . 'class.bootstrap.php');
    $classLoader->addModelClassPath(PATH_TRUNK . 'workflow' . PATH_SEP . 'engine' . PATH_SEP . 'classes' . PATH_SEP . 'model' . PATH_SEP);

    // Get the configurations related to the workspace
    $arraySystemConfiguration = System::getSystemConfiguration('', '', $workspace);

    // Define the debug value
    $e_all = (defined('E_DEPRECATED')) ? E_ALL & ~E_DEPRECATED : E_ALL;
    $e_all = (defined('E_STRICT')) ? $e_all & ~E_STRICT : $e_all;
    $e_all = ($arraySystemConfiguration['debug']) ? $e_all : $e_all & ~E_NOTICE;

    // In community version the default value is 0
    $_SESSION['__SYSTEM_UTC_TIME_ZONE__'] = (int)($arraySystemConfiguration['system_utc_time_zone']) == 1;

    app()->useStoragePath(realpath(PATH_DATA));
    app()->make(Kernel::class)->bootstrap();
    restore_error_handler();

    // Do not change any of these settings directly, use env.ini instead
    ini_set('display_errors', $arraySystemConfiguration['debug']);
    ini_set('error_reporting', $e_all);
    ini_set('short_open_tag', 'On');
    ini_set('default_charset', 'UTF-8');
    ini_set('soap.wsdl_cache_enabled', $arraySystemConfiguration['wsdl_cache']);
    ini_set('date.timezone', $_SESSION['__SYSTEM_UTC_TIME_ZONE__'] ? 'UTC' : $arraySystemConfiguration['time_zone']);

    define('DEBUG_SQL_LOG', $arraySystemConfiguration['debug_sql']);
    define('DEBUG_TIME_LOG', $arraySystemConfiguration['debug_time']);
    define('DEBUG_CALENDAR_LOG', $arraySystemConfiguration['debug_calendar']);
    define('MEMCACHED_ENABLED', $arraySystemConfiguration['memcached']);
    define('MEMCACHED_SERVER', $arraySystemConfiguration['memcached_server']);
    define('TIME_ZONE', ini_get('date.timezone'));

    date_default_timezone_set(TIME_ZONE);

    config(['app.timezone' => TIME_ZONE]);

    spl_autoload_register(['Bootstrap', 'autoloadClass']);

    //Set variables

    $argvx = '';

    for ($i = 7; $i <= count($argv) - 1; $i++) {
            $argvx = $argvx . (($argvx != '') ? ' ' : '') . $argv[$i];
    }
    global $sObject;
    $sObject = $workspace;

    //Workflow
    saveLog('main', 'action', 'checking folder ' . PATH_DB . $workspace);

    if (is_dir(PATH_DB . $workspace) && file_exists(PATH_DB . $workspace . PATH_SEP . 'db.php')) {
        define('SYS_SYS', $workspace);
        config(["system.workspace" => $workspace]);

        include_once(PATH_HOME . 'engine' . PATH_SEP . 'config' . PATH_SEP . 'paths_installed.php');
        include_once(PATH_HOME . 'engine' . PATH_SEP . 'config' . PATH_SEP . 'paths.php');

        //PM Paths DATA
        define('PATH_DATA_SITE', PATH_DATA . 'sites/' . config("system.workspace") . '/');
        define('PATH_DOCUMENT', PATH_DATA_SITE . 'files/');
        define('PATH_DATA_MAILTEMPLATES', PATH_DATA_SITE . 'mailTemplates/');
        define('PATH_DATA_PUBLIC', PATH_DATA_SITE . 'public/');
        define('PATH_DATA_REPORTS', PATH_DATA_SITE . 'reports/');
        define('PATH_DYNAFORM', PATH_DATA_SITE . 'xmlForms/');
        define('PATH_IMAGES_ENVIRONMENT_FILES', PATH_DATA_SITE . 'usersFiles' . PATH_SEP);
        define('PATH_IMAGES_ENVIRONMENT_USERS', PATH_DATA_SITE . 'usersPhotographies' . PATH_SEP);

        if (is_file(PATH_DATA_SITE . PATH_SEP . '.server_info')) {
            $SERVER_INFO = file_get_contents(PATH_DATA_SITE . PATH_SEP . '.server_info');
            $SERVER_INFO = unserialize($SERVER_INFO);

            define('SERVER_NAME', $SERVER_INFO['SERVER_NAME']);
            define('SERVER_PORT', $SERVER_INFO['SERVER_PORT']);
            //to do improvement G::is_https()
            if ((isset($SERVER_INFO['HTTPS']) && $SERVER_INFO['HTTPS'] == 'on') ||
                    (isset($SERVER_INFO['HTTP_X_FORWARDED_PROTO']) && $SERVER_INFO['HTTP_X_FORWARDED_PROTO'] == 'https')) {
                define('REQUEST_SCHEME', 'https');
            } else {
                define('REQUEST_SCHEME', $SERVER_INFO['REQUEST_SCHEME']);
            }
        } else {
            eprintln('WARNING! No server info found!', 'red');
        }
        //load Processmaker translations
        Bootstrap::LoadTranslationObject(SYS_LANG);
        //DB
        $phpCode = '';

        $fileDb = fopen(PATH_DB . $workspace . PATH_SEP . 'db.php', 'r');

        if ($fileDb) {
            while (!feof($fileDb)) {
                $buffer = fgets($fileDb, 4096); //Read a line

                $phpCode .= preg_replace('/define\s*\(\s*[\x22\x27](.*)[\x22\x27]\s*,\s*(\x22.*\x22|\x27.*\x27)\s*\)\s*;/i', '$$1 = $2;', $buffer);
            }

            fclose($fileDb);
        }

        $phpCode = str_replace(['<?php', '<?', '?>'], ['', '', ''], $phpCode);

        eval($phpCode);

        $dsn = $DB_ADAPTER . '://' . $DB_USER . ':' . $DB_PASS . '@' . $DB_HOST . '/' . $DB_NAME;
        $dsnRbac = $DB_ADAPTER . '://' . $DB_RBAC_USER . ':' . $DB_RBAC_PASS . '@' . $DB_RBAC_HOST . '/' . $DB_RBAC_NAME;
        $dsnRp = $DB_ADAPTER . '://' . $DB_REPORT_USER . ':' . $DB_REPORT_PASS . '@' . $DB_REPORT_HOST . '/' . $DB_REPORT_NAME;

        switch ($DB_ADAPTER) {
            case 'mysql':
                $dsn .= '?encoding=utf8';
                $dsnRbac .= '?encoding=utf8';
                break;
            case 'mssql':
                break;
            default:
                break;
        }

        $pro = [];
        $pro['datasources']['workflow']['connection'] = $dsn;
        $pro['datasources']['workflow']['adapter'] = $DB_ADAPTER;
        $pro['datasources']['rbac']['connection'] = $dsnRbac;
        $pro['datasources']['rbac']['adapter'] = $DB_ADAPTER;
        $pro['datasources']['rp']['connection'] = $dsnRp;
        $pro['datasources']['rp']['adapter'] = $DB_ADAPTER;

        $oFile = fopen(PATH_CORE . 'config' . PATH_SEP . '_databases_.php', 'w');
        fwrite($oFile, '<?php global $pro; return $pro; ?>');
        fclose($oFile);

        Propel::init(PATH_CORE . 'config' . PATH_SEP . '_databases_.php');

        /**
         * Load Laravel database connection
         */
        $dbHost = explode(':', $DB_HOST);
        config(['database.connections.workflow.host' => $dbHost[0]]);
        config(['database.connections.workflow.database' => $DB_NAME]);
        config(['database.connections.workflow.username' => $DB_USER]);
        config(['database.connections.workflow.password' => $DB_PASS]);
        if (count($dbHost) > 1) {
            config(['database.connections.workflow.port' => $dbHost[1]]);
        }

        //Enable RBAC, We need to keep both variables in upper and lower case.
        $rbac = $RBAC = RBAC::getSingleton(PATH_DATA, session_id());
        $rbac->sSystem = 'PROCESSMAKER';

        if (!defined('DB_ADAPTER')) {
            define('DB_ADAPTER', $DB_ADAPTER);
        }
        if (!defined('DB_HOST')) {
            define('DB_HOST', $DB_HOST);
        }
        if (!defined('DB_NAME')) {
            define('DB_NAME', $DB_NAME);
        }
        if (!defined('DB_USER')) {
            define('DB_USER', $DB_USER);
        }
        if (!defined('DB_PASS')) {
            define('DB_PASS', $DB_PASS);
        }
        if (!defined('SYS_SKIN')) {
            define('SYS_SKIN', $arraySystemConfiguration['default_skin']);
        }

        $dateSystem = date('Y-m-d H:i:s');
        if (empty($now)) {
            $now = $dateSystem;
        }

        //Processing
        eprintln('Processing workspace: ' . $workspace, 'green');
        
        /**
         * JobsManager
         */
        JobsManager::getSingleton()->init();

        // We load plugins' pmFunctions
        $oPluginRegistry = PluginRegistry::loadSingleton();
        $oPluginRegistry->init();

        try {
            switch ($cronName) {
                case 'cron':
                    processWorkspace();
                    break;
                case 'ldapcron':
                    require_once(PATH_HOME . 'engine' . PATH_SEP . 'methods' . PATH_SEP . 'services' . PATH_SEP . 'ldapadvanced.php');

                    $ldapadvancedClassCron = new ldapadvancedClassCron();

                    $ldapadvancedClassCron->executeCron(in_array('+debug', $argv));
                    break;
                case 'messageeventcron':
                    $messageApplication = new \ProcessMaker\BusinessModel\MessageApplication();

                    $messageApplication->catchMessageEvent(true);
                    break;
                case 'timereventcron':
                    $timerEvent = new \ProcessMaker\BusinessModel\TimerEvent();

                    $timerEvent->startContinueCaseByTimerEvent($now, true);
                    break;
                case 'sendnotificationscron':
                    sendNotifications();
                    break;
            }
        } catch (Exception $e) {
            $token = strtotime("now");
            PMException::registerErrorLog($e, $token);
            G::outRes(G::LoadTranslation("ID_EXCEPTION_LOG_INTERFAZ", array($token)) . "\n");

            eprintln('Problem in workspace: ' . $workspace . ' it was omitted.', 'red');
        }

        eprintln();
    }

    if (file_exists(PATH_CORE . 'config' . PATH_SEP . '_databases_.php')) {
        unlink(PATH_CORE . 'config' . PATH_SEP . '_databases_.php');
    }
} catch (Exception $e) {
    $token = strtotime("now");
    PMException::registerErrorLog($e, $token);
    G::outRes(G::LoadTranslation("ID_EXCEPTION_LOG_INTERFAZ", array($token)) . "\n");
}

//Functions
function processWorkspace()
{
    try {
        global $sObject;
        global $sLastExecution;

        resendEmails();
        unpauseApplications();
        calculateDuration();
        executeEvents($sLastExecution);
        executeScheduledCases();
        executeUpdateAppTitle();
        executeCaseSelfService();
        cleanSelfServiceTables();
        executePlugins();
    } catch (Exception $oError) {
        saveLog("main", "error", "Error processing workspace : " . $oError->getMessage() . "\n");
    }
}

function resendEmails()
{
    global $argvx;
    global $now;
    global $dateSystem;

    if ($argvx != "" && strpos($argvx, "emails") === false) {
        return false;
    }

    setExecutionMessage("Resending emails");

    try {
        $dateResend = $now;

        if ($now == $dateSystem) {
            $arrayDateSystem = getdate(strtotime($dateSystem));

            $mktDateSystem = mktime(
                $arrayDateSystem["hours"],
                $arrayDateSystem["minutes"],
                $arrayDateSystem["seconds"],
                $arrayDateSystem["mon"],
                $arrayDateSystem["mday"],
                $arrayDateSystem["year"]
            );

            $dateResend = date("Y-m-d H:i:s", $mktDateSystem - (7 * 24 * 60 * 60));
        }

        $oSpool = new SpoolRun();
        $oSpool->resendEmails($dateResend, 1);

        saveLog("resendEmails", "action", "Resending Emails", "c");

        $aSpoolWarnings = $oSpool->getWarnings();

        if ($aSpoolWarnings !== false) {
            foreach ($aSpoolWarnings as $sWarning) {
                print("MAIL SPOOL WARNING: " . $sWarning . "\n");
                saveLog("resendEmails", "warning", "MAIL SPOOL WARNING: " . $sWarning);
            }
        }

        setExecutionResultMessage("DONE");
    } catch (Exception $e) {
        $c = new Criteria("workflow");
        $c->clearSelectColumns();
        $c->addSelectColumn(ConfigurationPeer::CFG_UID);
        $c->add(ConfigurationPeer::CFG_UID, "Emails");
        $result = ConfigurationPeer::doSelectRS($c);
        $result->setFetchmode(ResultSet::FETCHMODE_ASSOC);
        if ($result->next()) {
            setExecutionResultMessage("WARNING", "warning");
            $message = "Emails won't be sent, but the cron will continue its execution";
            eprintln("  '-" . $message, "yellow");
        } else {
            setExecutionResultMessage("WITH ERRORS", "error");
            eprintln("  '-" . $e->getMessage(), "red");
        }

        saveLog("resendEmails", "error", "Error Resending Emails: " . $e->getMessage());
    }
}

function unpauseApplications()
{
    global $argvx;
    global $now;

    if ($argvx != "" && strpos($argvx, "unpause") === false) {
        return false;
    }

    setExecutionMessage("Unpausing applications");

    try {
        $oCases = new Cases();
        $oCases->ThrowUnpauseDaemon($now, 1);

        setExecutionResultMessage('DONE');
        saveLog('unpauseApplications', 'action', 'Unpausing Applications');
    } catch (Exception $oError) {
        setExecutionResultMessage('WITH ERRORS', 'error');
        eprintln("  '-" . $oError->getMessage(), 'red');
        saveLog('unpauseApplications', 'error', 'Error Unpausing Applications: ' . $oError->getMessage());
    }
}

function executePlugins()
{
    global $argvx;

    if ($argvx != "" && strpos($argvx, "plugins") === false) {
        return false;
    }

    $pathCronPlugins = PATH_CORE . 'bin' . PATH_SEP . 'plugins' . PATH_SEP;

    // Executing cron files in bin/plugins directory
    if (!is_dir($pathCronPlugins)) {
        return false;
    }

    if ($handle = opendir($pathCronPlugins)) {
        setExecutionMessage('Executing cron files in bin/plugins directory in Workspace: ' . config("system.workspace"));
        while (false !== ($file = readdir($handle))) {
            if (strpos($file, '.php', 1) && is_file($pathCronPlugins . $file)) {
                $filename = str_replace('.php', '', $file);
                $className = $filename . 'ClassCron';

                // Execute custom cron function
                executeCustomCronFunction($pathCronPlugins . $file, $className);
            }
        }
    }

    // Executing registered cron files
    // -> Get registered cron files
    $oPluginRegistry = PluginRegistry::loadSingleton();
    $cronFiles = $oPluginRegistry->getCronFiles();

    // -> Execute functions
    if (!empty($cronFiles)) {
        setExecutionMessage('Executing registered cron files for Workspace: ' . config('system.workspace'));
        /**
         * @var \ProcessMaker\Plugins\Interfaces\CronFile $cronFile
         */
        foreach ($cronFiles as $cronFile) {
            $path = PATH_PLUGINS . $cronFile->getNamespace() . PATH_SEP . 'bin' . PATH_SEP . $cronFile->getCronFile() . '.php';
            if (file_exists($path)) {
                executeCustomCronFunction($path, $cronFile->getCronFile());
            } else {
                setExecutionMessage('File ' . $cronFile->getCronFile() . '.php ' . 'does not exist.');
            }
        }
    }
}

function executeCustomCronFunction($pathFile, $className)
{
    include_once $pathFile;

    $oPlugin = new $className();

    if (method_exists($oPlugin, 'executeCron')) {
        $arrayCron = unserialize(trim(@file_get_contents(PATH_DATA . "cron")));
        $arrayCron["processcTimeProcess"] = 60; //Minutes
        $arrayCron["processcTimeStart"] = time();
        @file_put_contents(PATH_DATA . "cron", serialize($arrayCron));

        //Try to execute Plugin Cron. If there is an error then continue with the next file
        setExecutionMessage("\n--- Executing cron file: $pathFile");
        try {
            $oPlugin->executeCron();
            setExecutionResultMessage('DONE');
        } catch (Exception $e) {
            setExecutionResultMessage('FAILED', 'error');
            eprintln("  '-" . $e->getMessage(), 'red');
            saveLog('executePlugins', 'error', 'Error executing cron file: ' . $pathFile . ' - ' . $e->getMessage());
        }
    }
}

function calculateDuration()
{
    global $argvx;

    if ($argvx != "" && strpos($argvx, "calculate") === false) {
        return false;
    }

    setExecutionMessage("Calculating Duration");

    try {
        $oAppDelegation = new AppDelegation();
        $oAppDelegation->calculateDuration(1);

        setExecutionResultMessage('DONE');
        saveLog('calculateDuration', 'action', 'Calculating Duration');
    } catch (Exception $oError) {
        setExecutionResultMessage('WITH ERRORS', 'error');
        eprintln("  '-" . $oError->getMessage(), 'red');
        saveLog('calculateDuration', 'error', 'Error Calculating Duration: ' . $oError->getMessage());
    }
}

function executeEvents($sLastExecution, $now = null)
{
    global $argvx;
    global $now;

    $log = array();

    if ($argvx != "" && strpos($argvx, "events") === false) {
        return false;
    }

    setExecutionMessage("Executing events");
    setExecutionResultMessage('PROCESSING');

    try {
        $oAppEvent = new AppEvent();
        saveLog('executeEvents', 'action', "Executing Events $sLastExecution, $now ");
        $n = $oAppEvent->executeEvents($now, false, $log, 1);

        foreach ($log as $value) {
            $arrayCron = unserialize(trim(@file_get_contents(PATH_DATA . "cron")));
            $arrayCron["processcTimeStart"] = time();
            @file_put_contents(PATH_DATA . "cron", serialize($arrayCron));

            saveLog('executeEvents', 'action', "Execute Events : $value, $now ");
        }

        setExecutionMessage("|- End Execution events");
        setExecutionResultMessage("Processed $n");
    } catch (Exception $oError) {
        setExecutionResultMessage('WITH ERRORS', 'error');
        eprintln("  '-" . $oError->getMessage(), 'red');
        saveLog('calculateAlertsDueDate', 'Error', 'Error Executing Events: ' . $oError->getMessage());
    }
}

function executeScheduledCases($now = null)
{
    try {
        global $argvx;
        global $now;
        $log = array();

        if ($argvx != "" && strpos($argvx, "scheduler") === false) {
            return false;
        }

        setExecutionMessage("Executing the scheduled starting cases");
        setExecutionResultMessage('PROCESSING');

        $oCaseScheduler = new CaseScheduler();
        $oCaseScheduler->caseSchedulerCron($now, $log, 1);

        foreach ($log as $value) {
            $arrayCron = unserialize(trim(@file_get_contents(PATH_DATA . "cron")));
            $arrayCron["processcTimeStart"] = time();
            @file_put_contents(PATH_DATA . "cron", serialize($arrayCron));

            saveLog('executeScheduledCases', 'action', "OK Case# $value");
        }

        setExecutionResultMessage('DONE');
    } catch (Exception $oError) {
        setExecutionResultMessage('WITH ERRORS', 'error');
        eprintln("  '-" . $oError->getMessage(), 'red');
    }
}

function executeUpdateAppTitle()
{
    try {
        global $argvx;

        if ($argvx != "" && strpos($argvx, "update-case-labels") === false) {
            return false;
        }

        $criteriaConf = new Criteria("workflow");

        $criteriaConf->addSelectColumn(ConfigurationPeer::OBJ_UID);
        $criteriaConf->addSelectColumn(ConfigurationPeer::CFG_VALUE);
        $criteriaConf->add(ConfigurationPeer::CFG_UID, "TAS_APP_TITLE_UPDATE");

        $rsCriteriaConf = ConfigurationPeer::doSelectRS($criteriaConf);
        $rsCriteriaConf->setFetchmode(ResultSet::FETCHMODE_ASSOC);

        setExecutionMessage("Update case labels");
        saveLog("updateCaseLabels", "action", "Update case labels", "c");

        while ($rsCriteriaConf->next()) {
            $row = $rsCriteriaConf->getRow();

            $taskUid = $row["OBJ_UID"];
            $lang = $row["CFG_VALUE"];

            //Update case labels
            $appcv = new AppCacheView();
            $appcv->appTitleByTaskCaseLabelUpdate($taskUid, $lang, 1);

            //Delete record
            $criteria = new Criteria("workflow");

            $criteria->add(ConfigurationPeer::CFG_UID, "TAS_APP_TITLE_UPDATE");
            $criteria->add(ConfigurationPeer::OBJ_UID, $taskUid);
            $criteria->add(ConfigurationPeer::CFG_VALUE, $lang);

            $numRowDeleted = ConfigurationPeer::doDelete($criteria);

            saveLog("updateCaseLabels", "action", "OK Task $taskUid");
        }

        setExecutionResultMessage("DONE");
    } catch (Exception $e) {
        setExecutionResultMessage("WITH ERRORS", "error");
        eprintln("  '-" . $e->getMessage(), "red");
        saveLog("updateCaseLabels", "error", "Error updating case labels: " . $e->getMessage());
    }
}

function executeCaseSelfService()
{
    try {
        global $argvx;

        if ($argvx != "" && strpos($argvx, "unassigned-case") === false) {
            return false;
        }

        $criteria = new Criteria("workflow");

        //SELECT
        $criteria->addSelectColumn(AppCacheViewPeer::APP_UID);
        $criteria->addSelectColumn(AppCacheViewPeer::DEL_INDEX);
        $criteria->addSelectColumn(AppCacheViewPeer::DEL_DELEGATE_DATE);
        $criteria->addSelectColumn(AppCacheViewPeer::APP_NUMBER);
        $criteria->addSelectColumn(AppCacheViewPeer::PRO_UID);
        $criteria->addSelectColumn(TaskPeer::TAS_UID);
        $criteria->addSelectColumn(TaskPeer::TAS_SELFSERVICE_TIME);
        $criteria->addSelectColumn(TaskPeer::TAS_SELFSERVICE_TIME_UNIT);
        $criteria->addSelectColumn(TaskPeer::TAS_SELFSERVICE_TRIGGER_UID);

        //FROM
        $condition = array();
        $condition[] = array(AppCacheViewPeer::TAS_UID, TaskPeer::TAS_UID);
        $condition[] = array(TaskPeer::TAS_SELFSERVICE_TIMEOUT, 1);
        $criteria->addJoinMC($condition, Criteria::LEFT_JOIN);

        //WHERE
        $criteria->add(AppCacheViewPeer::USR_UID, "");
        $criteria->add(AppCacheViewPeer::DEL_THREAD_STATUS, "OPEN");

        //QUERY
        $rsCriteria = AppCacheViewPeer::doSelectRS($criteria);
        $rsCriteria->setFetchmode(ResultSet::FETCHMODE_ASSOC);

        setExecutionMessage("Unassigned case");
        saveLog("unassignedCase", "action", "Unassigned case", "c");

        $calendar = new Calendar();

        while ($rsCriteria->next()) {
            $row = $rsCriteria->getRow();
            $flag = false;

            $appcacheAppUid = $row["APP_UID"];
            $appcacheDelIndex = $row["DEL_INDEX"];
            $appcacheDelDelegateDate = $row["DEL_DELEGATE_DATE"];
            $appcacheAppNumber = $row["APP_NUMBER"];
            $appcacheProUid = $row["PRO_UID"];
            $taskUid = $row["TAS_UID"];
            $taskSelfServiceTime = intval($row["TAS_SELFSERVICE_TIME"]);
            $taskSelfServiceTimeUnit = $row["TAS_SELFSERVICE_TIME_UNIT"];
            $taskSelfServiceTriggerUid = $row["TAS_SELFSERVICE_TRIGGER_UID"];

            if ($calendar->pmCalendarUid == '') {
                $calendar->getCalendar(null, $appcacheProUid, $taskUid);
                $calendar->getCalendarData();
            }

            $dueDate = $calendar->calculateDate(
                $appcacheDelDelegateDate,
                $taskSelfServiceTime,
                $taskSelfServiceTimeUnit //HOURS|DAYS|MINUTES
                //1
            );

            if (time() > $dueDate["DUE_DATE_SECONDS"] && $flag == false) {
                $sessProcess = null;
                $sessProcessSw = 0;

                //Load data
                $case = new Cases();
                $appFields = $case->loadCase($appcacheAppUid);

                $appFields["APP_DATA"]["APPLICATION"] = $appcacheAppUid;

                if (isset($_SESSION["PROCESS"])) {
                    $sessProcess = $_SESSION["PROCESS"];
                    $sessProcessSw = 1;
                }

                $_SESSION["PROCESS"] = $appFields["PRO_UID"];

                //Execute trigger
                $criteriaTgr = new Criteria();
                $criteriaTgr->add(TriggersPeer::TRI_UID, $taskSelfServiceTriggerUid);

                $rsCriteriaTgr = TriggersPeer::doSelectRS($criteriaTgr);
                $rsCriteriaTgr->setFetchmode(ResultSet::FETCHMODE_ASSOC);

                if ($rsCriteriaTgr->next()) {
                    $row = $rsCriteriaTgr->getRow();

                    if (is_array($row) && $row["TRI_TYPE"] == "SCRIPT") {
                        $arrayCron = unserialize(trim(@file_get_contents(PATH_DATA . "cron")));
                        $arrayCron["processcTimeProcess"] = 60; //Minutes
                        $arrayCron["processcTimeStart"] = time();
                        @file_put_contents(PATH_DATA . "cron", serialize($arrayCron));

                        //Trigger
                        global $oPMScript;

                        $oPMScript = new PMScript();
                        $oPMScript->setDataTrigger($row);
                        $oPMScript->setFields($appFields["APP_DATA"]);
                        $oPMScript->setScript($row["TRI_WEBBOT"]);
                        $oPMScript->setExecutedOn(PMScript::SELF_SERVICE_TIMEOUT);
                        $oPMScript->execute();

                        $appFields["APP_DATA"] = array_merge($appFields["APP_DATA"], $oPMScript->aFields);

                        unset($appFields['APP_STATUS']);
                        unset($appFields['APP_PROC_STATUS']);
                        unset($appFields['APP_PROC_CODE']);
                        unset($appFields['APP_PIN']);
                        $case->updateCase($appFields["APP_UID"], $appFields);

                        saveLog("unassignedCase", "action", "OK Executed trigger to the case $appcacheAppNumber");
                    }
                }

                unset($_SESSION["PROCESS"]);

                if ($sessProcessSw == 1) {
                    $_SESSION["PROCESS"] = $sessProcess;
                }
            }
        }

        setExecutionResultMessage("DONE");
    } catch (Exception $e) {
        setExecutionResultMessage("WITH ERRORS", "error");
        eprintln("  '-" . $e->getMessage(), "red");
        saveLog("unassignedCase", "error", "Error in unassigned case: " . $e->getMessage());
    }
}

function saveLog($sSource, $sType, $sDescription)
{
    try {
        global $sObject;
        global $isDebug;

        if ($isDebug) {
            print date("H:i:s") . " ($sSource) $sType $sDescription <br />\n";
        }

        G::verifyPath(PATH_DATA . "log" . PATH_SEP, true);
        G::log("| $sObject | " . $sSource . " | $sType | " . $sDescription, PATH_DATA);
    } catch (Exception $e) {
        //CONTINUE
    }
}

function setExecutionMessage($m)
{
    $len = strlen($m);
    $linesize = 60;
    $rOffset = $linesize - $len;

    eprint("* $m");

    for ($i = 0; $i < $rOffset; $i++) {
        eprint('.');
    }
}

function setExecutionResultMessage($m, $t = '')
{
    $c = 'green';

    if ($t == 'error') {
        $c = 'red';
    }

    if ($t == 'info') {
        $c = 'yellow';
    }

    if ($t == 'warning') {
        $c = 'yellow';
    }

    eprintln("[$m]", $c);
}

function sendNotifications()
{
    try {
        global $argvx;
        if ($argvx != "" && strpos($argvx, "send-notifications") === false) {
            return false;
        }
        setExecutionMessage("Resending Notifications");
        setExecutionResultMessage("PROCESSING");
        $notQueue = new \NotificationQueue();
        $notQueue->checkIfCasesOpenForResendingNotification();
        $notificationsAndroid = $notQueue->loadStatusDeviceType('pending', 'android');
        if ($notificationsAndroid) {
            setExecutionMessage("|-- Send Android's Notifications");
            $n = 0;
            foreach ($notificationsAndroid as $key => $item) {
                $oNotification = new \ProcessMaker\BusinessModel\Light\PushMessageAndroid();
                $oNotification->setSettingNotification();
                $oNotification->setDevices(unserialize($item['DEV_UID']));
                $response['android'] = $oNotification->send($item['NOT_MSG'], unserialize($item['NOT_DATA']));
                $notQueue = new \NotificationQueue();
                $notQueue->changeStatusSent($item['NOT_UID']);
                $n += $oNotification->getNumberDevices();
            }
            setExecutionResultMessage("Processed $n");
        }
        $notificationsApple = $notQueue->loadStatusDeviceType('pending', 'apple');
        if ($notificationsApple) {
            setExecutionMessage("|-- Send Apple Notifications");
            $n = 0;
            foreach ($notificationsApple as $key => $item) {
                $oNotification = new \ProcessMaker\BusinessModel\Light\PushMessageIOS();
                $oNotification->setSettingNotification();
                $oNotification->setDevices(unserialize($item['DEV_UID']));
                $response['apple'] = $oNotification->send($item['NOT_MSG'], unserialize($item['NOT_DATA']));
                $notQueue = new \NotificationQueue();
                $notQueue->changeStatusSent($item['NOT_UID']);
                $n += $oNotification->getNumberDevices();
            }
            setExecutionResultMessage("Processed $n");
        }
    } catch (Exception $e) {
        setExecutionResultMessage("WITH ERRORS", "error");
        eprintln("  '-" . $e->getMessage(), "red");
        saveLog("ExecuteSendNotifications", "error", "Error when sending notifications " . $e->getMessage());
    }
}

/**
 * Clean unused records in tables related to the Self-Service Value Based feature
 *
 * @see processWorkspace()
 *
 * @link https://wiki.processmaker.com/3.2/Executing_cron.php#Syntax_of_cron.php_Options
 */
function cleanSelfServiceTables()
{
    try {
        global $argvx;

        // Check if the action can be executed
        if ($argvx !== "" && strpos($argvx, "clean-self-service-tables") === false) {
            return false;
        }

        // Start message
        setExecutionMessage("Clean unused records for Self-Service Value Based feature");

        // Get Propel connection
        $cnn = Propel::getConnection(AppAssignSelfServiceValueGroupPeer::DATABASE_NAME);

        // Delete related rows and missing relations, criteria don't execute delete with joins
        $cnn->begin();
        $stmt = $cnn->createStatement();
        $stmt->executeQuery("DELETE " . AppAssignSelfServiceValueGroupPeer::TABLE_NAME . "
                             FROM " . AppAssignSelfServiceValueGroupPeer::TABLE_NAME . "
                             LEFT JOIN " . AppAssignSelfServiceValuePeer::TABLE_NAME . "
                             ON (" . AppAssignSelfServiceValueGroupPeer::ID . " = " . AppAssignSelfServiceValuePeer::ID . ")
                             WHERE " . AppAssignSelfServiceValuePeer::ID . " IS NULL");
        $cnn->commit();

        // Success message
        setExecutionResultMessage("DONE");
    } catch (Exception $e) {
        $cnn->rollback();
        setExecutionResultMessage("WITH ERRORS", "error");
        eprintln("  '-" . $e->getMessage(), "red");
        saveLog("ExecuteCleanSelfServiceTables", "error", "Error when try to clean self-service tables " . $e->getMessage());
    }
}
