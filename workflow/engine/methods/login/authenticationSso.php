<?php
global $G_PUBLISH;

try {
    if ($RBAC->singleSignOn) {
        $_SESSION['__USER_LOGGED_SSO__']  = $RBAC->userObj->fields['USR_UID'];
        $_SESSION['__USR_USERNAME_SSO__'] = $RBAC->userObj->fields['USR_USERNAME'];
    } else {
        if (!isset($_SESSION['__USER_LOGGED_SSO__'])) {
            $u = '';

            if (isset($_POST['form']['URL']) && $_POST['form']['URL'] != '') {
                $u = $_POST['form']['URL'];
            } else {
                if (isset($_GET['u']) && $_GET['u'] != '') {
                    $u = $_GET['u'];
                }
            }

            header(
                'Location: /sys' . SYS_SYS . '/' . SYS_LANG . '/' . SYS_SKIN .
                '/login/login' . (($u != '')? '?u=' . $u : '')
            );

            exit(0);
        }
    }

    $userUid = (isset($_SESSION['USER_LOGGED']))? $_SESSION['USER_LOGGED'] : ((isset($_SESSION['__USER_LOGGED_SSO__']))? $_SESSION['__USER_LOGGED_SSO__'] : '');

    /*----------------------------------********---------------------------------*/
    if (PMLicensedFeatures::getSingleton()->verifyfeature('oq3S29xemxEZXJpZEIzN01qenJUaStSekY4cTdJVm5vbWtVM0d4S2lJSS9qUT0=')) {
        //Update User Time Zone
        if (isset($_POST['form']['BROWSER_TIME_ZONE'])) {
            $user = new Users();
            $user->update(['USR_UID' => $userUid, 'USR_TIME_ZONE' => $_POST['form']['BROWSER_TIME_ZONE']]);
        }
    }
    /*----------------------------------********---------------------------------*/

    $arraySystemConfiguration = System::getSystemConfiguration('', '', SYS_SYS);

    //Set User Time Zone
    $user = UsersPeer::retrieveByPK($userUid);

    if (!is_null($user)) {
        $userTimeZone = $user->getUsrTimeZone();

        if (trim($userTimeZone) == '') {
            $userTimeZone = $arraySystemConfiguration['time_zone'];
        }

        $_SESSION['USR_TIME_ZONE'] = $userTimeZone;
    }

    //Get default user location
    if (isset($_POST['form']['URL']) && $_POST['form']['URL'] != '') {
        $location = $_POST['form']['URL'];
    } else {
        if (isset($_GET['u']) && $_GET['u'] != '') {
            $location = $_GET['u'];
        } else {
            $userProperty = new UsersProperties();

            $location = $userProperty->redirectTo($userUid);
        }
    }

    /*----------------------------------********---------------------------------*/
    if (PMLicensedFeatures::getSingleton()->verifyfeature('oq3S29xemxEZXJpZEIzN01qenJUaStSekY4cTdJVm5vbWtVM0d4S2lJSS9qUT0=')) {
        if ((int)($arraySystemConfiguration['system_utc_time_zone'])) {
            $dateTime = new \ProcessMaker\Util\DateTime();

            $timeZoneOffset = $dateTime->getTimeZoneOffsetByTimeZoneId($_SESSION['USR_TIME_ZONE']);
            $browserTimeZoneOffset = 0;

            if (isset($_POST['form']['BROWSER_TIME_ZONE_OFFSET'])) {
                $browserTimeZoneOffset = (int)($_POST['form']['BROWSER_TIME_ZONE_OFFSET']);
            } else {
                if (isset($_GET['BROWSER_TIME_ZONE_OFFSET'])) {
                    $browserTimeZoneOffset = (int)($_GET['BROWSER_TIME_ZONE_OFFSET']);
                }
            }

            if ($timeZoneOffset === false || $timeZoneOffset != $browserTimeZoneOffset) {
                $userUtcOffset = $dateTime->getUtcOffsetByTimeZoneOffset($timeZoneOffset);
                $browserUtcOffset = $dateTime->getUtcOffsetByTimeZoneOffset($browserTimeZoneOffset);

                $arrayTimeZoneId = $dateTime->getTimeZoneIdByTimeZoneOffset($browserTimeZoneOffset);

                array_unshift($arrayTimeZoneId, 'false');
                array_walk(
                    $arrayTimeZoneId,
                    function (&$value, $key, $parameter)
                    {
                        $value = ['TZ_UID' => $value, 'TZ_NAME' => '(UTC ' . $parameter . ') ' . $value];
                    },
                    $browserUtcOffset
                );

                $_SESSION['_DBArray'] = ['TIME_ZONE' => $arrayTimeZoneId];

                $arrayData = [
                    'USR_USERNAME'  => '',
                    'USR_PASSWORD'  => '',
                    'USR_TIME_ZONE' => '(UTC ' . $userUtcOffset . ') ' . $_SESSION['USR_TIME_ZONE'],
                    'BROWSER_TIME_ZONE' => $dateTime->getTimeZoneIdByTimeZoneOffset($browserTimeZoneOffset, false),
                    'USER_LANG' => SYS_LANG,
                    'URL'       => $location
                ];

                $G_PUBLISH = new Publisher();
                $G_PUBLISH->AddContent(
                    'xmlform',
                    'xmlform',
                    'login' . PATH_SEP . 'TimeZoneAlert',
                    '',
                    $arrayData, SYS_URI . 'login/authenticationSso.php'
                );

                G::RenderPage('publish');
                exit(0);
            }
        }
    }
    /*----------------------------------********---------------------------------*/

    setcookie('singleSignOn', '1', time() + (24 * 60 * 60), '/');

    $_SESSION['USER_LOGGED']  = $_SESSION['__USER_LOGGED_SSO__'];
    $_SESSION['USR_USERNAME'] = $_SESSION['__USR_USERNAME_SSO__'];

    unset($_SESSION['__USER_LOGGED_SSO__'], $_SESSION['__USR_USERNAME_SSO__']);

    G::header('Location: ' . $location);
} catch (Exception $e) {
    $arrayData = [];
    $arrayData['MESSAGE'] = $e->getMessage();

    $G_PUBLISH = new Publisher();
    $G_PUBLISH->AddContent('xmlform', 'xmlform', 'login/showMessage', '', $arrayData);

    G::RenderPage('publish');
}
