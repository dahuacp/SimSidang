<?php

use App\Models\User;
use App\Services\NotificationService;

test('unread count default 0', function () {
    $mhs = User::factory()->mahasiswa()->create();

    $response = $this->actingAs($mhs)->get(route('notifications.unread-count'));

    $response->assertOk()->assertJson(['count' => 0]);
});

test('service dapat menyimpan dan menghitung notifikasi', function () {
    $mhs = User::factory()->mahasiswa()->create();

    $service = app(NotificationService::class);
    $service->send($mhs->id, 'revision.note.created', ['submission_id' => 1], '/mahasiswa/submissions/1');

    $this->assertSame(1, $service->unreadCount($mhs->id));
});

test('mark all read mengosongkan unread count', function () {
    $mhs = User::factory()->mahasiswa()->create();
    app(NotificationService::class)->send($mhs->id, 'revision.note.created', ['submission_id' => 1]);
    app(NotificationService::class)->send($mhs->id, 'revision.note.resolved', ['submission_id' => 1]);

    $this->actingAs($mhs)->post(route('notifications.read-all'));

    $this->assertSame(0, app(NotificationService::class)->unreadCount($mhs->id));
});

test('notifikasi muncul di endpoint list', function () {
    $mhs = User::factory()->mahasiswa()->create();
    app(NotificationService::class)->send($mhs->id, 'revision.note.created', ['submission_id' => 1, 'message' => 'Revision note baru'], '/mahasiswa/submissions/1');

    $response = $this->actingAs($mhs)->get(route('notifications.index'));

    $response->assertOk()->assertJsonCount(1);
});
