<?php

/**
 * class.pmGoogleApi.php
 *
 */

require_once PATH_TRUNK . 'vendor' . PATH_SEP . 'google' . PATH_SEP . 'apiclient' . PATH_SEP . 'src' . PATH_SEP . 'Google' . PATH_SEP . 'autoload.php';

class PMGoogleApi
{
    private $scope = array();
    private $serviceAccountEmail;
    private $serviceAccountP12;
    private $statusService;
    private $domain;
    private $user;

    private $typeAuthentication;
    private $accountJson;

    public function __construct()
    {
        $licensedFeatures = &PMLicensedFeatures::getSingleton();
        if (!$licensedFeatures->verifyfeature('7qhYmF1eDJWcEdwcUZpT0k4S0xTRStvdz09')) {
            G::SendTemporalMessage('ID_USER_HAVENT_RIGHTS_PAGE', 'error', 'labels');
            G::header('location: ../login/login');
            die;
        }
        $this->loadSettings();
    }

    public function setScope($scope)
    {
        $this->scope[] = $scope;
    }

    public function getScope()
    {
        return $this->scope;
    }

    public function setUser($user)
    {
        $this->user = $user;
    }

    public function getUser()
    {
        return $this->user;
    }

    public function setStatusService($status)
    {
        $conf = $this->getConfigGmail();

        $conf->aConfig['statusService'] = $status;
        $conf->saveConfig('GOOGLE_API_SETTINGS', '', '', '');

        $this->statusService = $status;
    }

    public function getStatusService()
    {
        return $this->statusService;
    }

    public function getConfigGmail()
    {
        $conf = new Configurations();
        $conf->loadConfig($gmail, 'GOOGLE_API_SETTINGS', '');
        return $conf;
    }

    public function setServiceAccountEmail($serviceAccountEmail)
    {
        $conf = $this->getConfigGmail();

        $conf->aConfig['serviceAccountEmail'] = $serviceAccountEmail;
        $conf->saveConfig('GOOGLE_API_SETTINGS', '', '', '');

        $this->serviceAccountEmail = $serviceAccountEmail;
    }

    public function getServiceAccountEmail()
    {
        return $this->serviceAccountEmail;
    }

    public function setServiceAccountP12($serviceAccountP12)
    {
        $conf = $this->getConfigGmail();

        $conf->aConfig['serviceAccountP12'] = $serviceAccountP12;
        $conf->saveConfig('GOOGLE_API_SETTINGS', '', '', '');

        $this->serviceAccountP12 = $serviceAccountP12;
    }

    public function getServiceAccountP12()
    {
        return $this->serviceAccountP12;
    }

    public function setDomain($domain)
    {
        $conf = $this->getConfigGmail();

        $conf->aConfig['domain'] = $domain;
        $conf->saveConfig('GOOGLE_API_SETTINGS', '', '', '');

        $this->domain = $domain;
    }

    public function getDomain()
    {
        return $this->domain;
    }

    public function setTypeAuthentication($type)
    {
        $conf = $this->getConfigGmail();

        $conf->aConfig['typeAuthentication'] = $type;
        $conf->saveConfig('GOOGLE_API_SETTINGS', '', '', '');

        $this->typeAuthentication = $type;
    }

    public function getTypeAuthentication()
    {
        return $this->typeAuthentication;
    }

    public function setAccountJson($accountJson)
    {
        $conf = $this->getConfigGmail();

        $conf->aConfig['accountJson'] = $accountJson;
        $conf->saveConfig('GOOGLE_API_SETTINGS', '', '', '');

        $this->accountJson = $accountJson;
    }

    public function getAccountJson()
    {
        return $this->accountJson;
    }

    /**
     * load configuration gmail service account
     *
     */
    public function loadSettings()
    {
        $conf = $this->getConfigGmail();

        $typeAuthentication     = empty($conf->aConfig['typeAuthentication']) ? ''  : $conf->aConfig['typeAuthentication'];
        $accountJson            = empty($conf->aConfig['accountJson']) ? ''   : $conf->aConfig['accountJson'];

        $serviceAccountP12      = empty($conf->aConfig['serviceAccountP12']) ? ''   : $conf->aConfig['serviceAccountP12'];
        $serviceAccountEmail    = empty($conf->aConfig['serviceAccountEmail']) ? '' : $conf->aConfig['serviceAccountEmail'];
        $statusService          = empty($conf->aConfig['statusService']) ? ''       : $conf->aConfig['statusService'];

        $this->scope = array();

        $this->setTypeAuthentication($typeAuthentication);
        $this->setAccountJson($accountJson);

        $this->setServiceAccountEmail($serviceAccountEmail);
        $this->setServiceAccountP12($serviceAccountP12);
        $this->setStatusService($statusService);
    }

