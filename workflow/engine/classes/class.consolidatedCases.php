<?php

G::LoadClass("pmFunctions");
G::LoadClass("reportTables");

class ConsolidatedCases
{
    private $existTable;
    private $existTableName;
    private $existCaseConsolidate;
    private $rowRepTab;
    private $rowCaseConsCore;

    public function processConsolidated($data)
    {
        $Status = $data['con_status'];
        $TasUid = $data['tas_uid'];
        $DynUid = $data['dyn_uid'];
        $ProUid = $data['pro_uid'];
        $RepTabUid = $data['rep_uid'];
        $TableName = $data['table_name'];
        $Title = $data['title'];
        if($this->isUpdateFields($data) && $Status) {
            return true;
        }
        if ($this->existTable && $Status) {
            throw (new \Exception(\G::LoadTranslation("ID_TABLE_ALREADY_EXISTS")));
        }

        if ($RepTabUid != '') {
            $this->deleteRepTab($RepTabUid, $Status, $TasUid, $TableName);
            if(!$Status){
                return true;
            }
            $RepTabUid = '';
        }

        $_POST['form']['PRO_UID'] = $ProUid;
        $_POST['form']['REP_TAB_UID']  = $RepTabUid;
        $_POST['form']['REP_TAB_NAME'] = $TableName;
        $_POST['form']['REP_TAB_TYPE'] = "NORMAL";
        $_POST['form']['REP_TAB_GRID'] = '';
        $_POST['form']['REP_TAB_CONNECTION'] = 'wf';
        $_POST['form']['REP_TAB_CREATE_DATE'] = date("Y-m-d H:i:s");
        $_POST['form']['REP_TAB_STATUS'] = 'ACTIVE';
        $_POST['form']['REP_TAB_TITLE'] = $Title;
        $_POST['form']['FIELDS'] = array();

        $sOldTableName  = $_POST['form']['REP_TAB_NAME'];
        $sOldConnection = $_POST['form']['REP_TAB_CONNECTION'];

        $_POST['form']['REP_TAB_UID'] = $this->createReportTable($_POST['form']);

        $oReportTables = new ReportTables();
        $oReportTables->deleteAllReportVars($_POST['form']['REP_TAB_UID']);

        G::LoadClass("pmDynaform");
        $pmDyna = new pmDynaform(array());
        $pmDyna->fields["CURRENT_DYNAFORM"] = $DynUid;
        $dataDyna = $pmDyna->getDynaform();
        $json = G::json_decode($dataDyna["DYN_CONTENT"]);
        $fieldsDyna = $json->items[0]->items;
        $valueType = array(
            'text',
            'textarea',
            'dropdown',
            'checkbox',
            'datetime',
            'yesno',
            'date',
            'hidden',
            'currency',
            'percentage',
            'link'
        );

        foreach ($fieldsDyna as $value) {
            foreach ($value as $val) {
                if (isset($val->type)) {
                    if (in_array($val->type, $valueType)) {
                        $_POST['form']['FIELDS'][] = $val->name . '-' . $val->type;
                    }
                }
            }
        }

        list($aFieldsClass, $aFields) = $this->createReportVariables($_POST['form']['REP_TAB_UID'], $ProUid, $_POST['form']['FIELDS']);

        $_POST['form']['REP_TAB_TYPE'] = "NORMAL";
        $oReportTables->dropTable($sOldTableName, $sOldConnection);
        $oReportTables->createTable($_POST['form']['REP_TAB_NAME'], $_POST['form']['REP_TAB_CONNECTION'], $_POST['form']['REP_TAB_TYPE'], $aFields);
        $oReportTables->populateTable($_POST['form']['REP_TAB_NAME'], $_POST['form']['REP_TAB_CONNECTION'], $_POST['form']['REP_TAB_TYPE'], $aFields, $_POST['form']['PRO_UID'], '');
        $sRepTabUid = $_POST['form']['REP_TAB_UID'];

        $oCaseConsolidated = CaseConsolidatedCorePeer::retrieveByPK($TasUid);
        if (!(is_object($oCaseConsolidated)) || get_class($oCaseConsolidated) != 'CaseConsolidatedCore') {
            $oCaseConsolidated = new CaseConsolidatedCore();
            $oCaseConsolidated->setTasUid($TasUid);
        }
        if ($this->existCaseConsolidate) {
            $oCaseConsolidated->delete();
            $oCaseConsolidated = CaseConsolidatedCorePeer::retrieveByPK($TasUid);
        }

        if (!(is_object($oCaseConsolidated)) || get_class($oCaseConsolidated) != 'CaseConsolidatedCore') {
            $oCaseConsolidated = new CaseConsolidatedCore();
            $oCaseConsolidated->setTasUid($TasUid);
        }

        $oCaseConsolidated->setConStatus('ACTIVE');
        $oCaseConsolidated->setDynUid($DynUid);
        $oCaseConsolidated->setRepTabUid($sRepTabUid);
        $oCaseConsolidated->save();

        $sClassName = $TableName;
        $oAdditionalTables = new AdditionalTables();
        $oAdditionalTables->createPropelClasses($TableName, $sClassName, $aFieldsClass, $TasUid);
    }

