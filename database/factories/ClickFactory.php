<?php

namespace Database\Factories;

use App\Click;
use Illuminate\Database\Eloquent\Factories\Factory;

class ClickFactory extends Factory
{
    protected $model = Click::class;

    public function definition(): array
    {
        return [
            'visitor' => $this->faker->md5(),
            'input'   => $this->faker->text(),
            'result'  => (string) $this->faker->randomNumber(6, true),
        ];
    }
}
