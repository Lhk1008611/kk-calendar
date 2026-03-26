<?php

namespace App\Http\Controllers\Calendar;

use App\Http\Controllers\Controller;
use App\Models\Calendar;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class CalendarController extends Controller
{
    /**
     * 获取行事历列表（分页、搜索）
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $query = Calendar::where('user_id', $user->id);

        // 搜索关键词
        if ($request->has('keyword') && !empty($request->keyword)) {
            $keyword = $request->keyword;
            $query->where(function ($q) use ($keyword) {
                $q->where('name', 'like', "%{$keyword}%")
                    ->orWhere('description', 'like', "%{$keyword}%");
            });
        }

        // 每页数量，默认10
        $perPage = $request->get('per_page', 10);
        $calendars = $query->orderBy('created_at', 'desc')->paginate($perPage);

        return response()->json($calendars);
    }

    /**
     * 新增行事历
     */
    public function store(Request $request)
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
    public function destroy(Request $request)
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

        // 确保只删除当前用户的日历
        $deletedCount = Calendar::where('user_id', $user->id)
            ->whereIn('id', $ids)
            ->delete();

        if ($deletedCount === 0) {
            return response()->json(['message' => '没有找到要删除的记录或无权删除'], 404);
        }

        // 如果删除了某个默认日历，可能需设置一个新的默认日历（可选）
        // 可以在这里处理逻辑：如果用户没有任何日历了，可以创建默认日历？或者不处理。

        return response()->json(['message' => "成功删除 {$deletedCount} 条记录"]);
    }
}
