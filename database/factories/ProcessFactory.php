<?php
/**
 * Model factory for a process
 */
use Faker\Generator as Faker;

$factory->define(\ProcessMaker\Model\Process::class, function(Faker $faker) {
    /**
     * @todo Determine if we need more base columns populated
     */
    $process =  [
        'PRO_UID' => G::generateUniqueID(),
        'PRO_TITLE' => $faker->sentence(3),
        'PRO_DESCRIPTION' => $faker->paragraph(3),
        'PRO_CREATE_USER' => '00000000000000000000000000000001',
        'PRO_DYNAFORMS' => '',
        'PRO_ITEE' => 1,
    ];

    $task1 = factory(\ProcessMaker\Model\Task::class)
        ->create([
            'PRO_UID' => $process['PRO_UID'],
            'TAS_START'=>'TRUE'
        ]);

    $task2 = factory(\ProcessMaker\Model\Task::class)
        ->create([
            'PRO_UID' => $process['PRO_UID'],
        ]);

    //routes
    factory(\ProcessMaker\Model\Route::class)
        ->create([
            'PRO_UID' => $process['PRO_UID'],
            'TAS_UID' => $task2['TAS_UID'],
            'ROU_NEXT_TASK' => '-1',
        ]);

    factory(\ProcessMaker\Model\Route::class)
        ->create([
            'PRO_UID' => $process['PRO_UID'],
            'TAS_UID' => $task1['TAS_UID'],
            'ROU_NEXT_TASK' => $task2['TAS_UID']
        ]);

    //User assignments
    factory(\ProcessMaker\Model\TaskUser::class)
        ->create([
            'TAS_UID' => $task1['TAS_UID'],
            'USR_UID' => \ProcessMaker\Model\User::all()->random()->USR_UID
        ]);

    factory(\ProcessMaker\Model\TaskUser::class)
        ->create([
            'TAS_UID' => $task2['TAS_UID'],
            'USR_UID' => \ProcessMaker\Model\User::all()->random()->USR_UID
        ]);

    return $process;
});