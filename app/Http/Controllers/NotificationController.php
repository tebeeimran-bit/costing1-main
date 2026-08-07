<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function open(Request $request, string $notification)
    {
        $record=$request->user()->notifications()->whereKey($notification)->firstOrFail();
        $record->markAsRead();
        $url=(string)($record->data['url']??'');
        return redirect(str_starts_with($url,'/')&&!str_starts_with($url,'//')?$url:route('dashboard',absolute:false));
    }

    public function markAllRead(Request $request)
    {
        $request->user()->unreadNotifications()->update(['read_at'=>now()]);
        return back()->with('success','Semua notifikasi ditandai sudah dibaca.');
    }
}