    /**
     * New service client - Authentication google Api
     *
     * @return Google_Service_Client $service API service instance.
     */
    public function serviceClient()
    {
        $client = null;
        if ($this->typeAuthentication == 'webApplication') {
            if (file_exists(PATH_DATA_SITE . $this->accountJson)) {
                $credential = file_get_contents(PATH_DATA_SITE . $this->accountJson);
            } else {
                throw new Exception(G::LoadTranslation('ID_GOOGLE_FILE_JSON_ERROR'));
            }


            $client = new Google_Client();
            $client->setAuthConfig($credential);
            $client->addScope($this->scope);

            if (!empty($_SESSION['google_token'])) {
                $client->setAccessToken($_SESSION['google_token']);
                if ($client->isAccessTokenExpired()) {
                    $client->getRefreshToken();
                    unset($_SESSION['google_token']);
                    $_SESSION['google_token'] = $client->getAccessToken();
                }
            } else if (!empty($_SESSION['CODE_GMAIL'])) {
                $token = $client->authenticate($_SESSION['CODE_GMAIL']);
                $_SESSION['google_token'] = $client->getAccessToken();
            } else {
                $authUrl = $client->createAuthUrl();
                echo '<script type="text/javascript">
                    var opciones = "width=450,height=480,scrollbars=NO, locatin=NO,toolbar=NO, status=NO, menumbar=NO, top=10%, left=25%";
                    window.open("' . $authUrl . '","Gmail", opciones);
                    </script>';
                die;
            }
        } else if ($this->typeAuthentication == 'serviceAccount') {

            if (file_exists(PATH_DATA_SITE . $this->serviceAccountP12)) {
                $key = file_get_contents(PATH_DATA_SITE . $this->serviceAccountP12);
            } else {
                throw new Exception(G::LoadTranslation('ID_GOOGLE_FILE_P12_ERROR'));
            }

            $assertionCredentials = new Google_Auth_AssertionCredentials(
                $this->serviceAccountEmail,
                $this->scope,
                $key
            );
            $assertionCredentials->sub = $this->user;

            $client = new Google_Client();
            $client->setApplicationName("PMDrive");
            $client->setAssertionCredentials($assertionCredentials);
        } else {
            throw new Exception(G::LoadTranslation('ID_SERVER_COMMUNICATION_ERROR'));
        }

        return $client;
    }

    /**
     * New service client - Authentication google Api
     *
     * @return Google_Service_Client $service API service instance.
     */
    public function testService($credentials)
    {

        $scope = array(
            'https://www.googleapis.com/auth/drive',
            'https://www.googleapis.com/auth/drive.file',
            'https://www.googleapis.com/auth/drive.readonly',
            'https://www.googleapis.com/auth/drive.metadata.readonly',
            'https://www.googleapis.com/auth/drive.appdata',
            'https://www.googleapis.com/auth/drive.metadata',
            'https://www.googleapis.com/auth/drive.photos.readonly'
        );

        if ($credentials->typeAuth == 'webApplication') {

            if (file_exists($credentials->pathFileJson)) {
                $credential = file_get_contents($credentials->pathFileJson);
            } else {
                throw new Exception(G::LoadTranslation('ID_GOOGLE_FILE_JSON_ERROR'));
            }

            $client = new Google_Client();
            $client->setAuthConfig($credential);
            $client->addScope($scope);

            if (!empty($_SESSION['google_token'])) {
                $client->setAccessToken($_SESSION['google_token']);
                if ($client->isAccessTokenExpired()) {
                    unset($_SESSION['google_token']);
                }
            } else if (!empty($_SESSION['CODE_GMAIL'])) {
                $token = $client->authenticate($_SESSION['CODE_GMAIL']);
                $_SESSION['google_token'] = $client->getAccessToken();
            } else {
                $authUrl = $client->createAuthUrl();
                echo '<script type="text/javascript">
                    var opciones = "width=450,height=480,scrollbars=NO, locatin=NO,toolbar=NO, status=NO, menumbar=NO, top=10%, left=25%";
                    window.open("' . $authUrl . '","Gmail", opciones);
                    </script>';
                die;
            }
        } else {

            if (file_exists($credentials->pathServiceAccountP12)) {
                $key = file_get_contents($credentials->pathServiceAccountP12);
            } else {
                throw new Exception(G::LoadTranslation('ID_GOOGLE_FILE_P12_ERROR'));
            }
            $assertionCredentials = new Google_Auth_AssertionCredentials(
                $credentials->emailServiceAccount,
                $scope,
                $key
            );
            $assertionCredentials->sub = $this->user;

            $client = new Google_Client();
            $client->setApplicationName("PMDrive");
            $client->setAssertionCredentials($assertionCredentials);
        }



        $service = new Google_Service_Drive($client);

        $result = new StdClass();
        $result->success = true;

        $result->currentUserName = G::LoadTranslation('ID_SERVER_COMMUNICATION_ERROR');
        $result->rootFolderId = G::LoadTranslation('ID_SERVER_COMMUNICATION_ERROR');
        $result->quotaType = G::LoadTranslation('ID_SERVER_COMMUNICATION_ERROR');
        $result->quotaBytesTotal = G::LoadTranslation('ID_SERVER_COMMUNICATION_ERROR');
        $result->quotaBytesUsed = G::LoadTranslation('ID_SERVER_COMMUNICATION_ERROR');

        try {
            $about = $service->about->get();

            $result->currentUserName = $about->getName();
            $result->rootFolderId = $about->getRootFolderId();
            $result->quotaType = $about->getQuotaType();
            $result->quotaBytesTotal = $about->getQuotaBytesTotal();
            $result->quotaBytesUsed = $about->getQuotaBytesUsed();
            $result->responseGmailTest = G::LoadTranslation('ID_SUCCESSFUL_CONNECTION');
        } catch (Exception $e) {
            $result->success = false;
            $result->responseGmailTest = G::LoadTranslation('ID_SERVER_COMMUNICATION_ERROR');
        }

        return $result;
    }
}
