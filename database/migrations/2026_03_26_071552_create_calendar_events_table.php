<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('calendar_events', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('calendar_id');      // 关联行事历，无外键约束
            $table->string('title');                        // 事件标题
            $table->text('description')->nullable();        // 详细描述
            $table->dateTime('start_time');                 // 开始时间
            $table->dateTime('end_time');                   // 结束时间
            $table->boolean('all_day')->default(false);     // 是否为全天事件
            $table->tinyInteger('status')->default(1);      // 状态：1-待办，2-进行中，3-已完成，4-已取消
            $table->tinyInteger('priority')->nullable();    // 优先级：1-低，2-中，3-高
            $table->string('location')->nullable();         // 地点
            $table->string('color', 7)->nullable();         // 自定义颜色（可选）
            $table->json('rrule')->nullable();              // 重复规则（RFC 5545 格式，JSON）
            $table->tinyInteger('permission')->default(1);  // 权限：1-仅自己，2-可查看，3-可编辑等
            $table->timestamps();
            $table->softDeletes();                          // 软删除

            // 索引
            $table->index('calendar_id');
            $table->index('start_time');
            $table->index(['calendar_id', 'start_time']);   // 联合索引，加速按日历查询日期范围
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('calendar_events');
    }
};
