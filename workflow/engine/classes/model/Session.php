<?php
/**
 * Session.php
 * @package    workflow.engine.classes.model
 */

require_once 'classes/model/om/BaseSession.php';


/**
 * Skeleton subclass for representing a row from the 'SESSION' table.
 *
 *
 *
 * You should add additional methods to this class to meet the
 * application requirements.  This class will only be generated as
 * long as it does not already exist in the output directory.
 *
 * @package    workflow.engine.classes.model
 */
class Session extends BaseSession
{
    /**
     * Delete all records related to a user uid
     * @param string $userUid User uid
     * @return int
     * @throws PropelException
     */
    public function removeByUser($userUid)
    {
        $criteria = new Criteria();
        $criteria->add(SessionPeer::USR_UID, $userUid);
        $resultSet = SessionPeer::doDelete($criteria);
        return $resultSet;
    }
}

