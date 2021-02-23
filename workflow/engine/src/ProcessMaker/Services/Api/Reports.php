<?php
namespace ProcessMaker\Services\Api;

if (file_exists(dirname(dirname(dirname(dirname(dirname(dirname(dirname(__FILE__))))))) . "/thirdparty/phpexcel/vendor/autoload.php")) {
    require_once dirname(dirname(dirname(dirname(dirname(dirname(dirname(__FILE__))))))) . "/thirdparty/phpexcel/vendor/autoload.php";
}
use Exception;
use G;
use Luracast\Restler\RestException;
use PHPExcel;
use PHPExcel_IOFactory;
use PHPExcel_Settings;
use PHPExcel_CachedObjectStorageFactory;

use ProcessMaker\BusinessModel\Cases as BusinessModelCases;
use ProcessMaker\BusinessModel\Process;
use ProcessMaker\BusinessModel\Validator;
use ProcessMaker\Core\System;
use ProcessMaker\Services\Api;
use ProcessMaker\Util\DateTime;
use RBAC;
use stdclass;
use Illuminate\Support\Facades\Cache;
use ProcessMaker\Util\Logger as ReportsLogger;

use App\Jobs\ReportsEmailEvent;
use App\Jobs\ReportsDashboardEmailEvent;

/**
 *
 * Process Api Controller
 *
 * @protected
 */
class Reports extends Api
{
    private static $arrayFieldIso8601 = [
        // request lists
        'newerThan',
        'oldestthan',
        //return lists
        'date',
        'delegateDate',
        'dueDate',
        'delRiskDate',
        'createDate',
        'updateDate',
        'finishDate',
        'initDate',
        'APP_CREATE_DATE',
        'APP_INIT_DATE',
        'APP_FINISH_DATE',
        'APP_UPDATE_DATE',
        'app_create_date',
        'app_init_date',
        'app_finish_date',
        'app_update_date',
        'DEL_INIT_DATE',
        'DEL_FINISH_DATE',
        'DEL_TASK_DUE_DATE',
        'DEL_RISK_DATE',
        'del_init_date',
        'del_finish_date',
        'del_task_due_date',
        'del_risk_date',
        'date_from',
        'date_to',
        'dateFrom',
        'dateTo',
        'due_by',
        'create_date',
        'completed_on',
        'start_date',
        'taskDeadline',
        "del_delegate_date",
        "note_date",
    ];

    private static $cases = null;

    private static $mongo_connection_worklfow = null;
    private static $mongo_connection_spotcues = null;

    private static $process_status = [
        [
            'process_status_label' => 'In Progress',
            'process_status_value' => 'to_do',
        ],
        [
            'process_status_label' => 'Completed',
            'process_status_value' => 'completed',
        ],
        [
            'process_status_label' => 'Open',
            'process_status_value' => 'unassigned',
        ],
    ];

    private static $process_completion_reports = [
        'ontime' => 'On Time',
        'delayed' => 'Delayed',
        'ontrack' => 'On Track',
        'overdue' => 'Overdue',
        '-'      => '-'
    ];

    private static $log_location = PATH_DATA.'logs/reports_excel_generation.log';
    public $reports_logger = null;

    /**
     * Constructor of the class
     *
     * return void
     */
    public function __construct()
    {
        try {
            /**
             * The variable $RBAC can be defined as follows:
             *
             * $RBAC = new stdclass();
             * $RBAC->aUserInfo['USER_INFO'] = ["USR_UID" => $this->getUserId()];
             *
             * Please consider removing the use of this variable in model class,
             * or perform a corresponding improvement.
             */
            global $RBAC;
            if (!isset($RBAC)) {
                $RBAC          = RBAC::getSingleton(PATH_DATA, session_id());
                $RBAC->sSystem = 'PROCESSMAKER';
                $RBAC->initRBAC();
                $RBAC->loadUserRolePermission($RBAC->sSystem, $this->getUserId());
            }
            if (self::$cases === null) {
                self::$cases = BusinessModelCases::getSingleton();
            }

            if ($this->reports_logger===null) {
                $this->reports_logger = new ReportsLogger(self::$log_location);
            }
        } catch (Exception $e) {
            throw new RestException(Api::STAT_APP_EXCEPTION, $e->getMessage());
        }
    }

    public function __destruct()
    {
        self::$arrayFieldIso8601  = null;
        self::$cases              = null;
    }

    public function convertTimeZone($data, array $arrayKey = [], $format = 'Y-m-d H:i:s')
    {
        try {
            foreach ($data as $key => &$value) {
                if (in_array($key, $arrayKey)) {
                    if (!empty($value)) {
                        $dt = new \DateTime($value, new \DateTimeZone('UTC'));
                        $dt->setTimeZone(new \DateTimeZone('Asia/Calcutta'));
                        $value = $dt->format($format);
                    } else {
                        $value = null;
                    }
                }
            }
            return $data;
        } catch (\Exception $e) {
            throw $e;
        }
    }

