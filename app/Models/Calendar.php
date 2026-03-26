<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Calendar extends Model
{
    use HasFactory, SoftDeletes;  //引入 HasFactory 使 factories 可用

    protected $fillable = ['user_id', 'name', 'description', 'color', 'is_default', 'visibility'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function events(): Calendar
    {
        return $this->hasMany(CalendarEvent::class, 'calendar_id');
    }
}
