<?php

namespace App\Http\Controllers;

use App\Models\Task;
use Illuminate\Http\Request;
use App\Http\Resources\TaskResource;
use App\Http\Requests\StoreTaskRequest;
use App\Http\Requests\UpdateTaskRequest;
use Illuminate\Support\Facades\Gate;

class TaskController extends Controller
{
    public function index(Request $request)
    {
        $request->validate([
            'is_done' => ['sometimes', 'boolean'],
            'priority' => ['sometimes', 'in:low,medium,high'],
            'sort' => ['sometimes', 'in:due_date,created_at,priority'],
        ]);

        $query = $request->user()->tasks();

        if ($request->has('is_done')) {
            $query->where('is_done', $request->boolean('is_done'));
        }

        if ($request->has('priority')) {
            $query->where('priority', $request->input('priority'));
        }

        if ($request->input('sort') === 'due_date') {
            $query->orderByRaw('due_date IS NULL')->orderBy('due_date');
        } elseif ($request->input('sort') === 'priority') {
            // 高い順(high → medium → low)
            $query->orderByRaw("CASE priority WHEN 'high' THEN 1 WHEN 'medium' THEN 2 ELSE 3 END");
        } else {
            $query->latest();
        }

        return TaskResource::collection($query->paginate(10)->withQueryString());
    }

    public function store(StoreTaskRequest $request)
    {
        // リレーション経由で作成すると user_id が自動で入る
        $task = $request->user()->tasks()->create($request->validated());

        return (new TaskResource($task))->response()->setStatusCode(201);
    }

    public function show(Task $task)
    {
        Gate::authorize('view', $task);

        return new TaskResource($task);
    }

    public function update(UpdateTaskRequest $request, Task $task)
    {
        Gate::authorize('update', $task);

        $task->update($request->validated());

        return new TaskResource($task);
    }

    public function destroy(Task $task)
    {
        Gate::authorize('delete', $task);

        $task->delete();

        return response()->noContent();
    }
}
