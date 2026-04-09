<?php

namespace App\Http\Controllers\Calendar;

use App\Http\Controllers\Controller;
use App\Models\Calendar;
use App\Models\CalendarEvent;
use Carbon\Carbon;
use DateTime;
use Illuminate\Database\Eloquent\Builder;
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
            ->select(['id','color','description','is_default','name'])
            ->where('is_default', true)
            ->first();
        if (!$defaultCalendar) {
            return response()->json([
                'calendar' => null,
                'events' => []
            ]);
        }
        // 获取时间范围参数
        $params = $request->validate([
            'start' => 'required|date',
            'end' => 'required|date|after:start_time',
        ]);
        $start = $params['start'];
        $end = $params['end'];

        $query = CalendarEvent::where('calendar_id', $defaultCalendar->id)
            ->select(['id', 'title', 'start_time', 'end_time', 'all_day', 'color', 'description', 'rrule']);

        if ($start && $end) {
            $query->where(function ($q) use ($start, $end) {
                // 条件1：事件时间与查询范围有重叠
                $q->where('start_time', '<', $end)
                    ->where('end_time', '>', $start);
            })->orWhere(function ($q) use ($start) {
                // 条件2：重复事件且重复结束日期大于查询开始时间
                $q->whereNotNull('rrule')
                    ->where('rrule_until', '>', $start);
            });
        }
        $events = $query->orderBy('id', 'asc')->get();
        return response()->json([
            'calendar' => $defaultCalendar,
            'events' => $events,
        ]);
    }

    /**
     * 获取事件列表（支持分页和搜索）
     */
    public function get(Request $request)
    {
        $user = Auth::user();
        $perPage = $request->input('per_page', 10);
        $keyword = $request->input('keyword',null);

        $query = CalendarEvent::whereHas('calendar', function ($q) use ($user) {
            $q->where('user_id', $user->id);
        });

        // 按标题模糊搜索
        if ($keyword) {
            $query->where('title', 'like', "%{$keyword}%");
        }

        $events = $query->orderBy('id', 'desc')->paginate($perPage);

        return response()->json($events);
    }


    /**
     * 新增事件
     */
    public function add(Request $request)
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
            'color' => 'nullable|string|max:7',
            'rrule' => 'nullable',
        ]);

        if (!empty($data['rrule'])) {
            $data['rrule_until'] = $data['rrule']['until'];
            if (!$data['all_day']){
                $data['rrule']['duration'] = $this->formatDuration($data['end_time'], $data['start_time']);
            }
        }
        $event = CalendarEvent::create($data);
        return response()->json($event, 201);
    }

    /**
     * 更新事件（使用 PATCH 方法）
     * @throws \DateMalformedStringException
     */
    public function update(Request $request, $id)
    {
        $user = Auth::user();

        // 查找事件，并确保属于当前用户
        $event = CalendarEvent::whereHas('calendar', function ($q) use ($user) {
            $q->where('user_id', $user->id);
        })->findOrFail($id, ['id', 'rrule']);

        $data = $request->validate([
            'calendar_id' => [
                'sometimes',
                'integer',
                Rule::exists('calendars', 'id')->where('user_id', $user->id),
            ],
            'title' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'start_time' => 'sometimes|date',
            'end_time' => 'sometimes|date|after_or_equal:start_time',
            'all_day' => 'sometimes|boolean',
            'status' => 'nullable|integer|in:1,2,3,4',
            'priority' => 'nullable|integer|in:1,2,3',
            'location' => 'nullable|string|max:255',
            'color' => 'nullable|string|max:7',
            'rrule' => 'nullable',
            'permission' => 'nullable|integer|in:1,2,3',
        ]);

        if ($event->rrule && empty($data['rrule'])) {
            $data['rrule'] = null;
        }
        if (!empty($data['rrule']) && !$data['all_day']) {
            $data['rrule']['duration'] = $this->formatDuration($data['end_time'], $data['start_time']);
        }

        if ($data['all_day']) {
            $oldDuration = $event->rrule['duration'] ?? [];
            $newDuration = $data['rrule']['duration'] ?? [];
            if (!empty($oldDuration) && !empty($newDuration)) {
                unset($data['rrule']['duration']);
            }
        }

        // 移动重复事件被删除的实例需要同步
        $exdates = $event->rrule['exdate'] ?? [];
        if (!empty($event->rrule['exdate'])) {
            $startDateTime = new DateTime($data['start_time']);
            $startDate = $startDateTime->format('Y-m-d');
            $startTime = $startDateTime->format('H:i:s');
            $newExdates = array_reduce($exdates, function ($arr, $exdate) use ($startDate, $startTime) {
                $exDateTime = new DateTime($exdate);
                if ($exDateTime->format('Y-m-d') == $startDate) return;
                // 解析新时间的小时、分钟、秒
                list($hour, $minute, $second) = explode(':', $startTime);
                $exDateTime->setTime($hour, $minute, $second);
                $arr[] = $exDateTime->format('Y-m-d\TH:i:s\Z'); // 保持 UTC 格式
                return $arr;
            }, []);
            $data['rrule']['exdate'] = $newExdates;
        }

        $event->update($data);

        return response()->json($event);
    }

    /**
     * 删除事件（支持批量删除，传递 ids 数组）
     */
    public function delete(Request $request)
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

    /**
     * 移除单个重复事件实例
     * @throws \DateMalformedStringException
     */
    public function excludeOccurrence(Request $request, $id)
    {
        $event = CalendarEvent::findOrFail($id);
        $date = $request->input('date'); // ISO 8601 字符串
        $allDay = $request->input('all_day');
        $allDayExDate = new DateTime($date)->format('Y-m-d');
        $exdates = $event->rrule['exdate'] ?? [];
        if ($allDay && !in_array($allDayExDate, $exdates)) {
            $exdates[] = $allDayExDate;
        }
        if (!$allDay && !in_array($date, $exdates)) {
            $exdates[] = $date;
        }
        $event->fill(['rrule' => array_merge($event->rrule ?? [], ['exdate' => $exdates])]);
        $event->save();
        return response()->json(['message' => '已排除该实例']);
    }


    /**
     * 格式化开始和结束时间差为 hh:mm
     * @param $start
     * @param $end
     * @return string
     */
    private function formatDuration($start, $end)
    {
        $start = Carbon::parse($start);
        $end = Carbon::parse($end);
        $diffMinutes = $end->diffInMinutes($start); // 总分钟数（绝对值）
        $hours = floor($diffMinutes / 60);
        $minutes = $diffMinutes % 60;
        return sprintf("%02d:%02d", $hours, $minutes);
    }
}
