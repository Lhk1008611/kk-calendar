<?php

namespace Database\Seeders;

use App\Models\Calendar;
use App\Models\CalendarEvent;
use Illuminate\Database\Seeder;

class CalendarEventSeeder extends Seeder
{
    public function run()
    {
        // 为每个日历创建 3~10 个事件
        Calendar::all()->each(function ($calendar) {
            CalendarEvent::factory()
                ->count(rand(3, 10))
                ->create(['calendar_id' => $calendar->id]);
        });
}
}
