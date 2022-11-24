<?php

namespace App\Http\Controllers;

use Antonrom\ModelChangesHistory\Models\Change;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class HistoryController extends Controller
{
    public function __construct(protected string $modelObject)
    {

    }

    public function createHistory(int $modelId, string $historyText): void
    {
        $array[] = ['message' => $historyText, 'changed_by' => Auth::user()];
        Change::create([
            'model_id' => $modelId,
            'model_type' => $this->modelObject,
            'changes' => json_encode($array),
            'change_type' => 'updated',
            'changer_type' => 'App\Models\User',
            'changer_id' => Auth::id(),
            'stack_trace' => null,
            'created_at' => now()
        ]);
    }
}
