<?php
/**
 * authSources_SelectType.php
 *
 * ProcessMaker Open Source Edition
 * Copyright (C) 2004 - 2011 Colosa Inc.23
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 * For more information, contact Colosa Inc, 2566 Le Jeune Rd.,
 * Coral Gables, FL, 33134, USA, or email info@colosa.com.
 */
global $RBAC;
if ($RBAC->userCanAccess('PM_SETUP_ADVANCE') != 1) {
    G::SendTemporalMessage('ID_USER_HAVENT_RIGHTS_PAGE', 'error', 'labels');
    G::header('location: ../login/login');
    die();
}

$G_MAIN_MENU = 'processmaker';
$G_SUB_MENU = 'users';
$G_ID_MENU_SELECTED = 'USERS';
$G_ID_SUB_MENU_SELECTED = 'AUTH_SOURCES';

$aAuthSourceTypes = array(array('sType' => 'char','sLabel' => 'char'));
$oDirectory = dir(PATH_RBAC . 'plugins' . PATH_SEP);
while ($sObject = $oDirectory->read()) {
    if (($sObject != '.') && ($sObject != '..') && ($sObject != '.svn') && ($sObject != 'ldap')) {
        if (is_file(PATH_RBAC . 'plugins' . PATH_SEP . $sObject)) {
            $sType = trim(str_replace('class.', '', str_replace('.php', '', $sObject)));
            $aAuthSourceTypes[] = array('sType' => $sType,'sLabel' => $sType );
        }
    }
}
global $_DBArray;
$_DBArray['authSourceTypes'] = $aAuthSourceTypes;
$_SESSION['_DBArray'] = $_DBArray;

$G_PUBLISH = new Publisher();
$oHeadPublisher = headPublisher::getSingleton();
$oHeadPublisher->addExtJsScript('authSources/authSourcesListNew', true); //adding a javascript file .js
G::RenderPage('publish', 'extJs');
