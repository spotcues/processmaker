<?php

namespace ProcessMaker\Model;

use Illuminate\Database\Eloquent\Model;

class User extends Model
{
    protected $table = "USERS";
    protected $primaryKey = 'USR_ID';
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

    /**
     * Return the user this belongs to
     */
    public function groups()
    {
        return $this->belongsTo(GroupUser::class, 'USR_UID', 'USR_UID');
    }

    /**
     * Return the groups from a user
     *
     * @param boolean $usrUid
     *
     * @return array
     */
    public static function getGroups($usrUid)
    {
        return User::find($usrUid)->groups()->get();
    }
}
