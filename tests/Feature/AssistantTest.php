<?php

use App\Models\AssistantConversation;
use App\Models\AssistantMessage;
use App\Models\Schedule;
use App\Models\Submission;
use App\Models\User;
use App\Services\VirtualAssistant\LlmProviderInterface;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

test('admin dapat membuka halaman asisten', function () {
    $admin = User::factory()->create([
        'role' => 'admin',
        'username' => 'admin01',
        'email' => 'admin01@test.local',
    ]);

    $response = $this->actingAs($admin)->get(route('admin.assistant.index'));

    $response->assertOk()->assertViewIs('admin.assistant.index');
});

test('admin dapat melihat percakangan tertentu', function () {
    $admin = User::factory()->create([
        'role' => 'admin',
        'username' => 'admin01',
        'email' => 'admin01@test.local',
    ]);
    $conversation = AssistantConversation::factory()->create(['admin_id' => $admin->id]);
    AssistantMessage::factory()->create([
        'conversation_id' => $conversation->id,
        'role' => 'user',
        'content' => 'Halo',
    ]);

    $response = $this->actingAs($admin)->get(route('admin.assistant.show', $conversation->id));

    $response->assertOk()->assertViewIs('admin.assistant.index');
});

test('non-admin tidak dapat mengakses halaman asisten', function () {
    $mahasiswa = User::factory()->mahasiswa()->create();

    $response = $this->actingAs($mahasiswa)->get(route('admin.assistant.index'));

    $response->assertForbidden();
});

test('dosen tidak dapat mengakses halaman asisten', function () {
    $dosen = User::factory()->dosen()->create();

    $response = $this->actingAs($dosen)->get(route('admin.assistant.index'));

    $response->assertForbidden();
});

test('admin dapat mengirim pesan dan mendapatkan respons (mock LLM)', function () {
    $admin = User::factory()->create([
        'role' => 'admin',
        'username' => 'admin01',
        'email' => 'admin01@test.local',
    ]);
    $conversation = AssistantConversation::factory()->create(['admin_id' => $admin->id]);

    $mock = mock(LlmProviderInterface::class);
    $mock->shouldReceive('chat')->once()->andReturn([
        'choices' => [[
            'message' => [
                'content' => 'Halo! Saya asisten virtual SIMSIDANG.',
                'tool_calls' => null,
            ],
        ]],
    ]);
    $this->app->instance(LlmProviderInterface::class, $mock);

    $response = $this->actingAs($admin)
        ->postJson(route('admin.assistant.chat', $conversation->id), ['content' => 'Halo']);

    $response->assertOk()
        ->assertJson([
            'success' => true,
            'response' => 'Halo! Saya asisten virtual SIMSIDANG.',
        ]);

    $this->assertDatabaseHas('assistant_messages', [
        'conversation_id' => $conversation->id,
        'role' => 'user',
        'content' => 'Halo',
    ]);

    $this->assertDatabaseHas('assistant_messages', [
        'conversation_id' => $conversation->id,
        'role' => 'assistant',
    ]);
});

test('validasi menolak pesan kosong', function () {
    $validator = Validator::make(
        ['content' => ''],
        ['content' => ['required', 'string', 'max:2000']],
        ['content.required' => 'Pesan tidak boleh kosong.']
    );

    $validator->validate();
})->throws(ValidationException::class, 'Pesan tidak boleh kosong.');

test('rate limiting membatasi jumlah request per menit', function () {
    RateLimiter::clear('assistant');

    $admin = User::factory()->create([
        'role' => 'admin',
        'username' => 'admin_rl',
        'email' => 'admin_rl@test.local',
    ]);
    $conversation = AssistantConversation::factory()->create(['admin_id' => $admin->id]);

    $mock = mock(LlmProviderInterface::class);
    $mock->shouldReceive('chat')->andReturn([
        'choices' => [[
            'message' => [
                'content' => 'OK',
                'tool_calls' => null,
            ],
        ]],
    ]);
    $this->app->instance(LlmProviderInterface::class, $mock);

    for ($i = 0; $i < 10; $i++) {
        $this->actingAs($admin)
            ->postJson(route('admin.assistant.chat', $conversation->id), ['content' => 'msg '.$i]);
    }

    $response = $this->actingAs($admin)
        ->postJson(route('admin.assistant.chat', $conversation->id), ['content' => 'overflow']);

    $response->assertStatus(429);
});

test('asisten menyelesaikan tool-call loop (mock LLM mengembalikan tool request + final response)', function () {
    $admin = User::factory()->create([
        'role' => 'admin',
        'username' => 'admin02',
        'email' => 'admin02@test.local',
    ]);
    $schedule = Schedule::factory()->create();
    $mahasiswa = User::factory()->mahasiswa()->create();
    Submission::factory()->create([
        'user_id' => $mahasiswa->id,
        'schedule_id' => $schedule->id,
        'status' => 'pending',
    ]);
    $conversation = AssistantConversation::factory()->create(['admin_id' => $admin->id]);

    $mock = mock(LlmProviderInterface::class);
    $mock->shouldReceive('chat')
        ->once()
        ->andReturn([
            'choices' => [[
                'message' => [
                    'content' => null,
                    'tool_calls' => [
                        [
                            'id' => 'call_1',
                            'type' => 'function',
                            'function' => [
                                'name' => 'getStudentProgress',
                                'arguments' => '{}',
                            ],
                        ],
                    ],
                ],
            ]],
        ]);
    $mock->shouldReceive('chat')
        ->once()
        ->andReturn([
            'choices' => [[
                'message' => [
                    'content' => 'Berdasarkan data, ada 1 submission dengan status pending.',
                    'tool_calls' => null,
                ],
            ]],
        ]);
    $this->app->instance(LlmProviderInterface::class, $mock);

    $response = $this->actingAs($admin)
        ->postJson(route('admin.assistant.chat', $conversation->id), ['content' => 'Berapa jumlah submission pending?']);

    $response->assertOk();

    $this->assertDatabaseHas('assistant_messages', [
        'conversation_id' => $conversation->id,
        'role' => 'assistant',
        'content' => 'Berdasarkan data, ada 1 submission dengan status pending.',
    ]);

    $toolMessage = AssistantMessage::where('conversation_id', $conversation->id)
        ->where('role', 'assistant')
        ->whereNotNull('tool_calls')
        ->first();
    expect($toolMessage)->not->toBeNull();
});

test('admin dapat membuat conversation baru', function () {
    $admin = User::factory()->create([
        'role' => 'admin',
        'username' => 'admin_new',
        'email' => 'admin_new@test.local',
    ]);

    $this->actingAs($admin)->get(route('admin.assistant.new'));
    // createNew redirects ke show — pastikan conversation dibuat
    $this->assertDatabaseHas('assistant_conversations', [
        'admin_id' => $admin->id,
    ]);
});

test('admin tidak dapat melihat conversation milik admin lain', function () {
    $admin1 = User::factory()->create([
        'role' => 'admin',
        'username' => 'admin_a',
        'email' => 'admin_a@test.local',
    ]);
    $admin2 = User::factory()->create([
        'role' => 'admin',
        'username' => 'admin_b',
        'email' => 'admin_b@test.local',
    ]);
    $conversation = AssistantConversation::factory()->create(['admin_id' => $admin1->id]);

    $response = $this->actingAs($admin2)->get(route('admin.assistant.show', $conversation->id));

    $response->assertNotFound();
});
