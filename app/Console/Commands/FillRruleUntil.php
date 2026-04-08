<?php

namespace App\Console\Commands;

use App\Models\CalendarEvent;
use Carbon\Carbon;
use Illuminate\Console\Command;

class FillRruleUntil extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'events:fill-rrule-until';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '从 rrule JSON 中提取 until 值，填充到 rrule_until 字段';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('开始填充 rrule_until...');

        // 使用 chunkById 避免内存溢出
        CalendarEvent::whereNotNull('rrule')->whereNull('rrule_until')
            ->chunkById(100, function ($events) {
                foreach ($events as $event) {
                    $rrule = is_string($event->rrule) ? json_decode($event->rrule, true) : $event->rrule;
                    if (isset($rrule['until'])) {
                        // 修改为：
                        $until = Carbon::parse($rrule['until'])->format('Y-m-d H:i:s');
                        $event->rrule_until = $until;
                        $event->saveQuietly(); // 避免触发模型事件
                    }
                }
            });

        $this->info('填充完成！');
    }
}
