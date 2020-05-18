<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\Click;
use App\Model;
use Faker\Generator as Faker;

$factory->define(Click::class, function (Faker $faker) {
    return [
        'visitor' => $faker->randomNumber,
        'input' => $faker->text,
        'result' => $faker->randomNumber
    ];
});
