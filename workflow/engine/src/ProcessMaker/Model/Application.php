<?php

namespace ProcessMaker\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Application extends Model
{
    protected $table = "APPLICATION";
    // No timestamps
    public $timestamps = false;

    public function delegations()
    {
        return $this->hasMany(Delegation::class, 'APP_UID', 'APP_UID');
    }

    public function parent()
    {
        return $this->hasOne(Application::class, 'APP_PARENT', 'APP_UID');
    }

    public function currentUser()
    {
        return $this->hasOne(User::class, 'APP_CUR_USER', 'USR_UID');
    }

    /**
     * Scope for query to get the application by APP_UID.
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $appUid
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeAppUid($query, $appUid)
    {
        $result = $query->where('APP_UID', '=', $appUid);
        return $result;
    }

    /**
     * Scope for query to get the applications by PRO_UID.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $proUid
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeProUid($query, $proUid)
    {
        $result = $query->where('PRO_UID', '=', $proUid);
        return $result;
    }

    /**
     * Get Applications by PRO_UID, ordered by APP_NUMBER.
     *
     * @param string $proUid
     *
     * @return object
     * @see ReportTables->populateTable()
     */
    public static function getByProUid($proUid)
    {
        $query = Application::query()
            ->select()
            ->proUid($proUid)
            ->orderBy('APP_NUMBER', 'ASC');
        return $query->get();
    }

    /**
     * Get information related to the created case
     *
     * @param string $appUid
     *
     * @return array|bool
     */
    public static function getCase($appUid)
    {
        $query = Application::query()->select(['APP_STATUS', 'APP_INIT_USER']);
        $query->appUid($appUid);
        $result = $query->get()->toArray();
        $firstElement = head($result);

        return $firstElement;
    }
}
