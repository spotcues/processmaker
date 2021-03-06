<?php
namespace ProcessMaker\BusinessModel;

use G;
use Exception;
use PmDynaform;
use ProcessMaker\Util\PhpShorthandByte;

class InputDocument
{
    private $arrayFieldDefinition = array(
        "INP_DOC_UID"              => array("type" => "string", "required" => false, "empty" => false, "defaultValues" => array(),                                "fieldNameAux" => "inputDocumentUid"),

        "INP_DOC_TITLE"            => array("type" => "string", "required" => true,  "empty" => false, "defaultValues" => array(),                                "fieldNameAux" => "inputDocumentTitle"),
        "INP_DOC_DESCRIPTION"      => array("type" => "string", "required" => false, "empty" => true,  "defaultValues" => array(),                                "fieldNameAux" => "inputDocumentDescription"),
        "INP_DOC_FORM_NEEDED"      => array("type" => "string", "required" => false, "empty" => false, "defaultValues" => array("VIRTUAL", "REAL", "VREAL"),      "fieldNameAux" => "inputDocumentFormNeeded"),
        "INP_DOC_ORIGINAL"         => array("type" => "string", "required" => false, "empty" => false, "defaultValues" => array("ORIGINAL", "COPY", "COPYLEGAL"), "fieldNameAux" => "inputDocumentOriginal"),
        "INP_DOC_PUBLISHED"        => array("type" => "string", "required" => false, "empty" => false, "defaultValues" => array("PRIVATE", "PUBLIC"),             "fieldNameAux" => "inputDocumentPublished"),
        "INP_DOC_VERSIONING"       => array("type" => "int",    "required" => false, "empty" => false, "defaultValues" => array(0, 1),                            "fieldNameAux" => "inputDocumentVersioning"),
        "INP_DOC_DESTINATION_PATH" => array("type" => "string", "required" => false, "empty" => true,  "defaultValues" => array(),                                "fieldNameAux" => "inputDocumentDestinationPath"),
        "INP_DOC_TAGS"             => array("type" => "string", "required" => false, "empty" => true,  "defaultValues" => array(),                                "fieldNameAux" => "inputDocumentTags"),		
        "INP_DOC_TYPE_FILE"             => array("type" => "string", "required" => true, "empty" => false,  "defaultValues" => array(),                                "fieldNameAux" => "inputDocumentTypeFile"),
        "INP_DOC_MAX_FILESIZE"             => array("type" => "int", "required" => true, "empty" => false,  "defaultValues" => array(),                                "fieldNameAux" => "inputDocumentMaxFilesize"),
        "INP_DOC_MAX_FILESIZE_UNIT"             => array("type" => "string", "required" => true, "empty" => false,  "defaultValues" => array(),                                "fieldNameAux" => "inputDocumentMaxFilesizeUnit")
    );

    private $formatFieldNameInUppercase = true;

    private $arrayFieldNameForException = array(
        "processUid" => "PRO_UID"
    );

