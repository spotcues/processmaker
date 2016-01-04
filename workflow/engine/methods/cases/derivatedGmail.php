<?php
$licensedFeatures = & PMLicensedFeatures::getSingleton();
if (!$licensedFeatures->verifyfeature('7qhYmF1eDJWcEdwcUZpT0k4S0xTRStvdz09')) {
    G::SendTemporalMessage( 'ID_USER_HAVENT_RIGHTS_PAGE', 'error', 'labels' );
    G::header( 'location: ../login/login' );
    die;
}
$caseId = $_SESSION['APPLICATION'];
$usrUid = $_SESSION['USER_LOGGED'];
$usrName = $_SESSION['USR_FULLNAME'];
$actualIndex = $_SESSION['INDEX'];
$cont = 0;

use \ProcessMaker\Services\Api;
$appDel = new AppDelegation();

$actualThread = $appDel->Load($caseId, $actualIndex);
$actualLastIndex = $actualThread['DEL_PREVIOUS'];

$appDelPrev = $appDel->LoadParallel($caseId);

if($appDelPrev == array()){
    $appDelPrev['0'] = $actualThread;
}

$Pmgmail = new \ProcessMaker\BusinessModel\Pmgmail();
foreach ($appDelPrev as $app){
    if( ($app['DEL_INDEX'] != $actualIndex) && ($app['DEL_PREVIOUS'] != $actualLastIndex) ){ //Sending the email to all threads of the case except the actual thread
        $response = $Pmgmail->sendEmail($caseId, "", $app['DEL_INDEX']);
    }
}

require_once (PATH_HOME . "engine" . PATH_SEP . "classes" . PATH_SEP . "class.labelsGmail.php");
$oLabels = new labelsGmail();
$oResponse = $oLabels->setLabels($caseId, $actualIndex, $actualLastIndex, false);

$enablePMGmail = false;
G::LoadClass( "pmDrive" );
$pmDrive = new PMDrive();
$enablePMGmail = $pmDrive->getStatusService();
if(key_exists('gmail', $_SESSION) && $_SESSION['gmail'] == 1 && !empty($enablePMGmail) && $enablePMGmail==1 ){
	$_SESSION['gmail'] = 0;
	unset($_SESSION['gmail']); //cleaning session
	$mUrl = '/sys'. $_SESSION['WORKSPACE'] .'/en/neoclassic/cases/cases_Open?APP_UID='.$caseId.'&DEL_INDEX='.$actualIndex.'&action=sent';
} else{
	$mUrl = 'casesListExtJs';
}

header( 'location:' . $mUrl );