    public function deleteRepTab($RepTabUid, $Status, $TasUid, $TableName)
    {
        if (!$Status) {
            $oCaseConsolidated = CaseConsolidatedCorePeer::retrieveByPK($TasUid);
            if (!(is_object($oCaseConsolidated)) || get_class($oCaseConsolidated) != 'CaseConsolidatedCore') {
                $oCaseConsolidated = new CaseConsolidatedCore();
                $oCaseConsolidated->setTasUid($TasUid);
                $oCaseConsolidated->setConStatus('INACTIVE');
                $oCaseConsolidated->save();
            } else {
                $oCaseConsolidated->delete();
            }
            return;
        }

        $rptUid = null;
        $criteria = new Criteria();
        $criteria->addSelectColumn(ReportTablePeer::REP_TAB_UID);
        $criteria->add(ReportTablePeer::REP_TAB_UID, $RepTabUid);
        $rsCriteria = ReportTablePeer::doSelectRS($criteria);

        if ($rsCriteria->next()) {
            $row = $rsCriteria->getRow();
            $rptUid = $row[0];
        }

        $rpts = new ReportTables();
        if ($rptUid != null) {
            $rpts->deleteReportTable($rptUid);
        }

        $sClassName = $TableName;
        $sPath = PATH_DB . SYS_SYS . PATH_SEP . 'classes' . PATH_SEP;

        @unlink($sPath . $sClassName . '.php');
        @unlink($sPath . $sClassName . 'Peer.php');
        @unlink($sPath . PATH_SEP . 'map' . PATH_SEP . $sClassName . 'MapBuilder.php');
        @unlink($sPath . PATH_SEP . 'om' . PATH_SEP . 'Base' . $sClassName . '.php');
        @unlink($sPath . PATH_SEP . 'om' . PATH_SEP . 'Base' . $sClassName . 'Peer.php');
        return;
    }

    public function isUpdateFields($data)
    {
        $oSession = new DBSession(new DBConnection());
        $oTables = $oSession->Execute('SHOW TABLES FROM ' . DB_NAME);
        $this->existTable = false;
        while ($aRow = $oTables->read()) {
            if (in_array($data['table_name'], $aRow)) {
                $this->existTable = true;
                break;
            }
        }
        $this->existTableName = $this->existTableName($data['table_name']);
        $this->existCaseConsolidate = $this->existCaseConsolidate($data['tas_uid']);
        unset($data['title']);
        unset($data['con_status']);
        $diff = $data;
        if ($this->existTableName) {
            $diff = array_diff($data, $this->rowRepTab);
        }
        if ($this->existCaseConsolidate){
            $diff = array_diff($diff, $this->rowCaseConsCore);
        }
        return count($diff) <= 0;
    }

    public function existTableName($name)
    {
        $criteria = new Criteria();
        $criteria->add(ReportTablePeer::REP_TAB_NAME, $name);
        $rsCriteria = ReportTablePeer::doSelectRS($criteria);
        $existName = false;
        if ($rsCriteria->next()) {
            $this->rowRepTab = $rsCriteria->getRow();
            $existName = true;
        }
        return $existName;
    }

