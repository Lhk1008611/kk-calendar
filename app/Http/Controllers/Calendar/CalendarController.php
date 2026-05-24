<?php

namespace App\Http\Controllers\Calendar;

use App\Http\Controllers\Controller;
use App\Models\Calendar;
use App\Models\CalendarEvent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CalendarController extends Controller
{
    /**
     * 获取行事历列表（分页、搜索）
     */
    public function get(Request $request)
    {
        $user = $request->user();
        $perPage = $request->get('per_page', 10);
        $keyword = $request->input('keyword',null);

        $query = Calendar::where('user_id', $user->id);
        // 搜索关键词
        if ($keyword) {
            $query->where(function ($q) use ($keyword) {
                $q->where('name', 'like', "%{$keyword}%")
                    ->orWhere('description', 'like', "%{$keyword}%");
            });
        }
        // 每页数量，默认10
        $calendars = $query->orderBy('is_default', 'desc')->paginate($perPage);
        return response()->json($calendars);
    }

    /**
     * 新增行事历
     */
    public function add(Request $request)
    {
        $user = Auth::user();

        $data = $request->validate([
            'name' => 'required|string|max:100',
            'description' => 'nullable|string',
            'color' => 'nullable|string|max:7',
            'is_default' => 'boolean',
            'visibility' => 'integer|in:1,2,3',
        ]);

        // 如果设为默认，需将其他默认日历取消
        if (!empty($data['is_default'])) {
            Calendar::where('user_id', $user->id)->where('is_default', true)->update(['is_default' => false]);
        }

        $calendar = Calendar::create(array_merge($data, ['user_id' => $user->id]));

        return response()->json($calendar, 201);
    }

    /**
     * 删除行事历（支持单条或批量）
     * 前端传递 ids 数组或单个 id
     */
    public function delete(Request $request)
    {
        $user = Auth::user();
        $ids = $request->input('ids', []);

        // 如果 ids 不是数组，尝试转换为数组（兼容单条删除）
        if (!is_array($ids)) {
            $ids = [$ids];
        }

        if (empty($ids)) {
            throw ValidationException::withMessages(['ids' => '请选择要删除的记录']);
        }

        try {
            $deletedCounts = DB::transaction(function () use ($ids, $user) {
                $counts = [];
                // 确保只删除当前用户的日历
                $counts['calendarCount'] = Calendar::where('user_id', $user->id)
                    ->whereIn('id', $ids)
                    ->where('is_default', false)
                    ->delete();
                // 删除日历下的所有事件
                $counts['eventCount'] = CalendarEvent::whereIn('calendar_id', $ids)
                    ->delete();
                return $counts;
            });
        } catch (\Throwable $e) {
            // 记录日志（可选）
            \Log::error('删除日历失败：' . $e->getMessage(), ['exception' => $e]);
            // 返回统一错误响应
            return response()->json([
                'message' => '删除日历失败',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
        $calendarCount = $deletedCounts['calendarCount'];
        $eventCount = $deletedCounts['eventCount'];
        if ($calendarCount === 0) {
            return response()->json(['message' => '没有找到要删除的记录或无权删除'], 404);
        }
        return response()->json(['message' => "成功删除 {$calendarCount} 个日历记录和其中的 {$eventCount} 个事件"]);
    }

    public function update(Request $request, $id)
    {
        $user = Auth::user();
        $calendar = Calendar::where('user_id', $user->id)->findOrFail($id);

        // 如果该日历是默认日历，则不允许修改默认标志为 false；但可以修改其他字段
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'color' => 'nullable|string|max:7',
            'is_default' => 'sometimes|boolean',
            'visibility' => 'sometimes|integer|in:1,2,3',
        ]);

        // 如果要设置为默认日历，则需要清除其他默认日历
        if (!$calendar->is_default){
            if (isset($data['is_default']) && $data['is_default'] === true) {
                Calendar::where('user_id', $user->id)
                    ->where('is_default', true)
                    ->update(['is_default' => false]);
            }
        }
        $calendar->update($data);
        return response()->json($calendar);
    }
}
