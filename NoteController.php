<?php

namespace App\Http\Api\v1\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Note;
use App\Models\Program;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class NoteController extends Controller
{
    // TODO: Add note_id to this.
    public function index($program_id, $user_id) {
        $params = [
            'program_id' => $program_id,
            'user_id' => $user_id
        ];

        Validator::make($params, [
            'program_id' => 'required|exists:App\Models\Program,id',
            'user_id' => 'required|exists:App\Models\User,id'
        ])->validate();

        $program = Auth::user()->programs()->where('id', $program_id)->firstOrFail();
        $user = $program->users()->where('id', $user_id)->firstOrFail();

        return response()->json([
            $user->notes()->where('program_id', $program_id)->with('author')->get()
        ]);
    }

    /**
     * Create a new Note for the requested User.
     * @param Request $request
     * @param $program_id
     * @param $user_id
     * @return mixed
     * @throws \Illuminate\Validation\ValidationException
     */
    public function create(Request $request, $program_id, $user_id) {
        $params = $request->all();
        $params['program_id'] = $program_id;
        $params['user_id'] = $user_id;

        Validator::make($params, [
            'body' => 'required|string',
            'program_id' => 'required|exists:App\Models\Program,id',
            'user_id' => 'required|exists:App\Models\User,id'
        ])->validate();

        $note = new Note([
            'body' => $request->body
        ]);

        $program = Auth::user()->programs()->where('id', $program_id)->firstOrFail();
        $user = $program->users()->where('id', $user_id)->firstOrFail();
        $note->program()->associate($program);
        $note->author()->associate(Auth::user());
        $note->user()->associate($user);
        $note->save();

        return response()->created(
            'Note created successfully!',
            $note
        );
    }

    /**
     * Update the specified note.
     * @param Request $request
     * @param $program_id
     * @param $user_id
     * @param $note_id
     * @return
     * @throws \Illuminate\Validation\ValidationException
     */
    public function update(Request $request, $program_id, $user_id, $note_id) {
        $params = $request->all();
        $params['program_id'] = $program_id;
        $params['user_id'] = $user_id;
        $params['note_id'] = $note_id;

        $validated = Validator::make($params, [
            'program_id' => 'required|exists:App\Models\Program,id',
            'user_id' => 'required|exists:App\Models\User,id',
            'note_id' => 'required|exists:App\Models\Note,id',
            'body' => 'required|string'
        ])->validate();

        $program = Auth::user()->programs()->where('id', $program_id)->firstOrFail();
        $user = $program->users()->where('id', $user_id)->firstOrFail();
        $note = $user->notes()->where('id', $note_id)->firstOrFail();
        $note->body = $validated['body'];
        $note->save();
        $note->author()->associate(Auth::user());
        return response()->updated('Note updated successfully!', $note->toArray());
    }

    /**
     * Delete the specified Note.
     * @param $program_id
     * @param $user_id
     * @param $note_id
     * @return JsonResponse
     */
    public function delete($program_id, $user_id, $note_id) {
        $params = [
            'program_id' => $program_id,
            'user_id' => $user_id,
            'note_id' => $note_id
        ];

        Validator::make($params, [
            'program_id' => 'required|exists:App\Models\Program,id',
            'user_id' => 'required|exists:App\Models\User,id',
            'note_id' => 'required|exists:App\Models\Note,id'
        ])->validate();

        Note::where('id', $note_id)->delete();
        return response()->deleted('Note deleted successfully!');
    }
}
