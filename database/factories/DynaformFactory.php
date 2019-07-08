<?php

/**
 * Model factory for a dynaform.
 */
use Faker\Generator as Faker;
use ProcessMaker\Model\Dynaform;
use ProcessMaker\Model\Process;

$factory->define(Dynaform::class, function(Faker $faker) {
    $date = $faker->dateTime();
    return [
        'DYN_UID' => G::generateUniqueID(),
        'DYN_ID' => $faker->unique()->numberBetween(1, 10000),
        'DYN_TITLE' => $faker->sentence(2),
        'DYN_DESCRIPTION' => $faker->sentence(5),
        'PRO_UID' => function() {
            $process = factory(Process::class)->create();
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