    /**
     * Constructor of the class
     *
     * return void
     */
    public function __construct()
    {
        try {
            foreach ($this->arrayFieldDefinition as $key => $value) {
                $this->arrayFieldNameForException[$value["fieldNameAux"]] = $key;
            }
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * Set the format of the fields name (uppercase, lowercase)
     *
     * @param bool $flag Value that set the format
     *
     * return void
     */
    public function setFormatFieldNameInUppercase($flag)
    {
        try {
            $this->formatFieldNameInUppercase = $flag;

            $this->setArrayFieldNameForException($this->arrayFieldNameForException);
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * Set exception messages for fields
     *
     * @param array $arrayData Data with the fields
     *
     * return void
     */
    public function setArrayFieldNameForException($arrayData)
    {
        try {
            foreach ($arrayData as $key => $value) {
                $this->arrayFieldNameForException[$key] = $this->getFieldNameByFormatFieldName($value);
            }
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * Get the name of the field according to the format
     *
     * @param string $fieldName Field name
     *
     * return string Return the field name according the format
     */
    public function getFieldNameByFormatFieldName($fieldName)
    {
        try {
            return ($this->formatFieldNameInUppercase)? strtoupper($fieldName) : strtolower($fieldName);
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * Verify if exists the title of a InputDocument
     *
     * @param string $processUid              Unique id of Process
     * @param string $inputDocumentTitle      Title
     * @param string $inputDocumentUidExclude Unique id of InputDocument to exclude
     *
     * return bool Return true if exists the title of a InputDocument, false otherwise
     */
    public function existsTitle($processUid, $inputDocumentTitle, $inputDocumentUidExclude = "")
    {
        try {
            $delimiter = \DBAdapter::getStringDelimiter();

            $criteria = new \Criteria("workflow");

            $criteria->addSelectColumn(\InputDocumentPeer::INP_DOC_UID);
            $criteria->addSelectColumn(\InputDocumentPeer::INP_DOC_TITLE);
            $criteria->add(\InputDocumentPeer::PRO_UID, $processUid, \Criteria::EQUAL);

            if ($inputDocumentUidExclude != "") {
                $criteria->add(\InputDocumentPeer::INP_DOC_UID, $inputDocumentUidExclude, \Criteria::NOT_EQUAL);
            }

            $criteria->add(\InputDocumentPeer::INP_DOC_TITLE, $inputDocumentTitle, \Criteria::EQUAL);

            $rsCriteria = \InputDocumentPeer::doSelectRS($criteria);

            if ($rsCriteria->next()) {
                return true;
            } else {
                return false;
            }
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * Verify if the InputDocument it's assigned in other objects
     *
     * @param string $inputDocumentUid Unique id of InputDocument
     *
     * return array Return array (true if it's assigned or false otherwise and data)
     */
    public function itsAssignedInOtherObjects($inputDocumentUid)
    {
        try {
            $flagAssigned = false;
            $arrayData = array();

            //Step
            $criteria = new \Criteria("workflow");

            $criteria->addSelectColumn(\StepPeer::STEP_UID);
            $criteria->add(\StepPeer::STEP_TYPE_OBJ, "INPUT_DOCUMENT", \Criteria::EQUAL);
            $criteria->add(\StepPeer::STEP_UID_OBJ, $inputDocumentUid, \Criteria::EQUAL);

            $rsCriteria = \StepPeer::doSelectRS($criteria);

            if ($rsCriteria->next()) {
                $flagAssigned = true;
                $arrayData[] = \G::LoadTranslation("ID_STEPS");
            }

            //StepSupervisor
            $criteria = new \Criteria("workflow");

            $criteria->addSelectColumn(\StepSupervisorPeer::STEP_UID);
            $criteria->add(\StepSupervisorPeer::STEP_TYPE_OBJ, "INPUT_DOCUMENT", \Criteria::EQUAL);
            $criteria->add(\StepSupervisorPeer::STEP_UID_OBJ, $inputDocumentUid, \Criteria::EQUAL);

            $rsCriteria = \StepSupervisorPeer::doSelectRS($criteria);

            if ($rsCriteria->next()) {
                $flagAssigned = true;
                $arrayData[] = \G::LoadTranslation("ID_CASES_MENU_ADMIN");
            }

            //ObjectPermission
            $criteria = new \Criteria("workflow");

            $criteria->addSelectColumn(\ObjectPermissionPeer::OP_UID);
            $criteria->add(\ObjectPermissionPeer::OP_OBJ_TYPE, "INPUT", \Criteria::EQUAL);
            $criteria->add(\ObjectPermissionPeer::OP_OBJ_UID, $inputDocumentUid, \Criteria::EQUAL);

            $rsCriteria = \ObjectPermissionPeer::doSelectRS($criteria);

            if ($rsCriteria->next()) {
                $flagAssigned = true;
                $arrayData[] = \G::LoadTranslation("ID_PROCESS_PERMISSIONS");
            }
            
            //Variables
            $criteria = new \Criteria("workflow");

            $criteria->addSelectColumn(\ProcessVariablesPeer::VAR_UID);
            $criteria->add(\ProcessVariablesPeer::INP_DOC_UID, $inputDocumentUid);

            $rsCriteria = \ProcessVariablesPeer::doSelectRS($criteria);

            if ($rsCriteria->next()) {
                $flagAssigned = true;
                $arrayData[] = \G::LoadTranslation("ID_VARIABLES");
            }

            //Return
            return array($flagAssigned, $arrayData);
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * Verify if doesn't exists the InputDocument in table INPUT_DOCUMENT
     *
     * @param string $inputDocumentUid      Unique id of InputDocument
     * @param string $processUid            Unique id of Process
     * @param string $fieldNameForException Field name for the exception
     *
     * return void Throw exception if doesn't exists the InputDocument in table INPUT_DOCUMENT
     */
    public function throwExceptionIfNotExistsInputDocument($inputDocumentUid, $processUid, $fieldNameForException)
    {
        try {
            $criteria = new \Criteria("workflow");

            $criteria->addSelectColumn(\InputDocumentPeer::INP_DOC_UID);

            if ($processUid != "") {
                $criteria->add(\InputDocumentPeer::PRO_UID, $processUid, \Criteria::EQUAL);
            }

            $criteria->add(\InputDocumentPeer::INP_DOC_UID, $inputDocumentUid, \Criteria::EQUAL);

            $rsCriteria = \InputDocumentPeer::doSelectRS($criteria);

            if (!$rsCriteria->next()) {
                throw new \Exception(\G::LoadTranslation("ID_INPUT_DOCUMENT_DOES_NOT_EXIST", array($fieldNameForException, $inputDocumentUid)));
            }
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * Verify if exists the title of a InputDocument
     *
     * @param string $processUid              Unique id of Process
     * @param string $inputDocumentTitle      Title
     * @param string $fieldNameForException   Field name for the exception
     * @param string $inputDocumentUidExclude Unique id of InputDocument to exclude
     *
     * return void Throw exception if exists the title of a InputDocument
     */
    public function throwExceptionIfExistsTitle($processUid, $inputDocumentTitle, $fieldNameForException, $inputDocumentUidExclude = "")
    {
        try {
            if ($this->existsTitle($processUid, $inputDocumentTitle, $inputDocumentUidExclude)) {
                throw new \Exception(\G::LoadTranslation("ID_INPUT_DOCUMENT_TITLE_ALREADY_EXISTS", array($fieldNameForException, $inputDocumentTitle)));
            }
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * Verify if the InputDocument it's assigned in other objects
     *
     * @param string $inputDocumentUid      Unique id of InputDocument
     * @param string $fieldNameForException Field name for the exception
     *
     * return void Throw exception if the InputDocument it's assigned in other objects
     */
    public function throwExceptionIfItsAssignedInOtherObjects($inputDocumentUid, $fieldNameForException)
    {
        try {
            list($flagAssigned, $arrayData) = $this->itsAssignedInOtherObjects($inputDocumentUid);

            if ($flagAssigned) {
                throw new \Exception(\G::LoadTranslation("ID_INPUT_DOCUMENT_ITS_ASSIGNED", array($fieldNameForException, $inputDocumentUid, implode(", ", $arrayData))));
            }
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * Create InputDocument for a Process
     *
     * @param string $processUid Unique id of Process
     * @param array  $arrayData  Data
     *
     * @return array Return data of the new InputDocument created
     * 
     * @see \ProcessMaker\Services\Api\Project\InputDocument->doPostInputDocument()
     * @link https://wiki.processmaker.com/3.0/Input_Documents#Creating_Input_Documents
     */
    public function create($processUid, $arrayData)
    {
        try {
            $arrayData = array_change_key_case($arrayData, CASE_UPPER);

            unset($arrayData["INP_DOC_UID"]);

            //Verify data
            $process = new \ProcessMaker\BusinessModel\Process();

            $process->throwExceptionIfNotExistsProcess($processUid, $this->arrayFieldNameForException["processUid"]);

            $process->throwExceptionIfDataNotMetFieldDefinition($arrayData, $this->arrayFieldDefinition, $this->arrayFieldNameForException, true);

            $this->throwExceptionIfExistsTitle($processUid, $arrayData["INP_DOC_TITLE"], $this->arrayFieldNameForException["inputDocumentTitle"]);

            //Flags
            $flagDataDestinationPath = (isset($arrayData["INP_DOC_DESTINATION_PATH"]))? 1 : 0;
            $flagDataTags = (isset($arrayData["INP_DOC_TAGS"]))? 1 : 0;

            $this->throwExceptionIfMaximumFileSizeExceed(intval($arrayData["INP_DOC_MAX_FILESIZE"]), $arrayData["INP_DOC_MAX_FILESIZE_UNIT"]);
            
            //Create
            $inputDocument = new \InputDocument();

            $arrayData["PRO_UID"] = $processUid;

            $arrayData["INP_DOC_DESTINATION_PATH"] = ($flagDataDestinationPath == 1)? $arrayData["INP_DOC_DESTINATION_PATH"] : "";
            $arrayData["INP_DOC_TAGS"] = ($flagDataTags == 1)? $arrayData["INP_DOC_TAGS"] : "";

            $inputDocumentUid = $inputDocument->create($arrayData);

            //Return
            unset($arrayData["PRO_UID"]);

            if ($flagDataDestinationPath == 0) {
                unset($arrayData["INP_DOC_DESTINATION_PATH"]);
            }

            if ($flagDataTags == 0) {
                unset($arrayData["INP_DOC_TAGS"]);
            }

            $arrayData = array_merge(array("INP_DOC_UID" => $inputDocumentUid), $arrayData);

            if (!$this->formatFieldNameInUppercase) {
                $arrayData = array_change_key_case($arrayData, CASE_LOWER);
            }

            return $arrayData;
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * Update InputDocument
     *
     * @param string $inputDocumentUid Unique id of InputDocument
     * @param array  $arrayData        Data
     * 
     * @return array Return data of the InputDocument updated
     * 
     * @see \ProcessMaker\Services\Api\Project\InputDocument->doPutInputDocument()
     * @link https://wiki.processmaker.com/3.0/Input_Documents#Creating_Input_Documents
     */
    public function update($inputDocumentUid, $arrayData)
    {
        try {
            $arrayData = array_change_key_case($arrayData, CASE_UPPER);

            //Verify data
            $this->throwExceptionIfNotExistsInputDocument($inputDocumentUid, "", $this->arrayFieldNameForException["inputDocumentUid"]);

            //Load InputDocument
            $inputDocument = new \InputDocument();

            $arrayInputDocumentData = $inputDocument->load($inputDocumentUid);

            $processUid = $arrayInputDocumentData["PRO_UID"];

            //Verify data
            $process = new \ProcessMaker\BusinessModel\Process();

            $process->throwExceptionIfDataNotMetFieldDefinition($arrayData, $this->arrayFieldDefinition, $this->arrayFieldNameForException, false);

            if (isset($arrayData["INP_DOC_TITLE"])) {
                $this->throwExceptionIfExistsTitle($processUid, $arrayData["INP_DOC_TITLE"], $this->arrayFieldNameForException["inputDocumentTitle"], $inputDocumentUid);
            }
            
            $this->throwExceptionIfMaximumFileSizeExceed(intval($arrayData["INP_DOC_MAX_FILESIZE"]), $arrayData["INP_DOC_MAX_FILESIZE_UNIT"]);

            //Update
            $arrayData["INP_DOC_UID"] = $inputDocumentUid;

            $result = $inputDocument->update($arrayData);

            $pmDynaform = new PmDynaform();
            $pmDynaform->synchronizeInputDocument($processUid, $arrayData);

            //Return
            unset($arrayData["INP_DOC_UID"]);

            if (!$this->formatFieldNameInUppercase) {
                $arrayData = array_change_key_case($arrayData, CASE_LOWER);
            }

            return $arrayData;
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * Delete InputDocument
     *
     * @param string $inputDocumentUid Unique id of InputDocument
     *
     * return void
     */
    public function delete($inputDocumentUid)
    {
        try {
            //Verify data
            $this->throwExceptionIfNotExistsInputDocument($inputDocumentUid, "", $this->arrayFieldNameForException["inputDocumentUid"]);

            $this->throwExceptionIfItsAssignedInOtherObjects($inputDocumentUid, $this->arrayFieldNameForException["inputDocumentUid"]);

            //Delete
            //StepSupervisor
            $stepSupervisor = new \StepSupervisor();

            $arrayData = $stepSupervisor->loadInfo($inputDocumentUid);
            $result = $stepSupervisor->remove($arrayData["STEP_UID"]);

            //ObjectPermission
            $objectPermission = new \ObjectPermission();

            $arrayData = $objectPermission->loadInfo($inputDocumentUid);

            if (is_array($arrayData)) {
                $result = $objectPermission->remove($arrayData["OP_UID"]);
            }

            //InputDocument
            $inputDocument = new \InputDocument();

            $result = $inputDocument->remove($inputDocumentUid);

            //Step
            $step = new \Step();

            $step->removeStep("INPUT_DOCUMENT", $inputDocumentUid);

            //ObjectPermission
            $objectPermission = new \ObjectPermission();

            $objectPermission->removeByObject("INPUT", $inputDocumentUid);
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * Get criteria for InputDocument
     *
     * return object
     */
    public function getInputDocumentCriteria()
    {
        try {
            $criteria = new \Criteria("workflow");

            $criteria->addSelectColumn(\InputDocumentPeer::INP_DOC_UID);
            $criteria->addSelectColumn(\InputDocumentPeer::INP_DOC_TITLE);
            $criteria->addSelectColumn(\InputDocumentPeer::INP_DOC_DESCRIPTION);
            $criteria->addSelectColumn(\InputDocumentPeer::INP_DOC_FORM_NEEDED);
            $criteria->addSelectColumn(\InputDocumentPeer::INP_DOC_ORIGINAL);
            $criteria->addSelectColumn(\InputDocumentPeer::INP_DOC_PUBLISHED);
            $criteria->addSelectColumn(\InputDocumentPeer::INP_DOC_VERSIONING);
            $criteria->addSelectColumn(\InputDocumentPeer::INP_DOC_DESTINATION_PATH);
            $criteria->addSelectColumn(\InputDocumentPeer::INP_DOC_TAGS);
            $criteria->addSelectColumn(\InputDocumentPeer::INP_DOC_TYPE_FILE);
            $criteria->addSelectColumn(\InputDocumentPeer::INP_DOC_MAX_FILESIZE);
            $criteria->addSelectColumn(\InputDocumentPeer::INP_DOC_MAX_FILESIZE_UNIT);
            return $criteria;
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * Get data of an InputDocument from a record
     *
     * @param array $record Record
     *
     * return array Return an array with data InputDocument
     */
    public function getInputDocumentDataFromRecord($record)
    {
        try {
            if ($record["INP_DOC_TITLE"] . "" == "") {
                //Load InputDocument
                $inputDocument = new \InputDocument();

                $arrayInputDocumentData = $inputDocument->load($record["INP_DOC_UID"]);

                //There is no transaltion for this Document name, try to get/regenerate the label
                $record["INP_DOC_TITLE"] = $arrayInputDocumentData["INP_DOC_TITLE"];
                $record["INP_DOC_DESCRIPTION"] = $arrayInputDocumentData["INP_DOC_DESCRIPTION"];
            }

            return array(
                $this->getFieldNameByFormatFieldName("INP_DOC_UID")              => $record["INP_DOC_UID"],
                $this->getFieldNameByFormatFieldName("INP_DOC_TITLE")            => $record["INP_DOC_TITLE"],
                $this->getFieldNameByFormatFieldName("INP_DOC_DESCRIPTION")      => $record["INP_DOC_DESCRIPTION"] . "",
                $this->getFieldNameByFormatFieldName("INP_DOC_FORM_NEEDED")      => $record["INP_DOC_FORM_NEEDED"] . "",
                $this->getFieldNameByFormatFieldName("INP_DOC_ORIGINAL")         => $record["INP_DOC_ORIGINAL"] . "",
                $this->getFieldNameByFormatFieldName("INP_DOC_PUBLISHED")        => $record["INP_DOC_PUBLISHED"] . "",
                $this->getFieldNameByFormatFieldName("INP_DOC_VERSIONING")       => (int)($record["INP_DOC_VERSIONING"]),
                $this->getFieldNameByFormatFieldName("INP_DOC_DESTINATION_PATH") => $record["INP_DOC_DESTINATION_PATH"] . "",
                $this->getFieldNameByFormatFieldName("INP_DOC_TAGS")             => $record["INP_DOC_TAGS"] . "",
                $this->getFieldNameByFormatFieldName("INP_DOC_TYPE_FILE")             => $record["INP_DOC_TYPE_FILE"] . "",
                $this->getFieldNameByFormatFieldName("INP_DOC_MAX_FILESIZE")             => (int)($record["INP_DOC_MAX_FILESIZE"]),
                $this->getFieldNameByFormatFieldName("INP_DOC_MAX_FILESIZE_UNIT")             => $record["INP_DOC_MAX_FILESIZE_UNIT"] . ""
            );
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * Get data of an InputDocument
     *
     * @param string $inputDocumentUid Unique id of InputDocument
     *
     * @return array Return an array with data of an InputDocument
     */
    public function getInputDocument($inputDocumentUid)
    {
        try {
            //Verify data
            $this->throwExceptionIfNotExistsInputDocument($inputDocumentUid, "", $this->arrayFieldNameForException["inputDocumentUid"]);

            //Get data
            $criteria = $this->getInputDocumentCriteria();

            $criteria->add(\InputDocumentPeer::INP_DOC_UID, $inputDocumentUid, \Criteria::EQUAL);

            $rsCriteria = \InputDocumentPeer::doSelectRS($criteria);
            $rsCriteria->setFetchmode(\ResultSet::FETCHMODE_ASSOC);

            $rsCriteria->next();

            $row = $rsCriteria->getRow();

            return $this->getInputDocumentDataFromRecord($row);
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * Throw exception if maximum file size exceed to php directives.
     * 
     * @param int $value
     * @param string $unit
     * @throws Exception
     * 
     * @see ProcessMaker\BusinessModel\InputDocument->create()
     * @see ProcessMaker\BusinessModel\InputDocument->update()
     * @link https://wiki.processmaker.com/3.2/Input_Documents
     */
    public function throwExceptionIfMaximumFileSizeExceed($value, $unit)
    {
        //The value of 'INP_DOC_MAX_FILESIZE_UNIT' can only take two values: 'KB'and 'MB'.
        if ($unit === "MB") {
            $value = $value * (1024 ** 2);
        }
        if ($unit === "KB") {
            $value = $value * (1024 ** 1);
        }
        $object = $this->getMaxFileSize();
        if ($object->uploadMaxFileSizeBytes < $value) {
            throw new Exception(G::LoadTranslation("ID_THE_MAXIMUM_VALUE_OF_THIS_FIELD_IS", [$object->uploadMaxFileSize]));
        }
    }

    /**
     * To upload large files, post_max_size value must be larger than upload_max_filesize. 
     * Generally speaking, memory_limit should be larger than post_max_size. When an integer 
     * is used, the value is measured in bytes. The shorthand notation may also be used. 
     * If the size of post data is greater than post_max_size, the $_POST and $_FILES 
     * superglobals are empty.
     * 
     * @return object
     * 
     * @see ProcessMaker\BusinessModel\InputDocument->throwExceptionIfMaximumFileSizeExceed()
     * @link https://wiki.processmaker.com/3.2/Input_Documents
     * @link http://php.net/manual/en/faq.using.php#faq.using.shorthandbytes
     */
    public function getMaxFileSize()
    {
        $phpShorthandByte = new PhpShorthandByte();
        $postMaxSize = ini_get("post_max_size");
        $postMaxSizeBytes = $phpShorthandByte->valueToBytes($postMaxSize);
        $uploadMaxFileSize = ini_get("upload_max_filesize");
        $uploadMaxFileSizeBytes = $phpShorthandByte->valueToBytes($uploadMaxFileSize);
        
        if ($postMaxSizeBytes < $uploadMaxFileSizeBytes) {
            $uploadMaxFileSize = $postMaxSize;
            $uploadMaxFileSizeBytes = $postMaxSizeBytes;
        }

        //according to the acceptance criteria the information is always shown in MBytes
        $uploadMaxFileSizeMBytes = $uploadMaxFileSizeBytes / (1024 ** 2); //conversion constant
        $uploadMaxFileSizeUnit = "MB"; //short processmaker notation, https://wiki.processmaker.com/3.0/File_control#Size_Unity
        $uploadMaxFileSizePhpUnit = "M"; //short php notation, http://php.net/manual/en/faq.using.php#faq.using.shorthandbytes

        $result = [
            "uploadMaxFileSize" => $phpShorthandByte->getFormatBytes($uploadMaxFileSizeMBytes . $uploadMaxFileSizePhpUnit),
            "uploadMaxFileSizeBytes" => $uploadMaxFileSizeBytes,
            "uploadMaxFileSizeMBytes" => $uploadMaxFileSizeMBytes,
            "uploadMaxFileSizeUnit" => $uploadMaxFileSizeUnit
        ];
        return (object) $result;
    }
}

