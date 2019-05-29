<?php

namespace ProcessMaker\Model;

use Illuminate\Database\Eloquent\Model;

/**
 * Class Process
 * @package ProcessMaker\Model
 *
 * Represents a business process object in the system.
 */
class Process extends Model
{
    // Set our table name
    protected $table = 'PROCESS';
    // Our custom timestamp columns
    const CREATED_AT = 'PRO_CREATE_DATE';
    const UPDATED_AT = 'PRO_UPDATE_DATE';
    /**
     * Retrieve all applications that belong to this process
     */
    public function applications()
    {
        return $this->hasMany(Application::class, 'PRO_ID', 'PRO_ID');
    }

    public function tasks()
    {
        return $this->hasMany(Task::class, 'PRO_UID', 'PRO_UID');
    }

    public function creator()
    {
        return $this->hasOne(User::class, 'PRO_CREATE_USER', 'USR_UID');
    }

    public function category()
    {
        return $this->hasOne(ProcessCategory::class, 'PRO_CATEGORY', 'CATEGORY_UID');
    }
}
