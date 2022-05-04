<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCommentRequest;
use App\Models\Comment;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;

class CommentController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(Comment::class);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Inertia\Response|\Inertia\ResponseFactory
     */
    public function create()
    {
        return inertia('Comments/Create');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     */
    public function store(StoreCommentRequest $request)
    {
        $project = \App\Models\Project::where('id', $request->project_id)->first();
        $user = User::where('id', Auth::id())->first();

        if ($project->users->contains(Auth::id()) || Auth::user()->hasRole('admin')) {
            Comment::create([
                'text' => $request->text,
                'user_id' => $request->user_id,
                'project_id' => $request->project_id,
            ]);
        } else if ($user->projects()->find($request->project_id) != null) {
            if ($user->projects()->find($request->project_id)->pivot->is_admin == 1
                || $user->projects()->find($request->project_id)->pivot->is_manager == 1) {
                Comment::create([
                    'text' => $request->text,
                    'user_id' => $request->user_id,
                    'project_id' => $request->project_id,
                ]);
            }
        } else {
            return response()->json(['error' => 'Not authorized to create comments in this project.'], 403);
        }

        return Redirect::back()->with('success', 'Comment created');
    }

    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param \App\Models\Comment $comment
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Comment $comment)
    {
        $comment->update($request->only('text'));
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param \App\Models\Comment $comment
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(Comment $comment)
    {
        $comment->delete();
        return Redirect::back()->with('success', 'Comment deleted');
    }
}
