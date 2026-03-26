<?php

namespace Database\Seeders;

use App\Models\Calendar;
use App\Models\User;
use Illuminate\Database\Seeder;

class CalendarSeeder extends Seeder
{
    public function run()
    {
        // 为每个用户创建 1~3 个行事历
        User::all()->each(function ($user) {
            Calendar::factory()
                ->count(rand(1, 3))
                ->create(['user_id' => $user->id]);
        });

        // 或者直接创建一些测试日历
        Calendar::factory()->count(10)->create();
    }
}
