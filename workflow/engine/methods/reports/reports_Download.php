<?php
/**
 * reports_Download.php
 *
 * Download reports related to the process
 *
 */

global $RBAC;
use ProcessMaker\Plugins\PluginRegistry;


if (empty($_GET['a'])) {
    G::header('Location: /errors/error403.php');
    die();
}


$bDownload = true;

$realPath = PATH_DATA_PUBLIC.'reports'.'/'.$_GET['a'];
$sw_file_exists = false;
if (file_exists($realPath)) {
    $sw_file_exists = true;
}

  if (!$sw_file_exists) {
      $error_message = G::LoadTranslation('ID_ERROR_STREAMING_FILE');
          G::SendMessageText($error_message, "ERROR");
          $backUrlObj = explode("sys" . config("system.workspace"), $_SERVER['HTTP_REFERER']);
          G::header("location: " . "/sys" . config("system.workspace") . $backUrlObj[1]);
          die();
  } else {
   
          $downloadStatus = false;
          if (!$downloadStatus) {
            //G::streamFile($realPath, $bDownload, $_GET['a']); //download
            $filename = $_GET['a'];
            ob_end_clean();
            header('Content-Description: File Transfer');
            header('Content-Type: ' . 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header("Content-Transfer-Encoding: Binary");
            header("Content-disposition: attachment; filename=\"" . $filename . "\"");
            header('Content-Transfer-Encoding: binary');
            header('Expires: 0');
            header('Cache-Control: must-revalidate');
            header('Pragma: public');
            readfile($realPath);
            exit();
          }
  }

