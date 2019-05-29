<?php
use Faker\Generator as Faker;
use ProcessMaker\BusinessModel\Cases as BmCases;

$factory->define(\ProcessMaker\Model\Delegation::class, function(Faker $faker) {
    $app = factory(\ProcessMaker\Model\Application::class)->create();
    $process = \ProcessMaker\Model\Process::where('PRO_UID', $app->PRO_UID)->first();
    $task = $process->tasks->first();

    // Grab a user if random
    $users = \ProcessMaker\Model\User::all();
    if(!count($users)) {
        $user = factory(\ProcessMaker\Model\User::class)->create();
    } else{
        $user = $users->random();
    }
    return [
        'APP_UID' => $app->APP_UID,
        'DEL_INDEX' => 1,
        'APP_NUMBER' => $app->APP_NUMBER,
        'DEL_PREVIOUS' => 0,
        'PRO_UID' => $app->PRO_UID,
        'TAS_UID' => $task->TAS_UID,
        'USR_UID' => $user->USR_UID,
        'DEL_TYPE' => 'NORMAL',
        'DEL_THREAD' => 1,
        'DEL_THREAD_STATUS' => 'OPEN',
        'DEL_PRIORITY' => 3,
        'DEL_DELEGATE_DATE' => $faker->dateTime(),
        'DEL_INIT_DATE' => $faker->dateTime(),
        'DEL_TASK_DUE_DATE' => $faker->dateTime(),
        'DEL_RISK_DATE' => $faker->dateTime(),
        'USR_ID' => $user->USR_ID,
        'PRO_ID' => $process->PRO_ID,
        'TAS_ID' => $task->TAS_ID,
        'DEL_DATA' => ''
    ];
});