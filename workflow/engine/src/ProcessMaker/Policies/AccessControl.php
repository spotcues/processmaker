<?php
namespace ProcessMaker\Policies;

use \Luracast\Restler\iAuthenticate;
use \Luracast\Restler\RestException;
use \Luracast\Restler\Defaults;
use \Luracast\Restler\Util;
use \Luracast\Restler\Scope;
use \OAuth2\Request;
use \ProcessMaker\Services\OAuth2\Server;
use \ProcessMaker\BusinessModel\User;

class AccessControl implements iAuthenticate
{
    public static $role;
    public static $permission;
    public static $className;
    private $userUid = null;
    private $oUser;

    /**
     * This method checks if an endpoint permission or permissions access
     *
     * @return bool
     * @throws RestException
     */
    public function __isAllowed()
    {
        $response = true;
        $oServerOauth = new Server();
        $this->oUser = new User();
        $server = $oServerOauth->getServer();
        $request = Request::createFromGlobals();
        $allowed = $server->verifyResourceRequest($request);
        $this->userUid = $oServerOauth->getUserId();
        $this->oUser->loadUserRolePermission('PROCESSMAKER', $this->userUid);
        $metadata = Util::nestedValue($this->restler, 'apiMethodInfo', 'metadata');
        if ($allowed && !empty($this->userUid) && (!empty($metadata['access']) && $metadata['access'] == 'protected')) {
            $parameters = Util::nestedValue($this->restler, 'apiMethodInfo', 'parameters');
            if (!is_null(self::$className) && is_string(self::$className)) {
                $authObj = Scope::get(self::$className);
                $authObj->parameters = $parameters;
                $authObj->permission = self::$permission;
                if (!method_exists($authObj, Defaults::$authenticationMethod)) {
                    throw new RestException (
                        500,
                        'Authentication Class should implement iAuthenticate');
                } elseif (!$authObj->{Defaults::$authenticationMethod}()) {
                    throw new RestException(403, "You don't have permission to access this endpoint or resource on this server.");
                }
            } elseif (!$this->verifyAccess(self::$permission)) {
                throw new RestException(401);
            }
        }
        return $response;
    }

    /**
     * @return string
     */
    public function __getWWWAuthenticateString()
    {
        return '';
    }

    /**
     * @param $permissions
     * @return bool
     */
    public function verifyAccess($permissions)
    {
        $response = false;
        $access = -1;
        if (!is_array($permissions)) {
            $access = $this->userCanAccess($permissions);
        } elseif (count($permissions) > 0) {
            foreach ($permissions as $perm) {
                $access = $this->userCanAccess($perm);
                if ($access == 1) {
                    break;
                }
            }
        }
        if ($access == 1 || empty($permissions)) {
            $response = true;
        }
        return $response;
    }

    public function userCanAccess($perm)
    {
        $res = -1;
        $permissions = Util::nestedValue($this->oUser, 'aUserInfo', 'PROCESSMAKER', 'PERMISSIONS');
        if (isset($permissions)) {
            $res = -3;
            foreach ($permissions as $key => $val) {
                if ($perm == $val['PER_CODE']) {
                    $res = 1;
                    break;
                }
            }
        }
        return $res;
    }
}
