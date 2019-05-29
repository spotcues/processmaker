<?php

namespace ProcessMaker\Model;

use Illuminate\Database\Eloquent\Model;

class Task extends Model
{
    protected $table = 'TASK';
    // We do not have create/update timestamps for this table
    public $timestamps = false;

    public function process()
    {
        return $this->belongsTo(Process::class, 'PRO_UID', 'PRO_UID');
    }

    public function delegations()
    {
        return $this->hasMany(Delegation::class, 'TAS_ID', 'TAS_ID');
    }
}
