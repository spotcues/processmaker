<?php
/**
 * processCategoryList.php
 *
 * ProcessMaker Open Source Edition
 * Copyright (C) 2004 - 2008 Colosa Inc.23
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

use ProcessMaker\Exception\RBACException;

/** @var RBAC $RBAC */
global $RBAC;
if ($RBAC->userCanAccess('PM_SETUP') != 1 && $RBAC->userCanAccess('PM_SETUP_PROCESS_CATEGORIES') != 1) {
    throw new RBACException('ID_USER_HAVENT_RIGHTS_PAGE', -1);
}

$c = new Configurations();
$configPage = $c->getConfiguration('processCategoryList', 'pageSize', '', $_SESSION['USER_LOGGED']);
$Config['pageSize'] = isset($configPage['pageSize']) ? $configPage['pageSize'] : 20;

$G_MAIN_MENU = 'workflow';
$G_SUB_MENU = 'processCategory';
$G_ID_MENU_SELECTED = '';
$G_ID_SUB_MENU_SELECTED = '';

$G_PUBLISH = new Publisher();

$oHeadPublisher = headPublisher::getSingleton();
$oHeadPublisher->addExtJsScript('processCategory/processCategoryList', false); //adding a javascript file .js
$oHeadPublisher->addContent('processCategory/processCategoryList'); //adding a html file  .html.
$oHeadPublisher->assign('FORMATS', $c->getFormats());
$oHeadPublisher->assign('CONFIG', $Config);
G::RenderPage('publish', 'extJs');
