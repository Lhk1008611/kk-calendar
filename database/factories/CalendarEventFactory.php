<?php

namespace Database\Factories;

use App\Models\Calendar;
use App\Models\CalendarEvent;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\CalendarEvent>
 */
class CalendarEventFactory extends Factory
{
    protected $model = CalendarEvent::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $start = $this->faker->dateTimeBetween('-1 day', '+1 day');
        $end = (clone $start)->modify('+'.rand(1, 4).' hours');

        return [
            'calendar_id' => Calendar::factory(),
            'title' => $this->faker->sentence(3),
            'description' => $this->faker->paragraph(),
            'start_time' => $start,
            'end_time' => $end,
            'all_day' => false,
            'status' => $this->faker->numberBetween(1, 4),
            'priority' => $this->faker->optional()->numberBetween(1, 3),
            'location' => $this->faker->optional()->address(),
            'color' => $this->faker->optional()->hexColor(),
            'rrule' => null,
            'permission' => 1,
        ];
    }

}
