<?php

namespace Database\Factories;

use App\Click;
use Illuminate\Database\Eloquent\Factories\Factory;

class ClickFactory extends Factory
{
    protected $model = Click::class;

    public function definition()
    {
        return [
            'visitor' => $this->faker->randomNumber(),
            'input' => $this->faker->text(),
            'result' => $this->faker->randomNumber(),
        ];
    }
}
