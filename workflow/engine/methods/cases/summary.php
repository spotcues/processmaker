<?php

use ProcessMaker\Util\DateTime;

try {
    global $RBAC;
    switch ($RBAC->userCanAccess( 'PM_CASES' )) {
        case - 2:
            throw new Exception( G::LoadTranslation( 'ID_USER_HAVENT_RIGHTS_SYSTEM' ) );
            break;
        case - 1:
            throw new Exception( G::LoadTranslation( 'ID_USER_HAVENT_RIGHTS_PAGE' ) );
            break;
    }

    if (! isset( $_REQUEST['APP_UID'] ) || ! isset( $_REQUEST['DEL_INDEX'] ) || ! isset( $_REQUEST['DYN_UID'] )) {
        throw new Exception( G::LoadTranslation( 'ID_REQUIRED_FIELDS_ERROR' ) . ' (APP_UID, DEL_INDEX, DYN_UID)' );
    }

    if ($_REQUEST['APP_UID'] == '' || $_REQUEST['DEL_INDEX'] == '' || $_REQUEST['DYN_UID'] == '') {
        throw new Exception( G::LoadTranslation( 'ID_REQUIRED_FIELDS_ERROR' ) . ' (APP_UID, DEL_INDEX, DYN_UID)' );
    }

    $case = new Cases();
    $viewSummaryForm = 0;
    $applicationFields = $case->loadCase( $_REQUEST['APP_UID'], $_REQUEST['DEL_INDEX'] );


    //Check if the user has the Process Permissions - Summary Form
    if ($viewSummaryForm == 0) {
        throw new Exception( G::LoadTranslation( 'ID_SUMMARY_FORM_NO_PERMISSIONS' ) );
    }

    $applicationFields['APP_DATA']['__DYNAFORM_OPTIONS']['PREVIOUS_STEP_LABEL'] = '';
    $applicationFields['APP_DATA']['__DYNAFORM_OPTIONS']['PREVIOUS_STEP'] = '#';
    $applicationFields['APP_DATA']['__DYNAFORM_OPTIONS']['NEXT_STEP_LABEL'] = '';
    $applicationFields['APP_DATA']['__DYNAFORM_OPTIONS']['NEXT_ACTION'] = '#';
    $applicationFields['APP_DATA']['__DYNAFORM_OPTIONS']['DYNUIDPRINT'] = $_REQUEST['DYN_UID'];

    $criteria = new Criteria();
    $criteria->addSelectColumn(DynaformPeer::DYN_CONTENT);
    $criteria->add(DynaformPeer::DYN_UID, $_REQUEST['DYN_UID']);
    $criteria->add(DynaformPeer::DYN_VERSION, 2);
    $result = DynaformPeer::doSelectRS($criteria);
    $result->setFetchmode(ResultSet::FETCHMODE_ASSOC);
    if ($result->next()) {
        $FieldsPmDynaform = $applicationFields;
        $FieldsPmDynaform["CURRENT_DYNAFORM"] = $_REQUEST['DYN_UID'];
        $a = new PmDynaform(DateTime::convertUtcToTimeZone($FieldsPmDynaform));
        $a->printView();
    }
    if (file_exists( PATH_DYNAFORM . $applicationFields['PRO_UID'] . PATH_SEP . $_REQUEST['DYN_UID'] . '.xml' )) {
        $_SESSION['PROCESS'] = $applicationFields['PRO_UID'];
        $dbConnections = new DbConnections( $_SESSION['PROCESS'] );
        $dbConnections->loadAdditionalConnections();
        $_SESSION['CURRENT_DYN_UID'] = $_REQUEST['DYN_UID'];

        global $G_PUBLISH;
        $G_PUBLISH = new Publisher();
        $G_PUBLISH->AddContent( 'dynaform', 'xmlform', $applicationFields['PRO_UID'] . '/' . $_REQUEST['DYN_UID'], '', $applicationFields['APP_DATA'], '', '', 'view' );
        G::RenderPage( 'publish', 'blank' );
    } else {
        throw new Exception( G::LoadTranslation( 'INVALID_FILE' ) . ': ' . $_REQUEST['DYN_UID'] );
    }
} catch (Exception $error) {
    global $G_PUBLISH;
    $G_PUBLISH = new Publisher();
    $G_PUBLISH->AddContent( 'xmlform', 'xmlform', 'login/showMessage', '', array ('MESSAGE' => $error->getMessage()) );
    G::RenderPage( 'publish', 'blank' );
    die();
}

?>
<script type="text/javascript">
    leimnud.event.add(window,"load",function(){
        if (parent.document.getElementById('buttonOpenDynaform') != null) {
            parent.document.getElementById('buttonOpenDynaform').setAttribute('class', 'x-btn x-btn-noicon')
            parent.document.getElementById('buttonOpenDynaform').style = "width: auto;";
        }
    });
</script>
<?php
