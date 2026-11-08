<?php

namespace App\Http\Controllers;

use App\Models\Comment;
use App\Models\Thread;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Auth;
use Yajra\DataTables\Facades\DataTables;

class ThreadController extends Controller
{
    public function __construct()
    {
        $this->middleware(['permission:thread-view'])->only(['index', 'show', 'datatable']);
        $this->middleware(['permission:thread-create'])->only(['create', 'store', 'comment']);
        $this->middleware(['permission:thread-update'])->only(['edit', 'update']);
        $this->middleware(['permission:thread-delete'])->only(['destroy']);
        $this->authorizeResource(Thread::class, 'thread');
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return view('dashboard.thread.index');
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view('dashboard.thread.create');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $this->validate_request($request);

        Thread::create([
            'title' => $request->input('title'),
            'slug' => Str::slug($request->input('title') . '-' . time()),
            'body' => $request->input('body'),
            'publication_status' => $request->user()->can('publish', Thread::class) ? (int) $request->boolean('publication_status') : 1,
            'user_id' => Auth::user()->id
        ]);

        return redirect()->route('thread.create')->with('success', 'Forum berhasil ditambah.');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function comment(Request $request)
    {
        $request->validate([
            'thread_id' => 'required|exists:threads,id',
            'body' => 'required|string',
        ]);
        $this->authorize('view', Thread::findOrFail($request->input('thread_id')));
        $this->authorize('create', Comment::class);

        Comment::create([
            'thread_id' => $request->input('thread_id'),
            'body' => $request->input('body'),
            'user_id' => Auth::user()->id
        ]);

        return redirect()->route('thread.show', $request->input('thread_id'))->with('success', 'Komentar berhasil ditambah.');
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Thread  $thread
     * @return \Illuminate\Http\Response
     */
    public function show(Thread $thread)
    {
        $comments = Comment::with('user')->where('thread_id', '=', $thread->id)->orderBy('created_at', 'desc')->paginate(5);
        $totalComments = Comment::with('user')->where('thread_id', '=', $thread->id)->count();

        // Tanggal Forum//
        $date_post = Carbon::parse($thread->created_at)->locale('id');
        $date_post->settings(['formatFunction' => 'translatedFormat']);
        $date_posted = $date_post->format('l, j F Y');

        return view('dashboard.thread.show', compact('thread', 'comments', 'totalComments', 'date_posted'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Thread  $thread
     * @return \Illuminate\Http\Response
     */
    public function edit(Thread $thread)
    {
        return view('dashboard.thread.edit', compact('thread'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Thread $thread
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Thread $thread)
    {
        $this->validate_request($request);

        $thread->title = $request->input('title');
        $thread->slug = Str::slug($request->input('title') . '-' . time());
        $thread->body = $request->input('body');
        if ($request->user()->can('publish', Thread::class)) {
            $thread->publication_status = $request->boolean('publication_status');
        }
        $thread->save();

        return redirect()->route('thread.edit', $thread->id)->with('success', 'Forum telah diubah.');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Thread  $thread
     * @return \Illuminate\Http\Response
     */
    public function destroy(Thread $thread)
    {
        Session::flash('success', 'Forum telah dihapus.');

        $thread->delete();
    }

    /**
     * Yajra datatable
     */
    public function datatable(Request $request)
    {
        if (Auth::user()->can('publish', Thread::class)) {
            if ($request->ajax()) {
                $threads = Thread::query();

                return DataTables::eloquent($threads)
                    ->addColumn('status', function ($thread) {
                        return view('dashboard.thread.status', compact('thread'))->render();
                    })
                    ->addColumn('action', function ($thread) {
                        return view('dashboard.thread.action', compact('thread'))->render();
                    })
                    ->rawColumns(['status', 'action'])
                    ->addIndexColumn()
                    ->make(true);
            }
        } else {
            if ($request->ajax()) {
                $threads = Thread::with('user')
                    ->where(fn ($q) => $q->where('publication_status', 1)->orWhere('user_id', Auth::user()->id));

                return DataTables::eloquent($threads)
                    ->addColumn('status', function ($thread) {
                        return view('dashboard.thread.status', compact('thread'))->render();
                    })
                    ->addColumn('action', function ($thread) {
                        return view('dashboard.thread.action', compact('thread'))->render();
                    })
                    ->rawColumns(['status', 'action'])
                    ->addIndexColumn()
                    ->make(true);
            }
        }
    }

    /**
     * Validate request
     */
    public function validate_request($request)
    {
        $request->validate([
            'title' => 'required|string|max:300',
            'body' => 'required|string',
            'publication_status' => 'nullable|boolean'
        ]);
    }
}