    public function formatDateTimeToUtc($data, array $arrayKey = [], $format = 'Y-m-d H:i:s')
    {
        try {
            foreach ($data as $key => &$value) {
                if (in_array($key, $arrayKey)) {
                    if (!empty($value)) {
                        $dt    = new \DateTime($value);
                        $value = $dt->format($format);
                    } else {
                        $value = null;
                    }
                }
            }
            return $data;
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /*
     *
     *
     *
     *
     *
     *
     *
     *
     *
     *
     * Reports Version 2.0
     *
     *
     *
     *
     *
     *
     *
     */

    /**
     *
     * @url GET /getAllProcessesVersion2
     * @author  Pawan Saxena <pawan@spotcues.com>
     */
    public function getAllProcessesVersion2(
        $start = 0,
        $limit = 18446744073709551615
    ) {
        try {
            $dataList['start'] = $start;
            $dataList['limit'] = $limit;
            Validator::throwExceptionIfDataNotMetIso8601Format($dataList, self::$arrayFieldIso8601);
            $dataList        = $this->formatDateTimeToUtc($dataList, self::$arrayFieldIso8601);
            return $response = self::$cases->getAllProcessesVersion2($dataList);
        } catch (Exception $e) {
            throw new RestException(Api::STAT_APP_EXCEPTION, $e->getMessage());
        }
    }

    /**
     *
     * @url GET /getCaseDetailsVersion2
     * @author  Pawan Saxena <pawan@spotcues.com>
     */
    public function getCaseDetailsVersion2(
        $start = 0,
        $limit = 10,
        $date_from = '',
        $date_to = '',
        $process = '',
        $search = '',
        $task = '',
        $owner_id = '',
        $status = '',
        $completion = '',
        $current_step = '',
        $is_custom_attributes_honoured = false,
        $assignee_list = '',
        $case_id = ''
    ) {
        if (empty($process)) {
            throw new RestException(Api::STAT_APP_EXCEPTION, 'process cannot be empty');
        }
        try {
            $dataList['start']                         = $start;
            $dataList['limit']                         = $limit;
            $dataList['dateFrom']                      = $date_from;
            $dataList['dateTo']                        = $date_to;
            $dataList['search']                        = trim($search);
            $dataList['status']                        = $status;
            $dataList['completion']                    = $completion;
            $dataList['current_step']                  = $current_step;
            $dataList['is_custom_attributes_honoured'] = $is_custom_attributes_honoured;
            $dataList['process']                       = !empty($process) ? ("'" . implode("', '", array_filter(explode(',', $process))) . "'") : null;
            $dataList['owner_id']                      = !empty($owner_id) ? ("'" . implode("', '", array_filter(explode(',', $owner_id))) . "'") : null;
            $dataList['assignee_list']                 = !empty($assignee_list) ? ("'" . implode("', '", array_filter(explode(',', $assignee_list))) . "'") : null;
            $dataList['task']                          = !empty($task) ? ("'" . implode("', '", array_filter(explode(',', $task))) . "'") : null;
            $dataList['case_id']                       = !empty($case_id) ? ("'" . implode("', '", array_filter(explode(',', $case_id))) . "'") : null;
            Validator::throwExceptionIfDataNotMetIso8601Format($dataList, self::$arrayFieldIso8601);
            $dataList = $this->formatDateTimeToUtc($dataList, self::$arrayFieldIso8601);
            $response = self::$cases->getCaseDetailsVersion2($dataList);
            
            if (empty($owner_id) && (count($response['case_details']) > 0) && $is_custom_attributes_honoured) {
                $response['case_details']            = [];
                $response['case_details_total_rows'] = 0;
            }
            return $response;
        } catch (Exception $e) {
            throw new RestException(Api::STAT_APP_EXCEPTION, $e->getMessage());
        }
    }

    public function getCaseDetailsVersion2ExcelData(
        $start = 0,
        $limit = 10,
        $date_from = '',
        $date_to = '',
        $process = '',
        $search = '',
        $task = '',
        $owner_id = '',
        $status = '',
        $completion = '',
        $current_step = '',
        $is_custom_attributes_honoured = false,
        $assignee_list = '',
        $case_id = ''
    ) {
        if (empty($process)) {
            throw new RestException(Api::STAT_APP_EXCEPTION, 'process cannot be empty');
        }
        try {
            $dataList['start']                         = $start;
            $dataList['limit']                         = $limit;
            $dataList['dateFrom']                      = $date_from;
            $dataList['dateTo']                        = $date_to;
            $dataList['search']                        = trim($search);
            $dataList['status']                        = $status;
            $dataList['completion']                    = $completion;
            $dataList['current_step']                  = $current_step;
            $dataList['is_custom_attributes_honoured'] = $is_custom_attributes_honoured;
            $dataList['process']                       = !empty($process) ? ("'" . implode("', '", array_filter(explode(',', $process))) . "'") : null;
            $dataList['owner_id']                      = !empty($owner_id) ? ("'" . implode("', '", array_filter(explode(',', $owner_id))) . "'") : null;
            $dataList['assignee_list']                 = !empty($assignee_list) ? ("'" . implode("', '", array_filter(explode(',', $assignee_list))) . "'") : null;
            $dataList['task']                          = !empty($task) ? ("'" . implode("', '", array_filter(explode(',', $task))) . "'") : null;
            $dataList['case_id']                       = !empty($case_id) ? ("'" . implode("', '", array_filter(explode(',', $case_id))) . "'") : null;
            Validator::throwExceptionIfDataNotMetIso8601Format($dataList, self::$arrayFieldIso8601);
            $dataList = $this->formatDateTimeToUtc($dataList, self::$arrayFieldIso8601);
            $response = self::$cases->getCaseDetailsVersion2ExcelData($dataList);
            
            if (empty($owner_id) && (count($response['case_details']) > 0) && $is_custom_attributes_honoured) {
                $response['case_details']            = [];
                $response['case_details_total_rows'] = 0;
            }
            return $response;
        } catch (Exception $e) {
            throw new RestException(Api::STAT_APP_EXCEPTION, $e->getMessage());
        }
    }

    /**
     *
     * @url GET /getCaseUserDetailsVersion2
     * @author  Pawan Saxena <pawan@spotcues.com>
     */
    public function getCaseUserDetailsVersion2(
        $start = 0,
        $limit = 10,
        $case_id = '',
        $date_from = '',
        $date_to = '',
        $process = '',
        $search = '',
        $task = '',
        $owner_id = '',
        $status = '',
        $completion = '',
        $current_step = '',
        $is_custom_attributes_honoured = false,
        $assignee_list = ''
    ) {
        if (empty($case_id)) {
            throw new RestException(Api::STAT_APP_EXCEPTION, 'case_id cannot be empty');
        }
        try {
            $dataList['start']   = $start;
            $dataList['limit']   = $limit;
            $dataList['case_id'] = $case_id;
            $dataList['dateFrom']                      = $date_from;
            $dataList['dateTo']                        = $date_to;
            $dataList['search']                        = trim($search);
            $dataList['status']                        = $status;
            $dataList['completion']                    = $completion;
            $dataList['current_step']                  = $current_step;
            $dataList['is_custom_attributes_honoured'] = $is_custom_attributes_honoured;
            $dataList['process']                       = !empty($process) ? ("'" . implode("', '", array_filter(explode(',', $process))) . "'") : null;
            $dataList['owner_id']                      = !empty($owner_id) ? ("'" . implode("', '", array_filter(explode(',', $owner_id))) . "'") : null;
            $dataList['assignee_list']                 = !empty($assignee_list) ? ("'" . implode("', '", array_filter(explode(',', $assignee_list))) . "'") : null;
            $dataList['task']                          = !empty($task) ? ("'" . implode("', '", array_filter(explode(',', $task))) . "'") : null;
            Validator::throwExceptionIfDataNotMetIso8601Format($dataList, self::$arrayFieldIso8601);
            $dataList = $this->formatDateTimeToUtc($dataList, self::$arrayFieldIso8601);
            $response = self::$cases->getCaseUserDetailsVersion2($dataList);
            if (empty($owner_id) && (count($response['user_details']) > 0) && $is_custom_attributes_honoured) {
                $response['user_details']            = [];
                $response['user_details_total_rows'] = 0;
                $response['case_summary'] = null;
            }
            return $response;
        } catch (Exception $e) {
            throw new RestException(Api::STAT_APP_EXCEPTION, $e->getMessage());
        }
    }

    public function getCaseUserDetailsVersion2ExcelData(
        $start = 0,
        $limit = 10,
        $case_id = '',
        $date_from = '',
        $date_to = '',
        $process = '',
        $search = '',
        $task = '',
        $owner_id = '',
        $status = '',
        $completion = '',
        $current_step = '',
        $is_custom_attributes_honoured = false,
        $assignee_list = ''
    ) {
        if (empty($case_id)) {
            throw new RestException(Api::STAT_APP_EXCEPTION, 'case_id cannot be empty');
        }
        try {
            $dataList['start']   = $start;
            $dataList['limit']   = $limit;
            $dataList['case_id'] = $case_id;
            $dataList['dateFrom']                      = $date_from;
            $dataList['dateTo']                        = $date_to;
            $dataList['search']                        = trim($search);
            $dataList['status']                        = $status;
            $dataList['completion']                    = $completion;
            $dataList['current_step']                  = $current_step;
            $dataList['is_custom_attributes_honoured'] = $is_custom_attributes_honoured;
            $dataList['process']                       = !empty($process) ? ("'" . implode("', '", array_filter(explode(',', $process))) . "'") : null;
            $dataList['owner_id']                      = !empty($owner_id) ? ("'" . implode("', '", array_filter(explode(',', $owner_id))) . "'") : null;
            $dataList['assignee_list']                 = !empty($assignee_list) ? ("'" . implode("', '", array_filter(explode(',', $assignee_list))) . "'") : null;
            $dataList['task']                          = !empty($task) ? ("'" . implode("', '", array_filter(explode(',', $task))) . "'") : null;
            Validator::throwExceptionIfDataNotMetIso8601Format($dataList, self::$arrayFieldIso8601);
            $dataList = $this->formatDateTimeToUtc($dataList, self::$arrayFieldIso8601);
            $response = self::$cases->getCaseUserDetailsVersion2ExcelData($dataList);
            if (empty($owner_id) && (count($response['user_details']) > 0) && $is_custom_attributes_honoured) {
                $response['user_details']            = [];
                $response['user_details_total_rows'] = 0;
            }
            return $response;
        } catch (Exception $e) {
            throw new RestException(Api::STAT_APP_EXCEPTION, $e->getMessage());
        }
    }

    /**
     *
     * @url GET /getFormDetailsVersion2
     * @author  Pawan Saxena <pawan@spotcues.com>
     */
    public function getFormDetailsVersion2(
        $start = 0,
        $limit = 10,
        $date_from = '',
        $date_to = '',
        $process = '',
        $search = '',
        $task = '',
        $owner_id = '',
        $status = '',
        $completion = '',
        $current_step = '',
        $is_custom_attributes_honoured = false,
        $case_id = '',
        $assignee_list = ''
    ) {
        if (empty($process)) {
            throw new RestException(Api::STAT_APP_EXCEPTION, 'process cannot be empty');
        }
        try {
            $dataList['start']                         = $start;
            $dataList['limit']                         = $limit;
            $dataList['dateFrom']                      = $date_from;
            $dataList['dateTo']                        = $date_to;
            $dataList['search']                        = trim($search);
            $dataList['status']                        = $status;
            $dataList['completion']                    = $completion;
            $dataList['is_custom_attributes_honoured'] = $is_custom_attributes_honoured;
            $dataList['current_step']                  = $current_step;
            $dataList['remove_label']                  = false;
            $dataList['process']                       = !empty($process) ? ("'" . implode("', '", array_filter(explode(',', $process))) . "'") : null;
            $dataList['owner_id']                      = !empty($owner_id) ? ("'" . implode("', '", array_filter(explode(',', $owner_id))) . "'") : null;
            $dataList['assignee_list']                 = !empty($assignee_list) ? ("'" . implode("', '", array_filter(explode(',', $assignee_list))) . "'") : null;
            $dataList['task']                          = !empty($task) ? ("'" . implode("', '", array_filter(explode(',', $task))) . "'") : null;
            $dataList['case_id']                       = !empty($case_id) ? ("'" . implode("', '", array_filter(explode(',', $case_id))) . "'") : null;
            Validator::throwExceptionIfDataNotMetIso8601Format($dataList, self::$arrayFieldIso8601);
            $dataList = $this->formatDateTimeToUtc($dataList, self::$arrayFieldIso8601);
            $response = self::$cases->getFormDetailsVersion2($dataList);
            
            if (isset($response['form_details']) && count($response['form_details']) < 1) {
                $response['form_details']            = [];
                $response['form_details_total_rows'] = 0;
            }

            if (empty($owner_id) && (count($response['form_details']) > 0) && $is_custom_attributes_honoured) {
                $response['form_details']            = [];
                $response['form_details_total_rows'] = 0;
            }

            return $response;
        } catch (Exception $e) {
            throw new RestException(Api::STAT_APP_EXCEPTION, $e->getMessage());
        }
    }

    public function getFormDetailsVersion2ExcelData(
        $start = 0,
        $limit = 10,
        $date_from = '',
        $date_to = '',
        $process = '',
        $search = '',
        $task = '',
        $owner_id = '',
        $status = '',
        $completion = '',
        $current_step = '',
        $is_custom_attributes_honoured = false,
        $case_id = '',
        $assignee_list = ''
    ) {
        if (empty($process)) {
            throw new RestException(Api::STAT_APP_EXCEPTION, 'process cannot be empty');
        }
        try {
            $dataList['start']                         = $start;
            $dataList['limit']                         = $limit;
            $dataList['dateFrom']                      = $date_from;
            $dataList['dateTo']                        = $date_to;
            $dataList['search']                        = trim($search);
            $dataList['status']                        = $status;
            $dataList['completion']                    = $completion;
            $dataList['is_custom_attributes_honoured'] = $is_custom_attributes_honoured;
            $dataList['current_step']                  = $current_step;
            $dataList['remove_label']                  = false;
            $dataList['process']                       = !empty($process) ? ("'" . implode("', '", array_filter(explode(',', $process))) . "'") : null;
            $dataList['owner_id']                      = !empty($owner_id) ? ("'" . implode("', '", array_filter(explode(',', $owner_id))) . "'") : null;
            $dataList['assignee_list']                 = !empty($assignee_list) ? ("'" . implode("', '", array_filter(explode(',', $assignee_list))) . "'") : null;
            $dataList['task']                          = !empty($task) ? ("'" . implode("', '", array_filter(explode(',', $task))) . "'") : null;
            $dataList['case_id']                       = !empty($case_id) ? ("'" . implode("', '", array_filter(explode(',', $case_id))) . "'") : null;
            Validator::throwExceptionIfDataNotMetIso8601Format($dataList, self::$arrayFieldIso8601);
            $dataList = $this->formatDateTimeToUtc($dataList, self::$arrayFieldIso8601);
            $response = self::$cases->getFormDetailsVersion2ExcelData($dataList);
            
            if (isset($response['form_details']) && count($response['form_details']) < 1) {
                $response['form_details']            = [];
            }

            if (empty($owner_id) && (count($response['form_details']) > 0) && $is_custom_attributes_honoured) {
                $response['form_details']            = [];
            }

            return $response;
        } catch (Exception $e) {
            throw new RestException(Api::STAT_APP_EXCEPTION, $e->getMessage());
        }
    }

    /**
     *
     * @url GET /getFormDetailsVersion2Excel
     * @author  Pawan Saxena <pawan@spotcues.com>
     */
    public function getFormDetailsVersion2Excel(
        $start = 0,
        $limit = 18446744073709551615,
        $date_from = '',
        $date_to = '',
        $process = '',
        $search = '',
        $task = '',
        $owner_id = '',
        $status = '',
        $completion = '',
        $current_step = '',
        $is_custom_attributes_honoured = false,
        $case_id = '',
        $assignee_list = '',
        $timezone = 'Asia/Calcutta',
        $send_to_email = '',
        $cc_to_email = '',
        $workspace_name = ''
    ) {
        if(empty($send_to_email) || empty($workspace_name))
            throw new RestException(Api::STAT_APP_EXCEPTION, 'Missing api params');

        $this->reports_logger->clog($this->reports_logger, ' :: excel generation request :: '.json_encode(func_get_args()));
        try {
            $process_details = self::$cases->doGetProcessDetails(trim($process));
            $payload = [
                'start' => $start,
                'limit' => $limit,
                'date_from' => $date_from,
                'date_to' => $date_to,
                'process' => $process,
                'search' => $search,
                'task' => $task,
                'owner_id' => $owner_id,
                'status' => $status,
                'completion' => $completion,
                'current_step' => $current_step,
                'is_custom_attributes_honoured' => $is_custom_attributes_honoured,
                'case_id' => $case_id,
                'assignee_list' => $assignee_list,
                'timezone' => $timezone,
                'send_to_email' => $send_to_email,
                'cc_to_email' => $cc_to_email,
                'pro_title' => $process_details['PRO_TITLE'],
                'workspace_name' => $workspace_name
            ];
            $payload = json_encode($payload);

            ReportsEmailEvent::dispatch($payload)->onQueue('reports_email');
            $this->reports_logger->clog($this->reports_logger, ' :: ReportsEmailEvent dispatched for excel generation request :: '.json_encode(func_get_args()));
            return [
                'success' => true,
                'message' => env('REPORTS_MAIL_MESSAGE', 'Excel generation request successfully queued and it will be sent to ').' '.$send_to_email
            ];
        } catch (Exception $e) {
            throw new RestException(Api::STAT_APP_EXCEPTION, $e->getMessage());
        }
    }

    public function getFormDetailsVersion2ExcelGenerate(
        $start = 0,
        $limit = 18446744073709551615,
        $date_from = '',
        $date_to = '',
        $process = '',
        $search = '',
        $task = '',
        $owner_id = '',
        $status = '',
        $completion = '',
        $current_step = '',
        $is_custom_attributes_honoured = false,
        $case_id = '',
        $assignee_list = '',
        $timezone = 'Asia/Calcutta'
    ) {
        try {
            set_time_limit(env('REPORTS_EXCEL_TIMEOUT', '3600'));
            error_reporting(0);
            ini_set('display_errors', 0);
            ini_set('memory_limit', env('REPORTS_EXCEL_MAX_MEMORY', '2048M'));
            
            $sysConf                 = System::getSystemConfiguration();
            $pmDocumentHost          = $sysConf['pm_server_host'];
            $workspace               = config('system.workspace');
            $caseExcelHeadingMapping = [
                'case_id'      => 'Process Id',
                'case_title'   => 'Process Title',
                'owner'        => 'Initiator',
                'assignee'     => 'Assignee',
                'status'       => 'Status',
                'current_step' => 'Current Step',
                'create_date'  => 'Create Date',
                'due_by'       => 'Due by',
                'completion'   => 'Completion',
                'completed_on' => 'Completed On',
            ];
            $caseUserDetailsExcelHeadingMapping = [
                'assignee'          => 'Assignee',
                'status'            => 'Status',
                'start_date'   => 'Start Date',
                'completion'   => 'Completion',
                'completed_on' => 'Completed On',
            ];
            $formExcelHeadingMapping = [
                'case_id'    => 'Process Id',
                'case_title' => 'Process Title',
                'owner'      => 'Initiator',
                'assignee'   => 'Assigned To',
            ];
            
            $this->reports_logger->clog($this->reports_logger, ' :: ReportsEmailEvent resolving case details from mysql :: '.json_encode(func_get_args()));
            $caseExcelData = $this->getCaseDetailsVersion2ExcelData(
                $start,
                $limit,
                $date_from,
                $date_to,
                $process,
                $search,
                $task,
                $owner_id,
                $status,
                $completion,
                $current_step,
                $is_custom_attributes_honoured,
                $case_id,
                $assignee_list
            );

            $this->reports_logger->clog($this->reports_logger, ' :: ReportsEmailEvent starting excel preparation for case details :: ');
            //Include case details into the excel
            $org_process = @str_replace('\'', '', $process);

            PHPExcel_Settings::setZipClass(PHPExcel_Settings::PCLZIP);
            $cacheSettings = [];
            $cacheMethod = PHPExcel_CachedObjectStorageFactory::cache_in_memory_gzip;
            if ($caseExcelData['case_details_total_rows'] > 200) {
                $cacheMethod = PHPExcel_CachedObjectStorageFactory:: cache_to_discISAM;
            }
            PHPExcel_Settings::setCacheStorageMethod($cacheMethod, $cacheSettings);
            //Get an instance of phpexcel
            $objPHPExcel = new PHPExcel();

            $objPHPExcel->getProperties()->setCreator("Admin")->setLastModifiedBy("Admin")->setSubject("PROCESS_REPORTS_DATA_" . date('d-m-Y'));
            $sheetIndex = 0;
            //The name of the directory that we need to create.
            $directoryName = PATH_DATA_PUBLIC . 'reports';
            //Check if the directory already exists.
            if (!is_dir($directoryName)) {
                //Directory does not exist, so lets create it.
                mkdir($directoryName, 0777, true);
            }

            $excelProTitle           = $title           = $this->sanitizeString(self::$cases->doGetProcessDetails($org_process)['PRO_TITLE']);
            if ($sheetIndex > 0) {
                $objPHPExcel->createSheet();
                $objPHPExcel->setActiveSheetIndex($sheetIndex)->setTitle("Process_Details_$title");
                $objPHPExcel->getActiveSheet()->getStyle('1:1')->getFont()->setBold(true);
            } else {
                $objPHPExcel->setActiveSheetIndex(0)->setTitle("Process_Details_$title");
                $objPHPExcel->getActiveSheet()->getStyle('1:1')->getFont()->setBold(true);
            }

            
            $pmInitiatorUserIds = [];
            $pmAssigneeUserIds  = [];
            
            foreach ($caseExcelData['case_details'] as $key => $value) {
                
                $checkWFSISTask = @unserialize($value['APP_DATA']);
                if (isset($checkWFSISTask['WFS_CASE_TITLE']) && !empty($checkWFSISTask['WFS_CASE_TITLE'])) {
                    $caseExcelData['case_details'][$key]['case_title'] = $checkWFSISTask['WFS_CASE_TITLE'];
                } else {
                    $caseExcelData['case_details'][$key]['case_title'] = isset($value['case_id']) ? $value['case_id'] : 'NA';
                }
                $caseExcelData['case_details'][$key]['APP_DATA'] = null;
                unset($caseExcelData['case_details'][$key]['APP_DATA']);

                if (isset($value['assignee'])) {
                    $caseExcelData['case_details'][$key]['assignee'] = array_filter(explode(',', $value['assignee']));
                }

                if (isset($value['completion'])) {
                    $caseExcelData['case_details'][$key]['completion'] = self::$process_completion_reports[$value['completion']]?:'-';
                }

                $pmInitiatorUserIds[] = $caseExcelData['case_details'][$key]['owner'];
                $pmAssigneeUserIds = array_merge($pmAssigneeUserIds, $caseExcelData['case_details'][$key]['assignee']);
            }

            
            

            $pmInitiatorUserIds = array_unique($pmInitiatorUserIds);
            $pmAssigneeUserIds = array_unique($pmAssigneeUserIds);
            
            
            $pmInitiatorWorkflowUsers = $this->getWorkflowUserDetails(['pmUserIds' => $pmInitiatorUserIds]);
            if($pmAssigneeUserIds)
                $pmAssigneeWorkflowUsers = $this->getWorkflowUserDetails(['pmUserIds' => $pmAssigneeUserIds]);
            else
                $this->reports_logger->clog($this->reports_logger, ' :: Empty Assignee UserIds... ::  ');
            
            $excelHeading            = [];
            $caseExcelOrder          = [];
            $indext                  = 1;
            foreach ($caseExcelHeadingMapping as $key => $value) {
                $excelHeading[]   = $value;
                $caseExcelOrder[] = $key;
            }
            foreach ($pmInitiatorWorkflowUsers['_targetCustomAtrributesIndexedFieldList'] as $key => $value) {
                $excelHeading[]   = 'Initiator ' . $value;
                $caseExcelOrder[] = 'initiator_custom_' . $value;
            }

            $objPHPExcel->getActiveSheet()->fromArray($excelHeading, null, "A$indext");

            $indext += 1;
            

            // Start case details evaluation
            foreach ($caseExcelData['case_details'] as $key => &$item) {
                $app_uid = $item['APP_UID'];
                unset($caseExcelData['case_details'][$key]['APP_UID']);

                $assignee_count = count($item['assignee']);
                foreach ($pmInitiatorWorkflowUsers['_targetCustomAtrributesIndexedFieldList'] as $key => $vx) {
                    $item['initiator_custom_' . $vx] = $pmInitiatorWorkflowUsers['_targetCustomAtrributesFieldList'][$item['owner']]['customUserDetailsFields'][$vx];
                }

                if ($assignee_count > 1) {
                    $item['assignee'] = $assignee_count. ' Users';

                } else {
                    if($item['assignee'] != null)
                        $item['assignee'] = $pmAssigneeWorkflowUsers['_targetCustomAtrributesFieldList'][$item['assignee'][0]]['name'];
                    else
                        $item['assignee'] = 'Unassigned';
                }
                if (isset($item['owner'])) {
                    $item['owner'] = $pmInitiatorWorkflowUsers['_targetCustomAtrributesFieldList'][$item['owner']]['name'];
                }
                $tempfinalData = array_values($item);

                $objPHPExcel->getActiveSheet()->fromArray($tempfinalData, null, "A$indext");
                $this->reports_logger->clog($this->reports_logger, ' :: ReportsEmailEvent finished excel preparation for case details ::  '.$app_uid);

                $drillStartIndex = $indext;
                $indext += 1;

                // Start case drill down details evaluation
                if ($assignee_count > 1) {
                    $this->reports_logger->clog($this->reports_logger, ' :: ReportsEmailEvent mulitple assignee, resolving drill down details :: ');
                    $excelHeading2      = [];
                    $caseExcelOrder2    = [];

                    foreach ($caseUserDetailsExcelHeadingMapping as $key => $value) {
                        $excelHeading2[]   = $value;
                        $caseExcelOrder2[] = $key;
                    }
                    foreach ($pmAssigneeWorkflowUsers['_targetCustomAtrributesIndexedFieldList'] as $key => $value) {
                        $excelHeading2[]   = 'Assignee ' . $value;
                        $caseExcelOrder2[] = 'assignee_custom_' . $value;
                    }
                    for ($i = 0; $i < count($excelHeading2); $i++) {
                        $objPHPExcel->getActiveSheet()->getStyleByColumnAndRow($i, $indext)->getFont()->setBold(true);
                    }

                    $objPHPExcel->getActiveSheet()->fromArray($excelHeading2, null, "A$indext");

                    $drill_down_details = $this->getCaseUserDetailsVersion2ExcelData(
                        $start,
                        $limit,
                        $app_uid
                    );

                    $this->reports_logger->clog($this->reports_logger, ' :: ReportsEmailEvent resolved drill down details trying to prepare drill down excel :: '.$app_uid);
                    $indext += 1;
                    foreach ($drill_down_details['user_details'] as $item) {

                        $item['completion'] = self::$process_completion_reports[$item['completion']];
                        foreach ($pmAssigneeWorkflowUsers['_targetCustomAtrributesIndexedFieldList'] as $key => $vx){
                            $item['assignee_custom_' . $vx] = $pmAssigneeWorkflowUsers['_targetCustomAtrributesFieldList'][$item['assignee']]['customUserDetailsFields'][$vx];
                        }
                        $item['assignee'] = $pmAssigneeWorkflowUsers['_targetCustomAtrributesFieldList'][$item['assignee']]['name'];
                        $tempfinalData2 = array_values($item);
                        $objPHPExcel->getActiveSheet()->fromArray($tempfinalData2, null, "A$indext");
                        $drillEndIndex = $indext;
                        $indext += 1;
                    }
                    $this->reports_logger->clog($this->reports_logger, ' :: ReportsEmailEvent finished drill down details excel :: '.$app_uid);
                    $drill_down_details = null;
                    // Set outline levels
                    for ($row = $drillStartIndex; $row < $drillEndIndex; $row++) {
                        $objPHPExcel->getActiveSheet()
                            ->getRowDimension($row)
                            ->setOutlineLevel(1)
                            ->setVisible(false)
                            ->setCollapsed(true);
                    }
                }
                // End case drill down details evaluation
            }
            // End case details evaluation
            
            $caseExcelData = null;
            $tempfinalData = null;
            $tempfinalData2 = null;
            $drill_down_details = null;
            $caseExcelOrder2 = null;
            $caseExcelOrder = null;
            unset($caseExcelData);
            unset($tempfinalData2);
            unset($item);
            unset($drill_down_details);
            unset($caseExcelOrder2);
            unset($caseExcelOrder);
            unset($excelHeading);
            unset($excelHeading2);
            unset($tempfinalData);
            $sheetIndex++;

            //Start form details evaluation
            $task = $this->getProcessTaskDetailsVersion2(
                $start,
                $limit,
                $process
            );
            foreach ($task['process_steps'] as $key => $value) {
                $title = $this->sanitizeString($value['TAS_TITLE']);
                if ($sheetIndex > 0) {
                    $objPHPExcel->createSheet();
                    $objPHPExcel->setActiveSheetIndex($sheetIndex)->setTitle("Form_Details_$title");
                    $objPHPExcel->getActiveSheet()->getStyle('1:1')->getFont()->setBold(true);
                } else {
                    $objPHPExcel->setActiveSheetIndex(0)->setTitle("Form_Details_$title");
                    $objPHPExcel->getActiveSheet()->getStyle('1:1')->getFont()->setBold(true);
                }
                $this->reports_logger->clog($this->reports_logger, ' :: ReportsEmailEvent resolving form details');
                $response = $this->getFormDetailsVersion2ExcelData(
                    $start,
                    $limit,
                    $date_from,
                    $date_to,
                    $process,
                    $search,
                    $value['TAS_UID'],
                    $owner_id,
                    $status,
                    $completion,
                    $current_step,
                    $is_custom_attributes_honoured,
                    $case_id,
                    $assignee_list
                );

                $formDetails = $response['form_details'];
                $formColumns = $response['columns'];
                $form_assignee_array = array_unique($response['assignee_array']);
                $response['assignee_array'] = null;
                unset($response['assignee_array']);
                $formDetailsPmAssigneeWorkflowUsers = $this->getWorkflowUserDetails(['pmUserIds' => $form_assignee_array]);
                

                $excelHeading   = [];
                $formExcelOrder = [];
                foreach ($formExcelHeadingMapping as $key => $value) {
                    $excelHeading[]   = $value;
                    $formExcelOrder[] = $key;
                }

                foreach ($formColumns as $key12 => $value12) {
                    $excelHeading[]   = $value12['label'] ? $value12['label'] : $key12;
                    $formExcelOrder[] = $key12;
                }

                //$excelHeading   = array_unique($excelHeading);
                //$formExcelOrder = array_unique($formExcelOrder);

                foreach ($pmInitiatorWorkflowUsers['_targetCustomAtrributesIndexedFieldList'] as $key => $valuett) {
                    $excelHeading[]   = 'Initiator ' . $valuett;
                    $formExcelOrder[] = 'initiator_custom_' . $valuett;
                }
                if(!empty($formDetailsPmAssigneeWorkflowUsers))
                {
                    foreach ($formDetailsPmAssigneeWorkflowUsers['_targetCustomAtrributesIndexedFieldList'] as $key => $valueww) {
                        $excelHeading[]   = 'Assignee ' . $valueww;
                        $formExcelOrder[] = 'assignee_custom_' . $valueww;
                    }
                }
                

                $indext = 1;
                $objPHPExcel->getActiveSheet()->fromArray($excelHeading, null, "A$indext");
                $indext += 1;

                for ($i = 0; $i < count($formDetails); $i++) {

                    $formDataArray = $formDetails[$i]['form_data']['data'];

                    
                    foreach ($pmInitiatorWorkflowUsers['_targetCustomAtrributesIndexedFieldList'] as $kkk => $vvv) {
                        $formDataArray['initiator_custom_' . $vvv] = $pmInitiatorWorkflowUsers['_targetCustomAtrributesFieldList'][$formDataArray['owner']]['customUserDetailsFields'][$vvv];
                    }
                    if(!empty($formDetailsPmAssigneeWorkflowUsers))
                    {
                        foreach ($formDetailsPmAssigneeWorkflowUsers['_targetCustomAtrributesIndexedFieldList'] as $kn => $vn) {
                            $formDataArray['assignee_custom_' . $vn] = $formDetailsPmAssigneeWorkflowUsers['_targetCustomAtrributesFieldList'][$formDataArray['assignee']]['customUserDetailsFields'][$vn];
                        }
                    }
                    
                    if (isset($formDataArray['assignee']) && !empty($formDataArray['assignee'])) {
                        $formDataArray['assignee'] = $formDetailsPmAssigneeWorkflowUsers['_targetCustomAtrributesFieldList'][$formDataArray['assignee']]['name'];
                    } else {
                        $formDataArray['assignee'] = 'Unassigned';
                    }
                    if (isset($formDataArray['owner'])) {
                        $formDataArray['owner'] = $pmInitiatorWorkflowUsers['_targetCustomAtrributesFieldList'][$formDataArray['owner']]['name'];
                    }

                    $columns   = $formColumns;
                    $finalData = [];
                    foreach ($formExcelOrder as $headItem) {
                        if (!array_key_exists($headItem, $formDataArray) || empty($formDataArray[$headItem])) {
                            $finalData[$headItem] = '-';
                        } elseif ($columns[$headItem]['dataType'] == 'boolean') {
                            if (is_array($formDataArray[$headItem]) && count($formDataArray[$headItem]) > 1) {
                                $finalData[$headItem] = @implode(',', $formDataArray[$headItem]);
                            } else {
                                $finalData[$headItem] = $formDataArray[$headItem];
                            }
                        } else {
                            $finalData[$headItem] = $formDataArray[$headItem];
                        }
                    }
                    $objPHPExcel->getActiveSheet()->fromArray($finalData, null, "A$indext");
                    $indext += 1;
                }
                $sheetIndex++;
            }
            $this->reports_logger->clog($this->reports_logger, ' :: ReportsEmailEvent finished form details excel. Trying to send mail now.');
            unset($formDataArray);
            unset($finalData);
            unset($columns);
            unset($excelHeading);
            unset($formExcelOrder);
            unset($additionalKeys);
            unset($pmInitiatorWorkflowUsers);
            unset($pmAssigneeWorkflowUsers);
            unset($formDetailsPmAssigneeWorkflowUsers);
            unset($task);
            
            $filename  = "Process_Details-" . preg_replace('/[^a-zA-Z0-9_.]/', '', preg_replace('/\s+/', '_', $excelProTitle)) . '-' . uniqid() . '.xlsx';
            $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
            $objWriter->save(str_replace(__FILE__, $directoryName . '/' . $filename, __FILE__));
            $objPHPExcel->disconnectWorksheets();
            $objPHPExcel->garbageCollect();
            unset($objWriter);
            unset($objPHPExcel);
            $link = "$pmDocumentHost/sys$workspace/en/neoclassic/reports/reports_Download?a=$filename";
            return ['report_excel_link' => $link];
        } catch (\Exception $e) {
            throw new RestException(Api::STAT_APP_EXCEPTION, $e->getMessage());
        }
    }

    /**
     *
     * @url GET /getProcessDashboardDetailsVersion2
     * @author  Pawan Saxena <pawan@spotcues.com>
     */
    public function getProcessDashboardDetailsVersion2(
        $start = 0,
        $limit = 10,
        $date_from = '',
        $date_to = '',
        $process = '',
        $search = '',
        $owner_id = '',
        $is_custom_attributes_honoured = false,
        $assignee_list = ''
    ) {
        try {
            $dataList['start']         = $start;
            $dataList['limit']         = $limit;
            $dataList['dateFrom']      = $date_from;
            $dataList['dateTo']        = $date_to;
            $dataList['search']        = trim($search);
            $dataList['process']       = !empty($process) ? ("'" . implode("', '", array_filter(explode(',', $process))) . "'") : null;
            $dataList['owner_id']      = !empty($owner_id) ? ("'" . implode("', '", array_filter(explode(',', $owner_id))) . "'") : null;
            $dataList['assignee_list'] = !empty($assignee_list) ? ("'" . implode("', '", array_filter(explode(',', $assignee_list))) . "'") : null;
            Validator::throwExceptionIfDataNotMetIso8601Format($dataList, self::$arrayFieldIso8601);
            $dataList = $this->formatDateTimeToUtc($dataList, self::$arrayFieldIso8601);
            $response = self::$cases->getProcessDashboardDetailsVersion2($dataList);
            if (empty($owner_id) && (count($response['data']) > 0) && $is_custom_attributes_honoured) {
                $response['data']            = [];
                $response['total_processes'] = 0;
            }
            return $response;
        } catch (Exception $e) {
            throw new RestException(Api::STAT_APP_EXCEPTION, $e->getMessage());
        }
    }


    /**
     *
     * @url GET /getProcessDashboardDetailsVersion2Excel
     * @author  Pawan Saxena <pawan@spotcues.com>
     */
    public function getProcessDashboardDetailsVersion2Excel(
        $start = 0,
        $limit = 18446744073709551615,
        $date_from = '',
        $date_to = '',
        $process = '',
        $search = '',
        $owner_id = '',
        $is_custom_attributes_honoured = false,
        $assignee_list = '',
        $send_to_email = '',
        $cc_to_email = '',
        $workspace_name = ''
    ) {
        if(empty($send_to_email) || empty($workspace_name))
            throw new RestException(Api::STAT_APP_EXCEPTION, 'Missing api params');

        $this->reports_logger->clog($this->reports_logger, ' :: dashboard excel generation request :: '.json_encode(func_get_args()));
        try {
            $payload = [
                'start' => $start,
                'limit' => $limit,
                'date_from' => $date_from,
                'date_to' => $date_to,
                'process' => $process,
                'search' => $search,
                'owner_id' => $owner_id,
                'is_custom_attributes_honoured' => $is_custom_attributes_honoured,
                'assignee_list' => $assignee_list,
                'send_to_email' => $send_to_email,
                'cc_to_email' => $cc_to_email,
                'workspace_name' => $workspace_name
            ];
            $payload = json_encode($payload);

            ReportsDashboardEmailEvent::dispatch($payload)->onQueue('reports_email');
            $this->reports_logger->clog($this->reports_logger, ' :: ReportsDashboardEmailEvent dispatched for dashboard excel generation request :: '.json_encode(func_get_args()));
            return [
                'success' => true,
                'message' => env('REPORTS_MAIL_MESSAGE', 'Excel generation request successfully queued and it will be sent to ').' '.$send_to_email
            ];
        } catch (Exception $e) {
            throw new RestException(Api::STAT_APP_EXCEPTION, $e->getMessage());
        }
    }


    public function getProcessDashboardDetailsVersion2ExcelGenerate(
        $start = 0,
        $limit = 18446744073709551615,
        $date_from = '',
        $date_to = '',
        $process = '',
        $search = '',
        $owner_id = '',
        $is_custom_attributes_honoured = false,
        $assignee_list = ''
    ) {
        try {
            set_time_limit(env('REPORTS_EXCEL_TIMEOUT', '3600'));
            error_reporting(0);
            ini_set('display_errors', 0);
            ini_set('memory_limit', env('REPORTS_EXCEL_MAX_MEMORY', '2048M'));

            $sysConf        = System::getSystemConfiguration();
            $pmDocumentHost = $sysConf['pm_server_host'];
            $workspace      = config('system.workspace');
            $response       = $this->getProcessDashboardDetailsVersion2(
                $start,
                $limit,
                $date_from,
                $date_to,
                $process,
                $search,
                $owner_id,
                $is_custom_attributes_honoured,
                $assignee_list
            );

            PHPExcel_Settings::setZipClass(PHPExcel_Settings::PCLZIP);
            $cacheSettings = [];
            $cacheMethod = PHPExcel_CachedObjectStorageFactory::cache_in_memory_gzip;
            if ($caseExcelData['case_details_total_rows'] > 200) {
                $cacheMethod = PHPExcel_CachedObjectStorageFactory:: cache_to_discISAM;
            }
            PHPExcel_Settings::setCacheStorageMethod($cacheMethod, $cacheSettings);
            //Get an instance of phpexcel
            $objPHPExcel = new PHPExcel();

            $objPHPExcel->getProperties()->setCreator("Admin")->setLastModifiedBy("Admin")->setSubject("PROCESS_REPORTS_DATA_" . date('d-m-Y'));
            $sheetIndex = 0;
            //The name of the directory that we need to create.
            $directoryName = PATH_DATA_PUBLIC . 'reports';
            //Check if the directory already exists.
            if (!is_dir($directoryName)) {
                //Directory does not exist, so lets create it.
                mkdir($directoryName, 0777, true);
            }

            

            $caseExcelHeadingMapping = [
                'process_id'                    => 'Process Id',
                'process_title'                 => 'Process Name',
                'open_cases'                    => 'In Progress',
                'open_cases_overdue'            => 'Overdue',
                'unassigned_cases'              => 'Open',
                'case_completed'                => 'Completed',
                'case_completed_ontime'         => 'Completed On Time',
                'case_completed_after_due_date' => 'Completed After Due Date',
                'total_cases'                   => 'Process Volume',
            ];
            $caseExcelHeadingRemoveKeys = ['process_id', 'APP_DATA', 'owner_user_id', 'submitted_by_user_id', 'APP_UID', 'PRO_UID', 'task_uid', 'del_index'];
            //Include case details into the excel
            $excelProTitle = $title = "Process_Dashboard_Details";
            if ($sheetIndex > 0) {
                $objPHPExcel->createSheet();
                $objPHPExcel->setActiveSheetIndex($sheetIndex)->setTitle("$title");
                $objPHPExcel->getActiveSheet()->getStyle('1:1')->getFont()->setBold(true);
            } else {
                $objPHPExcel->setActiveSheetIndex(0)->setTitle("$title");
                $objPHPExcel->getActiveSheet()->getStyle('1:1')->getFont()->setBold(true);
            }
            $excelHeading = [];
            foreach ($response['data'] as $key => &$value) {
                foreach ($caseExcelHeadingRemoveKeys as $keyx) {
                    if (isset($value[$keyx])) {
                        unset($value[$keyx]);
                    }
                }
                $excelHeading = @array_unique(@array_merge($excelHeading, @array_keys($value)));
            }
            ksort($excelHeading);
            $caseExcelOrder = $excelHeading;
            for ($i = 0; $i < count($excelHeading); $i++) {
                if (array_key_exists($excelHeading[$i], $caseExcelHeadingMapping)) {
                    $excelHeading[$i] = $caseExcelHeadingMapping[$excelHeading[$i]];
                }
                $objPHPExcel->getActiveSheet()->SetCellValueByColumnAndRow($i, 1, $excelHeading[$i]);
            }
            $indext = 0;
            foreach ($response['data'] as $key => $item) {
                $tempfinalData = [];
                foreach ($caseExcelOrder as $headItem) {
                    if (!isset($item[$headItem]) || empty($item[$headItem])) {
                        $tempfinalData[] = 'NA';
                    } else {
                        $tempfinalData[] = $item[$headItem];
                    }
                }
                $objPHPExcel->getActiveSheet()->fromArray($tempfinalData, null, 'A' . ($indext + 2));
                $indext++;
            }
            $filename  = "Process_Dashboard_Details-" . uniqid() . ".xlsx";
            $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
            $objWriter->save(str_replace(__FILE__, $directoryName . '/' . $filename, __FILE__));
            $objPHPExcel->disconnectWorksheets();
            $objPHPExcel->garbageCollect();
            unset($objWriter);
            unset($objPHPExcel);
            
            $link = "$pmDocumentHost/sys$workspace/en/neoclassic/reports/reports_Download?a=$filename";
            return ['report_excel_link' => $link];
        } catch (Exception $e) {
            throw new RestException(Api::STAT_APP_EXCEPTION, $e->getMessage());
        }
    }

    /**
     * @param  array - ['pmUserIds']
     * @return array - workflowUserDetails
     *
     */
    public function getWorkflowUserDetails($data = [])
    {
        try {
            if (file_exists(dirname(dirname(dirname(dirname(dirname(dirname(dirname(__FILE__))))))) . "/thirdparty/mongo_php_library/vendor/autoload.php")) {
                require_once dirname(dirname(dirname(dirname(dirname(dirname(dirname(__FILE__))))))) . "/thirdparty/mongo_php_library/vendor/autoload.php";
                require_once dirname(dirname(dirname(dirname(dirname(dirname(dirname(__FILE__))))))) . "/thirdparty/mongo_php_library/vendor/mongodb/mongodb/src/functions.php";
            }
            $sysConfig = System::getSystemConfiguration();
            if (self::$mongo_connection_worklfow === null) {
                $mongo_connection_string = isset($sysConfig['DEFAULT_MONGO_CONNECTION_WORKFLOWDB_STRING']) ? $sysConfig['DEFAULT_MONGO_CONNECTION_WORKFLOWDB_STRING'] : 'mongodb://localhost:27021/';
                self::$mongo_connection_worklfow  = $this->getMongoConnection($mongo_connection_string);
            }
            if (self::$mongo_connection_spotcues === null) {
                $mongo_connection_string = isset($sysConfig['DEFAULT_MONGO_CONNECTION_SPOTCUESDB_STRING']) ? $sysConfig['DEFAULT_MONGO_CONNECTION_SPOTCUESDB_STRING'] : 'mongodb://localhost:27021/';
                self::$mongo_connection_spotcues  = $this->getMongoConnection($mongo_connection_string);
            }
            if (empty($sysConfig['MONGO_SPOTCUES_DBNAME']) || empty($sysConfig['MONGO_WORKFLOW_DBNAME'])) {
                throw new RestException(Api::STAT_APP_EXCEPTION, 'mongo MONGO_SPOTCUES_DBNAME/MONGO_WORKFLOW_DBNAME database names missing from .env');
            }
            $MONGO_WORKFLOW_DBNAME                   = $sysConfig['MONGO_WORKFLOW_DBNAME'];
            $MONGO_SPOTCUES_DBNAME                   = $sysConfig['MONGO_SPOTCUES_DBNAME'];
            $sysConf                                 = System::getSystemConfiguration();
            $workflowServerHost                      = $sysConf['workflow_server_host'];
            $pmWorkspaceId                           = config('system.workspace');
            $finalAttributesSet                      = [];
            $_bsonTargetUserList                     = [];
            $_targetUserList                        = [];
            $tempPmUserIdList = [];
            $_targetCustomAtrributesIndexedFieldList = [];
            $usersMatch                              = [
                '$match' => [
                    '_pmUser' => [
                        '$in' => array_values($data['pmUserIds']),
                    ],
                ],
            ];
            $workspaceLookup = [
                '$lookup' => [
                    'from'         => 'workspaces',
                    'localField'   => '_targetWorkspace',
                    'foreignField' => '_targetWorkspace',
                    'as'           => 'workspaces',
                ],
            ];
            $workspaceUnwind = [
                '$unwind' => '$workspaces',
            ];
            $workspaceMatch = [
                '$match' => [
                    'workspaces.workspace' => $pmWorkspaceId,
                ],
            ];
            $project = [
                '$project' => [
                    '_targetUser'                 => 1,
                    '_pmUser'                     => 1,
                    '_id'                         => 0,
                    'workspaces._targetWorkspace' => 1,
                ],
            ];
            $pipeline = [$usersMatch, $workspaceLookup, $workspaceUnwind, $workspaceMatch, $project];
            $cursor   = self::$mongo_connection_worklfow->$MONGO_WORKFLOW_DBNAME->users->aggregate($pipeline);

            foreach ($cursor as $key => $value) {
                $_bsonTargetUserList[]              = new \MongoDB\BSON\ObjectId($value['_targetUser']);
                $_targetUserList[$value['_pmUser']] = $value['_targetUser'];
                $tempPmUserIdList[$value['_targetUser']] = $value['_pmUser'];
                $_targetWorkspace                   = $value['workspaces']->_targetWorkspace;
            }

            $match = [
                '$match' => [
                    '_channel'  => new \MongoDB\BSON\ObjectId($_targetWorkspace),
                    'isIndexed' => true,
                    'deleted' => false,
                ],
            ];
            $project = [
                '$project' => [
                    'fieldName' => 1,
                ],
            ];
            $pipeline = [$match, $project];
            $cursor   = @iterator_to_array(self::$mongo_connection_spotcues->$MONGO_SPOTCUES_DBNAME->customfields->aggregate($pipeline));
            foreach ($cursor as $row) {
                $_targetCustomAtrributesIndexedFieldList[] = $this->sanitizeString($row['fieldName']);
            }

            $_targetCustomAtrributesFieldList = [];
            $match                            = [
                '$match' => [
                    '_user'    => [
                        '$in' => array_values($_bsonTargetUserList),
                    ],
                    '_channel' => new \MongoDB\BSON\ObjectId($_targetWorkspace)
                ],
            ];
            $project = [
                '$project' => [
                    'customUserdetails' => 1,
                    '_user'             => 1,
                    '_channel'          => 1,
                    'name'              => 1,
                    '_id'               => 0,
                    '_id'               => 0,
                ],
            ];
            $pipeline = [$match, $project];
            $cursor   = iterator_to_array(self::$mongo_connection_spotcues->$MONGO_SPOTCUES_DBNAME->userchannels->aggregate($pipeline));
            if ($cursor !== null) {
                foreach ($cursor as $index => $row) {
                    $post = $row['_user']->__toString();
                    $indextmp = $tempPmUserIdList[$post];
                    $_targetCustomAtrributesFieldList[$indextmp]['_channel'] = $row['_channel']->__toString();
                    $_targetCustomAtrributesFieldList[$indextmp]['_user']    = $row['_user']->__toString();
                    $_targetCustomAtrributesFieldList[$indextmp]['name']     = $row['name'];
                    if (in_array($row['_user']->__toString(), $_targetUserList)) {
                        $_targetCustomAtrributesFieldList[$indextmp]['_pmUser'] = array_search($row['_user']->__toString(), $_targetUserList);
                    }

                    $tres = [];
                    if (isset($row['customUserdetails']) && count($row['customUserdetails']) > 0) {
                        $temp = iterator_to_array($row['customUserdetails']);
                        foreach ($_targetCustomAtrributesIndexedFieldList as $k23 => $v23) {
                            $putv = '-';
                            foreach ($temp as $putItem) {
                                if($putItem['fieldName'] == $v23)
                                    $putv = $putItem['fieldValue'];
                            }
                            $tres = array_merge($tres, [$v23=> $putv]);
                        }
                    } else {
                        foreach ($_targetCustomAtrributesIndexedFieldList as $k23 => $v23) {
                                    $tres = array_merge($tres, [$v23=> '-']);
                            }
                    }
                    $_targetCustomAtrributesFieldList[$indextmp]['customUserDetailsFields'] = $tres;
                }
            }
            $finalAttributesSet['_targetCustomAtrributesIndexedFieldList'] = $_targetCustomAtrributesIndexedFieldList;
            $finalAttributesSet['_targetCustomAtrributesFieldList']        = $_targetCustomAtrributesFieldList;
            unset($_targetCustomAtrributesFieldList);
            unset($_targetCustomAtrributesIndexedFieldList);
            unset($cursor);
            unset($pipeline);
            return $finalAttributesSet;
        } catch (\Exception $e) {
            throw $e;
        }
    }

    public function sanitizeString($string)
    {
        $string = str_replace(' ', '-', $string); // Replaces all spaces with hyphens.
        $string = preg_replace('/[^A-Za-z0-9\-]/', '', $string); // Removes special chars.
        return preg_replace('/-+/', '-', $string);
    }

    private function getMongoConnection($mongo_connection_string)
    {
        try {
            $mongo_connection = new \MongoDB\Client($mongo_connection_string);
            return $mongo_connection;
        } catch (Exception $e) {
            throw $e;
        }
    }

    /**
     *
     * @url GET /getProcessTaskDetailsVersion2
     * @author  Pawan Saxena <pawan@spotcues.com>
     */
    public function getProcessTaskDetailsVersion2(
        $start = 0,
        $limit = 10,
        $process = ''
    ) {
        try {
            $dataList['start']   = $start;
            $dataList['limit']   = $limit;
            $dataList['process'] = !empty($process) ? ("'" . implode("', '", array_filter(explode(',', $process))) . "'") : null;

            Validator::throwExceptionIfDataNotMetIso8601Format($dataList, $this->arrayFieldIso8601);
            return $response = self::$cases->getProcessTaskDetailsVersion2($dataList);
        } catch (Exception $e) {
            throw new RestException(Api::STAT_APP_EXCEPTION, $e->getTraceAsString());
        }
    }
}
