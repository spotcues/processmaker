<?php

use ProcessMaker\Util\DateTime;

switch ($RBAC->userCanAccess('PM_CASES')) {
    case - 2:
        G::SendTemporalMessage('ID_USER_HAVENT_RIGHTS_SYSTEM', 'error', 'labels');
        G::header('location: ../login/login');
        die();
        break;
    case - 1:
        G::SendTemporalMessage('ID_USER_HAVENT_RIGHTS_PAGE', 'error', 'labels');
        G::header('location: ../login/login');
        die();
        break;
}

$oCase = new Cases();
$Fields = $oCase->loadCase($_SESSION['APPLICATION'], $_SESSION['INDEX']);

/* Render page */
require_once 'classes/model/Process.php';
require_once 'classes/model/Task.php';

$objProc = new Process();
$aProc = $objProc->load($Fields['PRO_UID']);
$Fields['PRO_TITLE'] = $aProc['PRO_TITLE'];

$objTask = new Task();
$aTask = $objTask->load($Fields['TAS_UID']);
$Fields['TAS_TITLE'] = $aTask['TAS_TITLE'];

$Fields['STATUS'] .= ' ( ' . G::LoadTranslation('ID_UNASSIGNED') . ' )';

//now getting information about the PREVIOUS task. If is the first task then no preious, use 1
$oAppDel = new AppDelegation();
$oAppDel->Load($Fields['APP_UID'], ($Fields['DEL_PREVIOUS'] == 0 ? $Fields['DEL_PREVIOUS'] = 1 : $Fields['DEL_PREVIOUS']));

$aAppDel = $oAppDel->toArray(BasePeer::TYPE_FIELDNAME);
try {
    $oCurUser = new Users();
    $oCurUser->load($aAppDel['USR_UID']);
    $Fields['PREVIOUS_USER'] = $oCurUser->getUsrFirstname() . ' ' . $oCurUser->getUsrLastname();
} catch (Exception $oError) {
    $Fields['PREVIOUS_USER'] = G::LoadTranslation('ID_NO_PREVIOUS_USR_UID');
}

$objTask = new Task();
$aTask = $objTask->load($aAppDel['TAS_UID']);
$Fields['PREVIOUS_TASK'] = $aTask['TAS_TITLE'];

//To enable information (dynaforms, steps) before claim a case
$_SESSION['bNoShowSteps'] = true;
$G_MAIN_MENU = 'processmaker';
$G_SUB_MENU = 'caseOptions';
$G_ID_MENU_SELECTED = 'CASES';
$G_ID_SUB_MENU_SELECTED = '_';
$oHeadPublisher = headPublisher::getSingleton();
$oHeadPublisher->addScriptCode("
if (typeof parent != 'undefined') {
  if (parent.showCaseNavigatorPanel) {
    parent.showCaseNavigatorPanel('{$Fields['APP_STATUS']}');
  }
}");
$oHeadPublisher->addScriptCode('
      var Cse = {};
      Cse.panels = {};
      var leimnud = new maborak();
      leimnud.make();
      leimnud.Package.Load("rpc,drag,drop,panel,app,validator,fx,dom,abbr",{Instance:leimnud,Type:"module"});
      leimnud.Package.Load("cases",{Type:"file",Absolute:true,Path:"/jscore/cases/core/cases.js"});
      leimnud.Package.Load("cases_Step",{Type:"file",Absolute:true,Path:"/jscore/cases/core/cases_Step.js"});
      leimnud.Package.Load("processmap",{Type:"file",Absolute:true,Path:"/jscore/processmap/core/processmap.js"});
      leimnud.exec(leimnud.fix.memoryLeak);
    ');
$oHeadPublisher = headPublisher::getSingleton();
$oHeadPublisher->addScriptFile('/jscore/cases/core/cases_Step.js');

$Fields['isIE'] = Bootstrap::isIE();

$G_PUBLISH = new Publisher();
$Fields = DateTime::convertUtcToTimeZone($Fields);
$G_PUBLISH->AddContent('xmlform', 'xmlform', 'cases/cases_CatchSelfService.xml', '', $Fields, 'cases_CatchExecute');
G::RenderPage('publish', 'blank');
