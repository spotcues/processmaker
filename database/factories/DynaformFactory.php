<?php

/**
 * Model factory for a dynaform.
 */
use Faker\Generator as Faker;

$factory->define(\ProcessMaker\Model\Dynaform::class, function(Faker $faker) {
    $date = $faker->dateTime();
    return [
        'DYN_UID' => G::generateUniqueID(),
        'DYN_ID' => '',
        'DYN_TITLE' => '',
        'DYN_DESCRIPTION' => '',
        'PRO_UID' => function() {
            $process = factory(\ProcessMaker\Model\Process::class)->create();
            return $process->PRO_UID;
        },
        'DYN_TYPE' => 'xmlform',
        'DYN_FILENAME' => '',
        'DYN_CONTENT' => '',
        'DYN_LABEL' => '',
        'DYN_VERSION' => 2,
        'DYN_UPDATE_DATE' => $date->format('Y-m-d H:i:s'),
    ];
});
