<?php

namespace App\Http\Controllers;

use App\Models\Blog;
use App\Models\Thread;
use App\Models\Research;
use App\Models\Event;
use App\Models\User;
use Carbon\Carbon;

class HomeController extends Controller
{
    public function index()
    {
        $counter = [
            'blog' => Blog::select('id')->where('publication_status', '=', 1)->count(),
            'thread' => Thread::select('id')->where('publication_status', '=', 1)->count(),
            'research' =>  Research::select('id')->count(),
            'event' => Event::select('id')->count(),
            'user' => User::select('id')->count()
        ];

        $members = User::select('id')->role(['super_administrator','admin_pd','anggota'])->get();
        $generals = User::select('id')->role('umum')->get();
        $userRoles = [
            'member' => $members->count(),
            'general' => $generals->count()
        ];

        $event = Event::latest()->first();
        // B8: dashboard tetap jalan sebelum ada event
        $date_event = $event
            ? Carbon::parse($event->date_start)->locale('id')->translatedFormat('j M Y') . ' - ' . Carbon::parse($event->date_end)->locale('id')->translatedFormat('j M Y')
            : null;

        return view('dashboard.home.index', compact(
            'counter',
            'userRoles',
            'event',
            'date_event'
        ));
    }
}
