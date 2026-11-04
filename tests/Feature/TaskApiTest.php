<?php

namespace Tests\Feature;

use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class TaskApiTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        Sanctum::actingAs($this->user); // このユーザーでログインした状態にする
    }

    public function test_tasks_can_be_listed(): void
    {
        Task::factory()->count(3)->for($this->user)->create();
        Task::factory()->count(2)->create(); // 他人のタスク

        $this->getJson('/api/tasks')
            ->assertOk()
            ->assertJsonCount(3, 'data'); // 自分の3件だけ
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
        $task = Task::factory()->for($this->user)->create(['is_done' => false]);

        $this->patchJson("/api/tasks/{$task->id}", ['is_done' => true])
            ->assertOk()
            ->assertJsonPath('data.is_done', true);
    }

    public function test_task_can_be_deleted(): void
    {
        $task = Task::factory()->for($this->user)->create();

        $this->deleteJson("/api/tasks/{$task->id}")->assertNoContent();

        $this->assertModelMissing($task);
    }

    public function test_missing_task_returns_404(): void
    {
        $this->getJson('/api/tasks/99999')
            ->assertNotFound()
            ->assertJsonPath('message', 'リソースが見つかりません。');
    }

    public function test_cannot_view_other_users_task(): void
    {
        $others = Task::factory()->create();

        $this->getJson("/api/tasks/{$others->id}")->assertForbidden();
    }

    public function test_cannot_delete_other_users_task(): void
    {
        $others = Task::factory()->create();

        $this->deleteJson("/api/tasks/{$others->id}")->assertForbidden();

        $this->assertModelExists($others); // 消されていない
    }
}
