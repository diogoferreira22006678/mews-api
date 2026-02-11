<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Services\DialogflowService;
use App\Services\ChatbotAvailabilityService;
use Throwable;

class ChatbotController extends Controller
{
    public function __construct(
        private DialogflowService $dialogflow,
        private ChatbotAvailabilityService $chatbotAvailability,
    ) {}

    public function message(Request $request)
    {
        try {
            /*
             |--------------------------------------------------------------------------
             | Force JSON expectations
             |--------------------------------------------------------------------------
             */
            $request->headers->set('Accept', 'application/json');

            /*
             |--------------------------------------------------------------------------
             | Validate input (NO automatic HTML responses)
             |--------------------------------------------------------------------------
             */
            $validator = Validator::make($request->all(), [
                'text' => ['required', 'string'],
                'session_id' => ['nullable', 'string'],
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'error' => true,
                    'type' => 'validation_error',
                    'messages' => $validator->errors(),
                ], 422);
            }

            $data = $validator->validated();

            /*
             |--------------------------------------------------------------------------
             | Call Dialogflow
             |--------------------------------------------------------------------------
             */
            $df = $this->dialogflow->detectIntent(
                text: $data['text'],
                sessionId: $data['session_id'] ?? null
            );

            if (!is_array($df)) {
                return response()->json([
                    'error' => true,
                    'type' => 'dialogflow_error',
                    'message' => 'Invalid Dialogflow response',
                    'debug' => $df,
                ], 500);
            }

            /*
             |--------------------------------------------------------------------------
             | Handle intent
             |--------------------------------------------------------------------------
             */
            $intent = $df['intent'] ?? null;

            if ($intent !== 'CheckAvailability') {
                return response()->json([
                    'error' => false,
                    'text' => 'Intent not supported',
                    'intent' => $intent,
                    'debug' => $df,
                ]);
            }

            /*
             |--------------------------------------------------------------------------
             | Handle availability logic
             |--------------------------------------------------------------------------
             */
            $reply = $this->chatbotAvailability->handleAvailabilityIntent(
                $df['parameters'] ?? []
            );

            /*
             |--------------------------------------------------------------------------
             | Final response
             |--------------------------------------------------------------------------
             */
            return response()->json([
                'error' => false,
                'intent' => $intent,
                'reply' => $reply,
            ]);

        } catch (Throwable $e) {
            return response()->json([
                'error' => true,
                'type' => 'exception',
                'message' => $e->getMessage(),
                'exception' => get_class($e),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ], 500);
        }
    }
}