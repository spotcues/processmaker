<?php

if (PMLicensedFeatures
        ::getSingleton()
        ->verifyfeature('zLhSk5TeEQrNFI2RXFEVktyUGpnczV1WEJNWVp6cjYxbTU3R29mVXVZNWhZQT0=')) {
    $G_PUBLISH = new Publisher();
    try {
        /**
         * $backupSession = serialize($_SESSION);
         * This script runs with $ _SESSION ['USER_LOGGED'] = '00000000000000000000000000000001', 
         * this action enables login as admin if you enter the url 'http://myserver.net/sysworkflow/en/neoclassic/processes/main', 
         * in the Browser that invoked this script. 
         * This action ensures that any changes to the session variables required by 
         * this script do not affect the main session if it exists, for example 
         * when multiple tabs are open.
         * Serialization is used because for object types the simple assignment: 
         * $backupSession = $ _SESSION will not work because the assignment is by 
         * reference, eg:
         * <?php
         * $obj = new stdClass();
         * $obj->value = "value";
         * 
         * $a = ["one" => 1, "two" => $obj, "three" => 3];
         * $b = $a;

         * $a["two"]->value = "modify";
         * 
         * In 'b' is reflected the output of 'a'.
         */
        $backupSession = serialize($_SESSION);
        
        if ($_REQUEST['APP_UID'] == '') {
            if($_GET['APP_UID'] == ''){
                 throw new Exception('The parameter APP_UID is empty.');
            } else {
                $_REQUEST['APP_UID'] = $_GET['APP_UID'];
            }
        }

        if ($_REQUEST['DEL_INDEX'] == '') {
            throw new Exception('The parameter DEL_INDEX is empty.');
        }

        if ($_REQUEST['ABER'] == '') {
            throw new Exception('The parameter ABER is empty.');
        }

        if (!isset($_REQUEST['form'])) {
            $_REQUEST['form'] = array();
        }

        $_REQUEST['APP_UID']   = G::decrypt($_REQUEST['APP_UID'],   URL_KEY);
        $_REQUEST['DEL_INDEX'] = G::decrypt($_REQUEST['DEL_INDEX'], URL_KEY);
        $_REQUEST['ABER']      = G::decrypt($_REQUEST['ABER'],      URL_KEY);
        G::LoadClass('case');

        $case = new Cases();
        $casesFields = $case->loadCase($_REQUEST['APP_UID'], $_REQUEST['DEL_INDEX']);

        $casesFields['APP_DATA'] = array_merge($casesFields['APP_DATA'], $_REQUEST['form']);

        //Get user info
        $current_user_uid = null;
        $currentUsrName   = null;

        $criteria = new Criteria("workflow");

        $criteria->addSelectColumn(AppDelegationPeer::USR_UID);
        $criteria->add(AppDelegationPeer::APP_UID, $_REQUEST["APP_UID"]);
        $criteria->add(AppDelegationPeer::DEL_INDEX, $_REQUEST["DEL_INDEX"]);

        $rsSQL = AppDelegationPeer::doSelectRS($criteria);
        $rsSQL->setFetchmode(ResultSet::FETCHMODE_ASSOC);

        while ($rsSQL->next()) {
            $row = $rsSQL->getRow();

            $current_user_uid = $row["USR_UID"];
        }

        if ($current_user_uid != null) {
            $criteria = new Criteria("workflow");

            $criteria->addSelectColumn(UsersPeer::USR_USERNAME);
            $criteria->add(UsersPeer::USR_UID, $current_user_uid);

            $rsSQL = UsersPeer::doSelectRS($criteria);
            $rsSQL->setFetchmode(ResultSet::FETCHMODE_ASSOC);

            $rsSQL->next();

            $row = $rsSQL->getRow();
            $currentUsrName = $row["USR_USERNAME"];

            $casesFields["APP_DATA"]["USER_LOGGED"]  = $current_user_uid;
            $casesFields["APP_DATA"]["USR_USERNAME"] = $currentUsrName;
        }

        foreach ($casesFields["APP_DATA"] as $index => $value) {
            $_SESSION[$index] = $value;
        }

        //Update case info
        $case->updateCase($_REQUEST['APP_UID'], $casesFields);

        G::LoadClass('wsBase');

        $wsBaseInstance = new wsBase();
        $result = $wsBaseInstance->derivateCase($casesFields['CURRENT_USER_UID'], $_REQUEST['APP_UID'], $_REQUEST ['DEL_INDEX'], true);
        $code = (is_array($result) ? $result['status_code'] : $result->status_code);

        $dataResponses = array();
        $dataResponses['ABE_REQ_UID'] = $_REQUEST['ABER'];
        $dataResponses['ABE_RES_CLIENT_IP'] = $_SERVER['REMOTE_ADDR'];
        $dataResponses['ABE_RES_DATA'] = serialize($_REQUEST['form']);
        $dataResponses['ABE_RES_STATUS'] = 'PENDING';
        $dataResponses['ABE_RES_MESSAGE'] = '';

        try {
            require_once 'classes/model/AbeResponses.php';

            $abeAbeResponsesInstance = new AbeResponses();
            $dataResponses['ABE_RES_UID'] = $abeAbeResponsesInstance->createOrUpdate($dataResponses);
        } catch (Exception $error) {
            throw $error;
        }

        if ($code == 0) {
            //Save Cases Notes
            include_once 'utils.php';

            $dataAbeRequests = loadAbeRequest($_REQUEST['ABER']);
            $dataAbeConfiguration = loadAbeConfiguration($dataAbeRequests['ABE_UID']);

            if ($dataAbeConfiguration['ABE_CASE_NOTE_IN_RESPONSE'] == 1) {
                $response = new stdclass();
                $response->usrUid = $casesFields['APP_DATA']['USER_LOGGED'];
                $response->appUid = $_REQUEST['APP_UID'];
                $response->noteText = "Check the information that was sent for the receiver: " . $dataAbeRequests['ABE_REQ_SENT_TO'];

                postNote($response);
            }

            $dataAbeRequests['ABE_REQ_ANSWERED'] = 1;
            $code == 0 ? uploadAbeRequest($dataAbeRequests) : '';

            if (isset ( $_FILES ['form'] )) {
                if (isset( $_FILES["form"]["name"] ) && count( $_FILES["form"]["name"] ) > 0) {
                    $oInputDocument = new \ProcessMaker\BusinessModel\Cases\InputDocument();
                    $oInputDocument->uploadFileCase($_FILES, $case, $casesFields, $current_user_uid, $_REQUEST['APP_UID'], $_REQUEST["DEL_INDEX"]);
                }
            }

            $assign = $result['message'];
            $aMessage['MESSAGE'] = '<strong>The information was submitted. Thank you.</strong>';
        } else {
            throw new Exception('An error occurred while the application was being processed.<br /><br />
                                 Error code: '.$result->status_code.'<br />
                                 Error message: '.$result->message.'<br /><br />');
        }

        // Update
        $dataResponses['ABE_RES_STATUS'] = ($code == 0 ? 'SENT' : 'ERROR');
        $dataResponses['ABE_RES_MESSAGE'] = ($code == 0 ? '-' : $result->message);

        try {
            $abeAbeResponsesInstance = new AbeResponses();
            $abeAbeResponsesInstance->createOrUpdate($dataResponses);
        } catch (Exception $error) {
            throw $error;
        }

        $_SESSION = unserialize($backupSession);
        $G_PUBLISH->AddContent('xmlform', 'xmlform', 'login/showInfo', '', $aMessage);
    } catch (Exception $error) {
        $G_PUBLISH->AddContent('xmlform', 'xmlform', 'login/showMessage', '', array('MESSAGE' => $error->getMessage().' Please contact to your system administrator.'));
    }
    $_SESSION = unserialize($backupSession);
    G::RenderPage('publish', 'blank');
}

