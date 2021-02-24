<?php
namespace ProcessMaker\Util;
use Exception;
use ProcessMaker\BusinessModel\DynaForm as BusinessModelDynaForm;
use ProcessMaker\BusinessModel\Process;
use PmDynaform;
use ProcessMaker\Validation\ExceptionRestApi;
use Application;
use Cases as ClassesCases;
use AppDelegation;
use G;

class LiteUtil
{
    //protected $lightApiObj;
    public function __construct()
    {
        //$this->$lightApiObj = new Lite();
    }

    public function replaceFields($data, $structure)
    {
        $response = array();
        foreach ($data as $field => $d) {
            $field = preg_quote($field);
            if (is_array($d)) {
                $newData = array();
                foreach ($d as $field => $value) {
                    $field = preg_quote($field);
                    if (
                        preg_match(
                            '/\|(' . $field . ')\|/i',
                            '|' . implode('|', array_keys($structure)) . '|',
                            $arrayMatch
                        ) &&
                        !is_array($structure[$arrayMatch[1]])
                    ) {
                        $newName = $structure[$arrayMatch[1]];
                        $newData[$newName] = is_null($value) ? "" : $value;
                    } else {
                        foreach ($structure as $name => $str) {
                            if (is_array($str) &&
                                preg_match(
                                    '/\|(' . $field . ')\|/i',
                                    '|' . implode('|', array_keys($str)) . '|',
                                    $arrayMatch
                                ) &&
                                !is_array($str[$arrayMatch[1]])
                            ) {
                                $newName = $str[$arrayMatch[1]];
                                $newData[$name][$newName] = is_null($value) ? "" : $value;
                            }
                        }
                    }
                }
                if (count($newData) > 0) {
                    $response[] = $newData;
                }
            } else {
                if (
                    preg_match(
                        '/\|(' . $field . ')\|/i',
                        '|' . implode('|', array_keys($structure)) . '|',
                        $arrayMatch
                    ) &&
                    !is_array($structure[$arrayMatch[1]])
                ) {
                    $newName = $structure[$arrayMatch[1]];
                    $response[$newName] = is_null($d) ? "" : $d;
                } else {
                    foreach ($structure as $name => $str) {
                        if (is_array($str) &&
                            preg_match(
                                '/\|(' . $field . ')\|/i',
                                '|' . implode('|', array_keys($str)) . '|',
                                $arrayMatch
                            ) &&
                            !is_array($str[$arrayMatch[1]])
                        ) {
                            $newName = $str[$arrayMatch[1]];
                            $response[$name][$newName] = is_null($d) ? "" : $d;
                        }
                    }
                }
            }

        }

        return $response;
    }

    public function parserDataDynaForm($data)
    {
        $structure = array(
            'dyn_uid' => 'formId',
            'dyn_title' => 'formTitle',
            'dyn_description' => 'formDescription',
            'dyn_content' => 'formContent',
            'dyn_update_date' => 'formUpdateDate'
        );

        $response = $this->replaceFields($data, $structure);

        return $response;
    }

    public function doGetDynaFormService($prj_uid, $dyn_uid)
    {
        try {
            $dynaForm = new BusinessModelDynaForm();
            $dynaForm->setFormatFieldNameInUppercase(false);
            $_SESSION['PROCESS'] = $prj_uid;
            $response = $dynaForm->getDynaForm($dyn_uid);
            $result = $this->parserDataDynaForm($response);
            $result['formContent'] = (isset($result['formContent']) && $result['formContent'] != null) ? G::json_decode($result['formContent']) : "";

            $pmDynaForm = new PmDynaform(["CURRENT_DYNAFORM" => $dyn_uid]);
            $pmDynaForm->jsonr($result['formContent']);

            return $result;
        } catch (Exception $e) {
            throw (new Exception($e->getMessage()));
        }
    }

    public function addMinimalStepInfo($data, $pro_uid, $app_uid){
        $dyn_uid = $data["conditionalSteps"]["UID"];
        $data['_stepInfo']['formData'] = $this->doGetDynaFormService($pro_uid, $dyn_uid);
        $app = new Application();
        $caseDetails = $app->load($app_uid);
        $caseVars = unserialize($caseDetails["APP_DATA"]);
        $data['_stepInfo']['variables'] = $caseVars;
        return $data;
    }

    public function doGetStepStandalone($app_uid, $reData)
    {
        $pro_uid = $reData["pro_uid"];
        $step_pos = 0;
        $app_index = isset($reData["app_index"]) && !empty($reData["app_index"])? $reData["app_index"] : null;
        $response = array();

        //conditionalSteps
        $oCase = new ClassesCases();
        $oAppDelegate = new AppDelegation();
        $alreadyRouted = $oAppDelegate->alreadyRouted($app_uid, $app_index);
        if ($alreadyRouted) {
            throw (new Exception(G::LoadTranslation('ID_CASE_DELEGATION_ALREADY_CLOSED')));
        }

        do {
            $conditionalSteps = $oCase->getNextStep($pro_uid, $app_uid, $app_index, $step_pos);
            if (is_array($conditionalSteps) && (
                    $conditionalSteps["TYPE"] === "DYNAFORM" ||
                    $conditionalSteps["TYPE"] === "DERIVATION")
            ) {
                break;
            } else {
                $step_pos = $step_pos + 1;
            }
        } while ($conditionalSteps !== false);
        $response["conditionalSteps"] = $conditionalSteps;
        $response = $this->addMinimalStepInfo($response, $pro_uid, $app_uid);
        return $response;
    }
}