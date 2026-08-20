<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use Illuminate\Http\RedirectResponse;

class NotificationController extends Controller
{
    public function markRead(int $id): RedirectResponse
    {
        $notification = Notification::findOrFail($id);
        $this->authorize('update', $notification);

        $notification->update(['read_at' => now()]);

        return back();
    }

    public function markAllRead(): RedirectResponse
    {
        Notification::where('user_id', auth()->id())
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return back();
    }
}
