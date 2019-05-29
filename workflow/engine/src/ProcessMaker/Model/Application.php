<?php

namespace ProcessMaker\Model;

use Illuminate\Database\Eloquent\Model;

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
}
