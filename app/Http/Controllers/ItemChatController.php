<?php

namespace App\Http\Controllers;

use App\Services\ItemChatService;
use Illuminate\Http\Request;

class ItemChatController extends Controller
{
    protected ItemChatService $chatService;

    public function __construct(ItemChatService $chatService)
    {
        $this->chatService = $chatService;
    }

    public function show()
    {
        return view('items.chat');
    }

    public function chat(Request $request)
    {
        $request->validate([
            'question' => 'required|string',
            'history' => 'sometimes|array'
        ]);

        try {
            $response = $this->chatService->chat(
                $request->input('question'),
                $request->input('history', [])
            );

            return response()->json([
                'success' => true,
                'answer' => $response['answer'],
                'context' => $response['context']
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }
}