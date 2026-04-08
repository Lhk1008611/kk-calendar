<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;


class CalendarEvent extends Model
{
    use HasFactory, SoftDeletes;  //引入 HasFactory 使 factories 可用

    protected $fillable = [
        'calendar_id', 'title', 'description', 'start_time', 'end_time',
        'all_day', 'status', 'priority', 'location', 'color', 'rrule', 'rrule_until','permission'
    ];

    protected $casts = [
        'start_time' => 'datetime',
        'end_time' => 'datetime',
        'all_day' => 'boolean',
        'rrule' => 'array',          // 自动转换 JSON 为数组
        'rrule_until' => 'datetime'
    ];

    public function calendar(): BelongsTo
    {
        return $this->belongsTo(Calendar::class);
    }
}
