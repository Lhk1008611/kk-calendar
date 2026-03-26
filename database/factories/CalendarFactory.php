<?php

namespace Database\Factories;

use App\Models\Calendar;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Calendar>
 */
class CalendarFactory extends Factory
{
    protected $model = Calendar::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(), // 关联用户工厂
            'name' => $this->faker->word() . ' 行事历',
            'description' => $this->faker->sentence(),
            'color' => $this->faker->hexColor(),
            'is_default' => false,
            'visibility' => 1,
        ];
    }
}
