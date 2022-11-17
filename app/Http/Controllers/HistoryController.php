<?php

namespace App\Http\Controllers;

use Antonrom\ModelChangesHistory\Models\Change;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class HistoryController extends Controller
{
    public function __construct(protected int $modelId, protected string $modelObject, protected string $historyText)
    {

    }

    public function createHistory(): void
    {
        Change::create([
            'model_id' => $this->modelId,
            'model_type' => $this->modelObject,
            'changes' => json_encode(['message' => $this->historyText]),
            'change_type' => 'updated',
            'changer_type' => 'App\Models\User',
            'changer_id' => Auth::id(),
            'stack_trace' => null,
            'created_at' => now()
        ]);
    }
}
