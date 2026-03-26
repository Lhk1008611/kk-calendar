<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('calendars', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');          // 关联用户，无外键约束
            $table->string('name', 100);                    // 行事历名称
            $table->text('description')->nullable();        // 描述
            $table->string('color', 7)->nullable();         // 主题色（如 #3174ad）
            $table->boolean('is_default')->default(false);  // 是否为默认行事历
            $table->tinyInteger('visibility')->default(1);  // 可见性：1-仅自己，2-共享，3-公开（未来扩展）
            $table->timestamps();
            $table->softDeletes();                          // 软删除

            // 索引
            $table->index('user_id');
            $table->index(['user_id', 'is_default']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('calendars');
    }
};
