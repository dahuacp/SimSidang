<?php

namespace App\Http\Controllers;

use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class NotificationController extends Controller
{
    public function index(Request $request, NotificationService $service)
    {
        return response()->json(
            $service->recent($request->user()->id)->map(function (object $n) {
                $n->data = json_decode($n->data) ?? new \stdClass;

                return $n;
            })
        );
    }

    public function unreadCount(Request $request, NotificationService $service)
    {
        return response()->json(['count' => $service->unreadCount($request->user()->id)]);
    }

    public function markAllRead(Request $request, NotificationService $service)
    {
        $service->markAllRead($request->user()->id);

        return response()->json(['status' => 'ok']);
    }

    public function markRead(Request $request, NotificationService $service, string $id)
    {
        DB::table('notifications')
            ->where('notifiable_type', 'App\\Models\\User')
            ->where('notifiable_id', $request->user()->id)
            ->where('id', $id)
            ->update(['read_at' => now(), 'updated_at' => now()]);

        return response()->json(['status' => 'ok']);
    }
}
