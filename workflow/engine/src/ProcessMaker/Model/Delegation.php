<?php

namespace ProcessMaker\Model;

use G;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Delegation extends Model
{
    protected $table = "APP_DELEGATION";

    // We don't have our standard timestamp columns
    public $timestamps = false;

    /**
     * Returns the application this delegation belongs to
     */
    public function application()
    {
        return $this->belongsTo(Application::class, 'APP_UID', 'APP_UID');
    }

    /**
     * Returns the user this delegation belongs to
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'USR_ID', 'USR_ID');
    }

    /**
     * Return the process task this belongs to
     */
    public function task()
    {
        return $this->belongsTo(Task::class, 'TAS_ID', 'TAS_ID');
    }

    /**
     * Return the process this delegation belongs to
     */
    public function process()
    {
        return $this->belongsTo(Process::class, 'PRO_ID', 'PRO_ID');
    }

    /**
     * Searches for delegations which match certain criteria
     *
     * The query is related to advanced search with different filters
     * We can search by process, status of case, category of process, users, delegate date from and to
     *
     * @param integer $userId The USR_ID to search for (Note, this is no longer the USR_UID)
     * @param integer $start for the pagination
     * @param integer $limit for the pagination
     * @param string $search
     * @param integer $process the pro_id
     * @param integer $status of the case
     * @param string $dir if the order is DESC or ASC
     * @param string $sort name of column by sort, can be:
     *        [APP_NUMBER, APP_TITLE, APP_PRO_TITLE, APP_TAS_TITLE, APP_CURRENT_USER, APP_UPDATE_DATE, DEL_DELEGATE_DATE, DEL_TASK_DUE_DATE, APP_STATUS_LABEL]
     * @param string $category uid for the process
     * @param date $dateFrom
     * @param date $dateTo
     * @param string $filterBy name of column for a specific search, can be: [APP_NUMBER, APP_TITLE, TAS_TITLE]
     * @return array $result result of the query
     */

    public static function search(
        $userId = null,
        // Default pagination values
        $start = 0,
        $limit = 25,
        $search = null,
        $process = null,
        $status = null,
        $dir = null,
        $sort = null,
        $category = null,
        $dateFrom = null,
        $dateTo = null,
        $filterBy = 'APP_TITLE'
    ) {
        $search = trim($search);

        // Start the query builder, selecting our base attributes
        $selectColumns = [
            'APPLICATION.APP_NUMBER',
            'APPLICATION.APP_UID',
            'APPLICATION.APP_STATUS',
            'APPLICATION.APP_STATUS AS APP_STATUS_LABEL',
            'APPLICATION.PRO_UID',
            'APPLICATION.APP_CREATE_DATE',
            'APPLICATION.APP_FINISH_DATE',
            'APPLICATION.APP_UPDATE_DATE',
            'APPLICATION.APP_TITLE',
            'APP_DELEGATION.USR_UID',
            'APP_DELEGATION.TAS_UID',
            'APP_DELEGATION.USR_ID',
            'APP_DELEGATION.PRO_ID',
            'APP_DELEGATION.DEL_INDEX',
            'APP_DELEGATION.DEL_LAST_INDEX',
            'APP_DELEGATION.DEL_DELEGATE_DATE',
            'APP_DELEGATION.DEL_INIT_DATE',
            'APP_DELEGATION.DEL_FINISH_DATE',
            'APP_DELEGATION.DEL_TASK_DUE_DATE',
            'APP_DELEGATION.DEL_RISK_DATE',
            'APP_DELEGATION.DEL_THREAD_STATUS',
            'APP_DELEGATION.DEL_PRIORITY',
            'APP_DELEGATION.DEL_DURATION',
            'APP_DELEGATION.DEL_QUEUE_DURATION',
            'APP_DELEGATION.DEL_STARTED',
            'APP_DELEGATION.DEL_DELAY_DURATION',
            'APP_DELEGATION.DEL_FINISHED',
            'APP_DELEGATION.DEL_DELAYED',
            'APP_DELEGATION.DEL_DELAY_DURATION',
            'TASK.TAS_TITLE AS APP_TAS_TITLE',
            'TASK.TAS_TYPE AS APP_TAS_TYPE',
        ];
        $query = DB::table('APP_DELEGATION')->select(DB::raw(implode(',', $selectColumns)));

        // Add join for task, filtering for task title if needed
        // It doesn't make sense for us to search for any delegations that match tasks that are events or web entry
        $query->join('TASK', function ($join) use ($filterBy, $search) {
            $join->on('APP_DELEGATION.TAS_ID', '=', 'TASK.TAS_ID')
                ->whereNotIn('TASK.TAS_TYPE', [
                    'WEBENTRYEVENT',
                    'END-MESSAGE-EVENT',
                    'START-MESSAGE-EVENT',
                    'INTERMEDIATE-THROW',
                ]);
            if ($filterBy == 'TAS_TITLE' && $search) {
                $join->where('TASK.TAS_TITLE', 'LIKE', "%${search}%");
            }
        });

        // Add join for application, taking care of status and filtering if necessary
        $query->join('APPLICATION', function ($join) use ($filterBy, $search, $status, $query) {
            $join->on('APP_DELEGATION.APP_NUMBER', '=', 'APPLICATION.APP_NUMBER');
            if ($filterBy == 'APP_TITLE' && $search) {
                $join->where('APPLICATION.APP_TITLE', 'LIKE', "%${search}%");
            }
            // Based on the below, we can further limit the join so that we have a smaller data set based on join criteria
            switch ($status) {
                case 1: //DRAFT
                    $join->where('APPLICATION.APP_STATUS_ID', 1);
                    break;
                case 2: //TO_DO
                    $join->where('APPLICATION.APP_STATUS_ID', 2);
                    break;
                case 3: //COMPLETED
                    $join->where('APPLICATION.APP_STATUS_ID', 3);
                    break;
                case 4: //CANCELLED
                    $join->where('APPLICATION.APP_STATUS_ID', 4);
                    break;
                case "PAUSED":
                    $join->where('APPLICATION.APP_STATUS', 'TO_DO');
                    break;
                default: //All status
                    // Don't do anything here, we'll need to do the more advanced where below
            }
        });

        // Add join for process, but only for certain scenarios such as category or process
        if (($category && !$process) || $sort == 'APP_PRO_TITLE') {
            $query->join('PROCESS', function ($join) use ($category) {
                $join->on('APP_DELEGATION.PRO_ID', '=', 'PROCESS.PRO_ID');
                if ($category) {
                    $join->where('PROCESS.PRO_CATEGORY', $category);
                }
            });
        }

        // Add join for user, but only for certain scenarios as sorting
        if ($sort == 'APP_CURRENT_USER') {
            $query->join('USERS', function ($join) use ($userId) {
                $join->on('APP_DELEGATION.USR_ID', '=', 'USERS.USR_ID');
            });
        }

        // Search for specified user
        if ($userId) {
            $query->where('APP_DELEGATION.USR_ID', $userId);
        }

        // Search for specified process
        if ($process) {
            $query->where('APP_DELEGATION.PRO_ID', $process);
        }

        // Search for an app/case number
        if ($filterBy == 'APP_NUMBER' && $search) {
            $query->where('APP_DELEGATION.APP_NUMBER', 'LIKE', "%${search}%");
        }

        // Date range filter
        if (!empty($dateFrom)) {
            $query->where('APP_DELEGATION.DEL_DELEGATE_DATE', '>=', $dateFrom);
        }
        if (!empty($dateTo)) {
            $dateTo = $dateTo . " 23:59:59";
            // This is inclusive
            $query->where('APP_DELEGATION.DEL_DELEGATE_DATE', '<=', $dateTo);
        }

        // Status Filter
        // This is tricky, the below behavior is combined with the application join behavior above
        switch ($status) {
            case 1: //DRAFT
                $query->where('APP_DELEGATION.DEL_THREAD_STATUS', 'OPEN');
                break;
            case 2: //TO_DO
                $query->where('APP_DELEGATION.DEL_THREAD_STATUS', 'OPEN');
                break;
            case 3: //COMPLETED
                $query->where('APP_DELEGATION.DEL_LAST_INDEX', 1);
                break;
            case 4: //CANCELLED
                $query->where('APP_DELEGATION.DEL_LAST_INDEX', 1);
                break;
            case "PAUSED":
                // Do nothing, as the app status check for TO_DO is performed in the join above
                break;
            default: //All statuses.
                $query->where(function ($query) {
                    // Check to see if thread status is open
                    $query->where('APP_DELEGATION.DEL_THREAD_STATUS', 'OPEN')
                        ->orWhere(function ($query) {
                            // Or, we make sure if the thread is closed, and it's the last delegation, and if the app is completed or cancelled
                            $query->where('APP_DELEGATION.DEL_THREAD_STATUS', 'CLOSED')
                                ->where('APP_DELEGATION.DEL_LAST_INDEX', 1)
                                ->whereIn('APPLICATION.APP_STATUS_ID', [3, 4]);
                        });
                });
                break;
        }

        // Add any sort if needed
        if($sort) {
            switch ($sort) {
                case 'APP_NUMBER':
                    $query->orderBy('APP_DELEGATION.APP_NUMBER', $dir);
                    break;
                case 'APP_PRO_TITLE':
                    // We can do this because we joined the process table if sorting by it
                    $query->orderBy('PROCESS.PRO_TITLE', $dir);
                    break;
                case 'APP_TAS_TITLE':
                    $query->orderBy('TASK.TAS_TITLE', $dir);
                    break;
                case 'APP_CURRENT_USER':
                    // We can do this because we joined the user table if sorting by it
                    $query->orderBy('USERS.USR_LASTNAME', $dir);
                    $query->orderBy('USERS.USR_FIRSTNAME', $dir);
                    break;
                default:
                    $query->orderBy($sort, $dir);
            }
        }

        // Add pagination to the query
        $query = $query->offset($start)
            ->limit($limit);

        // Fetch results and transform to a laravel collection
        $results = $query->get();

        // Transform with additional data
        $priorities = ['1' => 'VL', '2' => 'L', '3' => 'N', '4' => 'H', '5' => 'VH'];
        $results->transform(function ($item, $key) use ($priorities) {
            // Convert to an array as our results must be an array
            $item = json_decode(json_encode($item), true);
            // If it's assigned, fetch the user
            if($item['USR_ID']) {
                $user = User::where('USR_ID', $item['USR_ID'])->first();
            } else  {
                $user = null;
            }
            $process = Process::where('PRO_ID', $item['PRO_ID'])->first();

            // Rewrite priority string
            if ($item['DEL_PRIORITY']) {
                $item['DEL_PRIORITY'] = G::LoadTranslation("ID_PRIORITY_{$priorities[$item['DEL_PRIORITY']]}");
            }

            // Merge in desired application data
            if ($item['APP_STATUS']) {
                $item['APP_STATUS_LABEL'] = G::LoadTranslation("ID_${item['APP_STATUS']}");
            } else {
                $item['APP_STATUS_LABEL'] = $item['APP_STATUS'];
            }

            // Merge in desired process data
            // Handle situation where the process might not be in the system anymore
            $item['APP_PRO_TITLE'] = $process ? $process->PRO_TITLE : '';

            // Merge in desired user data
            $item['USR_LASTNAME'] = $user ? $user->USR_LASTNAME : '';
            $item['USR_FIRSTNAME'] = $user ? $user->USR_FIRSTNAME : '';
            $item['USR_USERNAME'] = $user ? $user->USR_USERNAME : '';

            //@todo: this section needs to use 'User Name Display Format', currently in the extJs is defined this
            $item["APP_CURRENT_USER"] = $item["USR_LASTNAME"] . ' ' . $item["USR_FIRSTNAME"];

            $item["APPDELCR_APP_TAS_TITLE"] = '';

            $item["USRCR_USR_UID"] = $item["USR_UID"];
            $item["USRCR_USR_FIRSTNAME"] = $item["USR_FIRSTNAME"];
            $item["USRCR_USR_LASTNAME"] = $item["USR_LASTNAME"];
            $item["USRCR_USR_USERNAME"] = $item["USR_USERNAME"];
            $item["APP_OVERDUE_PERCENTAGE"] = '';

            return $item;
        });

        // Remove any empty erroenous data
        $results = $results->filter();

        // Bundle into response array
        $response = [
            // Fake totalCount to show pagination
            'totalCount' => $start + $limit + 1,
            'sql' => $query->toSql(),
            'bindings' => $query->getBindings(),
            'data' => $results->values()->toArray(),
        ];

        return $response;
    }

}
