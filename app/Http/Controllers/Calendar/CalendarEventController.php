<?php

namespace App\Http\Controllers\Calendar;

use App\Http\Controllers\Controller;
use App\Models\Calendar;
use App\Models\CalendarEvent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class CalendarEventController extends Controller
{
    /**
     * 获取默认日历在指定时间范围内的事件（用于 FullCalendar）
     */
    protected function getDefaultEvents(Request $request)
    {
        $user = Auth::user();
        // 获取默认日历
        $defaultCalendar = Calendar::where('user_id', $user->id)
            ->where('is_default', true)
            ->first();

        if (!$defaultCalendar) {
            return response()->json([
                'calendar' => null,
                'events' => []
            ]);
        }

        // 获取时间范围参数
        $start = $request->input('start');
        $end = $request->input('end');

        $query = CalendarEvent::where('calendar_id', $defaultCalendar->id);

        if ($start && $end) {
            // FullCalendar 传递的是 ISO 日期字符串，需要转换为日期范围
            // 注意：FullCalendar 传递的 start 和 end 是查询范围的开始和结束（通常是该视图的开始和结束日期）
            // 我们需要查询事件时间与 [start, end) 有重叠的事件
            $query->where(function ($q) use ($start, $end) {
                $q->whereBetween('start_time', [$start, $end])
                    ->orWhereBetween('end_time', [$start, $end])
                    ->orWhere(function ($q) use ($start, $end) {
                        $q->where('start_time', '<=', $start)
                            ->where('end_time', '>=', $end);
                    });
            });
        }

        $events = $query->orderBy('start_time', 'asc')->get();

        return response()->json([
            'calendar' => $defaultCalendar,
            'events' => $events,
        ]);
    }

    /**
     * 获取事件列表（支持分页和搜索）
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $query = CalendarEvent::whereHas('calendar', function ($q) use ($user) {
            $q->where('user_id', $user->id);
        });

        // 按标题模糊搜索
        if ($keyword = $request->input('keyword')) {
            $query->where('title', 'like', "%{$keyword}%");
        }

        $perPage = $request->input('per_page', 10);
        $events = $query->orderBy('start_time', 'desc')->paginate($perPage);

        return response()->json($events);
    }



    /**
     * 新增事件（使用 PUT 方法）
     */
    public function store(Request $request)
    {
        $user = Auth::user();

        $data = $request->validate([
            'calendar_id' => [
                'required',
                'integer',
                Rule::exists('calendars', 'id')->where('user_id', $user->id), // 确保日历属于当前用户
            ],
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'start_time' => 'required|date',
            'end_time' => 'required|date|after:start_time',
            'all_day' => 'boolean',
            'status' => 'nullable|integer|in:1,2,3,4',
            'priority' => 'nullable|integer|in:1,2,3',
            'location' => 'nullable|string|max:255',
            'color' => 'nullable|string|max:7',
            'rrule' => 'nullable|json',
            'permission' => 'nullable|integer|in:1,2,3',
        ]);

        $event = CalendarEvent::create($data);

        return response()->json($event, 201);
    }

    /**
     * 更新事件（使用 PATCH 方法）
     */
    public function update(Request $request, $id)
    {
        $user = Auth::user();

        // 查找事件，并确保属于当前用户
        $event = CalendarEvent::whereHas('calendar', function ($q) use ($user) {
            $q->where('user_id', $user->id);
        })->findOrFail($id);

        $data = $request->validate([
            'calendar_id' => [
                'sometimes',
                'integer',
                Rule::exists('calendars', 'id')->where('user_id', $user->id),
            ],
            'title' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'start_time' => 'sometimes|date',
            'end_time' => 'sometimes|date|after:start_time',
            'all_day' => 'sometimes|boolean',
            'status' => 'nullable|integer|in:1,2,3,4',
            'priority' => 'nullable|integer|in:1,2,3',
            'location' => 'nullable|string|max:255',
            'color' => 'nullable|string|max:7',
            'rrule' => 'nullable|json',
            'permission' => 'nullable|integer|in:1,2,3',
        ]);

        $event->update($data);

        return response()->json($event);
    }

    /**
     * 删除事件（支持批量删除，传递 ids 数组）
     */
    public function destroy(Request $request)
    {
        $user = Auth::user();

        $ids = $request->input('ids');
        if (!$ids) {
            return response()->json(['message' => 'No IDs provided'], 422);
        }

        $ids = is_array($ids) ? $ids : [$ids];

        // 验证所有事件都属于当前用户
        $count = CalendarEvent::whereHas('calendar', function ($q) use ($user) {
            $q->where('user_id', $user->id);
        })->whereIn('id', $ids)->count();

        if ($count !== count($ids)) {
            return response()->json(['message' => 'Some events not found or unauthorized'], 403);
        }

        CalendarEvent::whereIn('id', $ids)->delete();

        return response()->json(['message' => 'Deleted successfully']);
    }


}
