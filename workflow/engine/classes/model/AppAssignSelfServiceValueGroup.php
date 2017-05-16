<?php

require_once 'classes/model/om/BaseAppAssignSelfServiceValueGroup.php';


/**
 * Skeleton subclass for representing a row from the 'APP_ASSIGN_SELF_SERVICE_VALUE_GROUP' table.
 *
 * 
 *
 * You should add additional methods to this class to meet the
 * application requirements.  This class will only be generated as
 * long as it does not already exist in the output directory.
 *
 * @package    classes.model
 */
class AppAssignSelfServiceValueGroup extends BaseAppAssignSelfServiceValueGroup {

	public function createRows($appAssignSelfServiceValueId, $dataVariable) {
		try {
			$con = Propel::getConnection(AppAssignSelfServiceValuePeer::DATABASE_NAME);
			$con->begin();
			$stmt = $con->createStatement();
			if (is_array($dataVariable)) {
				foreach ($dataVariable as $uid) {
					$rs = $stmt->executeQuery("INSERT INTO
                                                   " . AppAssignSelfServiceValueGroupPeer::TABLE_NAME . " (" . 
                                                       AppAssignSelfServiceValueGroupPeer::ID . ", " . 
                                                       AppAssignSelfServiceValueGroupPeer::GRP_UID . ")
                                               VALUES (" . $appAssignSelfServiceValueId . ", '" . $uid . "');");
				}
			} else {
				$rs = $stmt->executeQuery("INSERT INTO
                                               " . AppAssignSelfServiceValueGroupPeer::TABLE_NAME . " (" . 
                                                   AppAssignSelfServiceValueGroupPeer::ID . ", " . 
                                                   AppAssignSelfServiceValueGroupPeer::GRP_UID . ")
                                           VALUES (" . $appAssignSelfServiceValueId . ", '" . $dataVariable . "');");
			}
			$con->commit(); // Commit all rows inserted in batch
		} catch (Exception $error) {
            throw new $error;
		}
	}

} // AppAssignSelfServiceValueGroup
