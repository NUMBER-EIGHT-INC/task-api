<?php

namespace Tests\Feature;

use App\Models\Task;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TaskApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_tasks_can_be_listed(): void
    {
        Task::factory()->count(3)->create();

        $this->getJson('/api/tasks')
            ->assertOk()
            ->assertJsonCount(3, 'data');
    }

    public function test_task_can_be_created(): void
    {
        $this->postJson('/api/tasks', ['title' => '研修資料を読む'])
            ->assertCreated()
            ->assertJsonPath('data.title', '研修資料を読む')
            ->assertJsonPath('data.is_done', false);

        $this->assertDatabaseHas('tasks', ['title' => '研修資料を読む']);
    }

    public function test_title_is_required(): void
    {
        $this->postJson('/api/tasks', [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('title');
    }

    public function test_title_must_not_exceed_100_characters(): void
    {
        $this->postJson('/api/tasks', ['title' => str_repeat('あ', 101)])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('title');
    }
    public function test_task_can_be_marked_as_done(): void
    {
        $task = Task::factory()->create(['is_done' => false]);

        $this->patchJson("/api/tasks/{$task->id}", ['is_done' => true])
            ->assertOk()
            ->assertJsonPath('data.is_done', true);
    }

    public function test_task_can_be_deleted(): void
    {
        $task = Task::factory()->create();

        $this->deleteJson("/api/tasks/{$task->id}")->assertNoContent();

        $this->assertModelMissing($task);
    }

    public function test_missing_task_returns_404(): void
    {
        $this->getJson('/api/tasks/99999')
            ->assertNotFound()
            ->assertJsonPath('message', 'リソースが見つかりません。');
    }
}
