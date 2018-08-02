<?php

use ProcessMaker\Core\System;

$filter = new InputFilter();
$_POST = $filter->xssFilterHard($_POST);
$_REQUEST = $filter->xssFilterHard($_REQUEST);

$arrayToTranslation = [
    'TRIGGER' => G::LoadTranslation('ID_TRIGGER_DB'),
    'DERIVATION' => G::LoadTranslation('ID_DERIVATION_DB')
];

$actionAjax = isset($_REQUEST['actionAjax']) ? $_REQUEST['actionAjax'] : null;

switch ($actionAjax) {
    case 'messageHistoryGridList_JXP':
        if (!isset($_REQUEST['start']) || $_REQUEST['start'] == '') {
            $_REQUEST['start'] = 0;
        }

        if (!isset($_REQUEST['limit']) || $_REQUEST['limit'] == '') {
            $_REQUEST['limit'] = 20;
        }

        $dir = isset($_POST['dir']) ? $_POST['dir'] : 'ASC';
        $sort = isset($_POST['sort']) ? $_POST['sort'] : '';

        global $G_PUBLISH;
        $case = new Cases();
        $case->dir = $dir;
        $case->sort = $sort;

        $appMessageArray = $case->getHistoryMessagesTrackerExt($_SESSION['APPLICATION'], true, $_REQUEST['start'], $_REQUEST['limit']);
        $appMessageCountArray = $case->getHistoryMessagesTrackerExt($_SESSION['APPLICATION'], true);
        $result = new stdClass();
        $process = [];


        $proUid = $_SESSION['PROCESS'];
        $appUid = $_SESSION['APPLICATION'];
        $tasUid = $_SESSION['TASK'];
        $usrUid = $_SESSION['USER_LOGGED'];

        $respBlock = $case->getAllObjectsFrom($proUid, $appUid, $tasUid, $usrUid, 'BLOCK');
        $respView = $case->getAllObjectsFrom($proUid, $appUid, $tasUid, $usrUid, 'VIEW');
        $respResend = $case->getAllObjectsFrom($proUid, $appUid, $tasUid, $usrUid, 'RESEND');

        $delIndex = [];
        $respMess = '';

        if (count($respBlock['MSGS_HISTORY']) > 0) {
            $respMess = $respBlock['MSGS_HISTORY']['PERMISSION'];
            if (isset($respBlock['MSGS_HISTORY']['DEL_INDEX'])) {
                $delIndex = $respBlock['MSGS_HISTORY']['DEL_INDEX'];
            }
        }

        if (count($respView['MSGS_HISTORY']) > 0) {
            $respMess = $respView['MSGS_HISTORY']['PERMISSION'];
            if (isset($respView['MSGS_HISTORY']['DEL_INDEX'])) {
                $delIndex = $respView['MSGS_HISTORY']['DEL_INDEX'];
            }
        }

        if (count($respResend['MSGS_HISTORY']) > 0) {
            $respMess = $respResend['MSGS_HISTORY']['PERMISSION'];
            if (isset($respResend['MSGS_HISTORY']['DEL_INDEX'])) {
                $delIndex = $respResend['MSGS_HISTORY']['DEL_INDEX'];
            }
        }

        $totalCount = 0;
        foreach ($appMessageArray as $index => $value) {
            if (($appMessageArray[$index]['APP_MSG_SHOW_MESSAGE'] == 1 && $respMess != 'BLOCK') &&
                ($appMessageArray[$index]['DEL_INDEX'] == 0 || in_array($appMessageArray[$index]['DEL_INDEX'], $delIndex))) {
                $appMessageArray[$index]['ID_MESSAGE'] = $appMessageArray[$index]['APP_UID'] . '_' . $appMessageArray[$index]['APP_MSG_UID'];
                if ($respMess == 'BLOCK' || $respMess == '') {
                    $appMessageArray[$index]['APP_MSG_BODY'] = '';
                }
                $process[] = array_merge($appMessageArray[$index], ['MSGS_HISTORY' => $respMess]);
                $totalCount++;
            }
        }

        $process = array_splice($process, $_REQUEST['start'], $_REQUEST['limit']);

        $response = new stdclass();
        $response->data = $process;
        $response->totalCount = $totalCount;

        if (!empty($process)) {
            if (!isset($response->data[0])) {
                $response->data[0] = array('APP_MSG_TYPE' => '');
            }

            foreach ($response->data as $key => $value) {
                $response->data[$key]['APP_MSG_TYPE'] = array_key_exists($response->data[$key]['APP_MSG_TYPE'], $arrayToTranslation) ?
                    $arrayToTranslation[$response->data[$key]['APP_MSG_TYPE']] :
                    $response->data[$key]['APP_MSG_TYPE'];
            }
        }

        echo G::json_encode($response);
        break;
    case 'showHistoryMessage':
        ?>
        <link rel="stylesheet" type="text/css" href="/css/classic.css"/>
        <style type="text/css">
            html {
                color: black !important;
            }

            body {
                color: black !important;
            }
        </style>
        <script language="Javascript">
            //!Code that simulated reload library javascript maborak
            var leimnud = {};
            leimnud.exec = "";
            leimnud.fix = {};
            leimnud.fix.memoryLeak = "";
            leimnud.browser = {};
            leimnud.browser.isIphone = "";
            leimnud.iphone = {};
            leimnud.iphone.make = function () {
            };

            function ajax_function(ajax_server, funcion, parameters, method) {
            }

            //!
        </script>
        <?php

        $case = new Cases();

        $_POST['APP_UID'] = $_REQUEST['APP_UID'];
        $_POST['APP_MSG_UID'] = $_REQUEST['APP_MSG_UID'];

        $G_PUBLISH = new Publisher();
        $case = new Cases();

        $G_PUBLISH->AddContent('xmlform', 'xmlform', 'cases/cases_MessagesView', '', $case->getHistoryMessagesTrackerView($_POST['APP_UID'], $_POST['APP_MSG_UID']));
        ?>
        <script language="javascript">
            <?php
            global $G_FORM; ?>
            function loadForm_ <?php echo $G_FORM->id; ?>(parameter) {
            }
        </script>
        <?php

        G::RenderPage('publish', 'raw');
        break;
    case 'sendMailMessage_JXP':
        //!dataSystem
        $message = '';
        try {
            //!dataInput
            $_POST['APP_UID'] = $_REQUEST['APP_UID'];
            $_POST['APP_MSG_UID'] = $_REQUEST['APP_MSG_UID'];

            $case = new Cases();
            $data = $case->getHistoryMessagesTrackerView($_POST['APP_UID'], $_POST['APP_MSG_UID']);

            $spool = new SpoolRun();

            $spool->setConfig(System::getEmailConfiguration());
            $spool->create([
                'msg_uid' => $data['MSG_UID'],
                'app_uid' => $data['APP_UID'],
                'del_index' => $data['DEL_INDEX'],
                'app_msg_type' => $data['APP_MSG_TYPE'],
                'app_msg_subject' => $data['APP_MSG_SUBJECT'],
                'app_msg_from' => $data['APP_MSG_FROM'],
                'app_msg_to' => $data['APP_MSG_TO'],
                'app_msg_body' => $data['APP_MSG_BODY'],
                'app_msg_cc' => $data['APP_MSG_CC'],
                'app_msg_bcc' => $data['APP_MSG_BCC'],
                'app_msg_attach' => $data['APP_MSG_ATTACH'],
                'app_msg_template' => $data['APP_MSG_TEMPLATE'],
                'app_msg_status' => 'pending'
            ]);
            $spool->sendMail();
        } catch (Exception $error) {
            $message = $error->getMessage();
        }

        echo $message;
        break;
}
