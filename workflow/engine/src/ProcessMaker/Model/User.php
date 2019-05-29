<?php

namespace ProcessMaker\Model;

use Illuminate\Database\Eloquent\Model;

class User extends Model
{
    protected $table = "USERS";
    // Our custom timestamp columns
    const CREATED_AT = 'USR_CREATE_DATE';
    const UPDATED_AT = 'USR_UPDATE_DATE';

    /**
     * Returns the delegations this user has (all of them)
     */
    public function delegations()
    {
        return $this->hasMany(Delegation::class, 'USR_ID', 'USR_ID');
    }
}
