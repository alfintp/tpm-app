<?php

namespace App\Http\Controllers;

use App\Models\AppNotification;
use App\Models\NotificationRead;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $notifications = AppNotification::where('is_active', true)
            ->orderByDesc('created_at')
            ->get()
            ->map(function ($n) use ($user) {
                return [
                    'id' => $n->id,
                    'title' => $n->title,
                    'body' => $n->body,
                    'created_at' => $n->created_at->toIso8601String(),
                    'is_read' => $n->isReadBy($user->id),
                ];
            });

        $unreadCount = $notifications->where('is_read', false)->count();

        return response()->json([
            'notifications' => $notifications,
            'unread_count' => $unreadCount,
        ]);
    }

    public function markAsRead(Request $request, $id)
    {
        $user = $request->user();

        NotificationRead::firstOrCreate([
            'notification_id' => $id,
            'user_id' => $user->id,
        ], [
            'read_at' => now(),
        ]);

        return response()->json(['message' => 'Notification marked as read']);
    }

    public function markAllAsRead(Request $request)
    {
        $user = $request->user();

        $notificationIds = AppNotification::where('is_active', true)->pluck('id');

        foreach ($notificationIds as $nid) {
            NotificationRead::firstOrCreate([
                'notification_id' => $nid,
                'user_id' => $user->id,
            ], [
                'read_at' => now(),
            ]);
        }

        return response()->json(['message' => 'All notifications marked as read']);
    }

    // Admin CRUD
    public function list(Request $request)
    {
        $notifications = AppNotification::orderByDesc('created_at')->get();

        return response()->json($notifications);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'body' => 'required|string',
            'is_active' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $notification = AppNotification::create([
            'title' => $request->title,
            'body' => $request->body,
            'is_active' => $request->boolean('is_active', true),
        ]);

        return response()->json($notification, 201);
    }

    public function update(Request $request, $id)
    {
        $notification = AppNotification::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'body' => 'required|string',
            'is_active' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $notification->update([
            'title' => $request->title,
            'body' => $request->body,
            'is_active' => $request->boolean('is_active', true),
        ]);

        return response()->json($notification);
    }

    public function destroy($id)
    {
        $notification = AppNotification::findOrFail($id);
        $notification->delete();

        return response()->json(['message' => 'Notification deleted']);
    }
}