    public function existCaseConsolidate($taskUid)
    {
        $criteria = new Criteria();
        $criteria->add(CaseConsolidatedCorePeer::TAS_UID, $taskUid);
        $rsCriteria = CaseConsolidatedCorePeer::doSelectRS($criteria);
        $existCaseConsolidate = false;
        if ($rsCriteria->next()) {
            $this->rowCaseConsCore = $rsCriteria->getRow();
            $existCaseConsolidate = true;
        }
        return $existCaseConsolidate;
    }

    public function createReportTable($dataRepTab)
    {
        G::LoadClass("reportTables");
        $oReportTable = new ReportTable();
        $oReportTable->create($dataRepTab);
        return $oReportTable->getRepTabUid();
    }

    public function createReportVariables($RepTabUid, $ProUid, $formFields)
    {
        $oReportVar = new ReportVar();
        $fieldsClass = array();
        $fields = array();
        $i = 1;
        $fieldsClass[$i]['FLD_NAME'] = 'APP_UID';
        $fieldsClass[$i]['FLD_NULL'] = 'off';
        $fieldsClass[$i]['FLD_KEY'] = 'on';
        $fieldsClass[$i]['FLD_AUTO_INCREMENT'] = 'off';
        $fieldsClass[$i]['FLD_DESCRIPTION'] = '';
        $fieldsClass[$i]['FLD_TYPE'] = 'VARCHAR' ;
        $fieldsClass[$i]['FLD_SIZE'] = 32;
        $i++;
        $fieldsClass[$i]['FLD_NAME'] = 'APP_NUMBER';
        $fieldsClass[$i]['FLD_NULL'] = 'off';
        $fieldsClass[$i]['FLD_KEY'] = 'on';
        $fieldsClass[$i]['FLD_AUTO_INCREMENT'] = 'off';
        $fieldsClass[$i]['FLD_DESCRIPTION'] = '';
        $fieldsClass[$i]['FLD_TYPE'] = 'VARCHAR' ;
        $fieldsClass[$i]['FLD_SIZE'] = 255;

        foreach ($formFields as $sField) {
            $aField = explode('-', $sField);
            if ($aField[1] == 'title' || $aField[1] == 'submit') {
                continue;
            }
            $i++;
            $fieldsClass[$i]['FLD_NAME'] = $aField[0];
            $fieldsClass[$i]['FLD_NULL'] = 'off';
            $fieldsClass[$i]['FLD_KEY'] = 'off';
            $fieldsClass[$i]['FLD_AUTO_INCREMENT'] = 'off';
            $fieldsClass[$i]['FLD_DESCRIPTION'] = '';

            switch ($aField[1]) {
                case 'currency':
                case 'percentage':
                    $sType = 'number';
                    $fieldsClass[$i]['FLD_TYPE'] = 'FLOAT' ;
                    $fieldsClass[$i]['FLD_SIZE'] = 255;
                    break;
                case 'text':
                case 'password':
                case 'dropdown':
                case 'yesno':
                case 'checkbox':
                case 'radiogroup':
                case 'hidden':
                case "link":
                    $sType = 'char';
                    $fieldsClass[$i]['FLD_TYPE'] = 'VARCHAR' ;
                    $fieldsClass[$i]['FLD_SIZE'] = 255;
                    break;
                case 'textarea':
                    $sType = 'text';
                    $fieldsClass[$i]['FLD_TYPE'] = 'TEXT' ;
                    $fieldsClass[$i]['FLD_SIZE'] = '';
                    break;
                case 'date':
                    $sType = 'date';
                    $fieldsClass[$i]['FLD_TYPE'] = 'DATE' ;
                    $fieldsClass[$i]['FLD_SIZE'] = '';
                    break;
                default:
                    $sType = 'char';
                    $fieldsClass[$i]['FLD_TYPE'] = 'VARCHAR' ;
                    $fieldsClass[$i]['FLD_SIZE'] = 255;
                    break;
            }

            $oReportVar->create(array(
                    'REP_TAB_UID' => $RepTabUid,
                    'PRO_UID' => $ProUid,
                    'REP_VAR_NAME' => $aField[0],
                    'REP_VAR_TYPE' => $sType)
            );
            $fields[] = array('sFieldName' => $aField[0], 'sType' => $sType);
        }
        return array($fieldsClass, $fields);
    }
}
