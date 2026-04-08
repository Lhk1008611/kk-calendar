<?php

use App\Models\CalendarEvent;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('calendar_events', function (Blueprint $table) {
            // 添加 rrule_until 字段，类型为 timestamp（可为空，因为不是所有事件都有重复规则）
            $table->timestamp('rrule_until')->nullable()->after('rrule');

            // 可选：添加索引以加速查询（例如 WHERE rrule_until > ...）
            // $table->index('rrule_until');

            // 如果希望同时与 calendar_id 组成复合索引，可以：
            // $table->index(['calendar_id', 'rrule_until']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('calendar_events', function (Blueprint $table) {
            // 删除索引（必须先删除索引才能删除列）
            $table->dropIndex(['rrule_until']);
            // 删除字段
            $table->dropColumn('rrule_until');
        });
    }
};
