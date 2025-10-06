<?php

namespace App\Http\Controllers;

use App\Services\AI\OpenAIService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class TestOpenAIController extends Controller
{
    protected OpenAIService $openAIService;

    public function __construct(OpenAIService $openAIService)
    {
        $this->openAIService = $openAIService;
    }

    /**
     * Show test page
     */
    public function index()
    {
        return view('test-openai.index', [
            'isUsingMock' => $this->openAIService->isUsingMock(),
            'availableModels' => $this->openAIService->getAvailableModels(),
        ]);
    }

    /**
     * Test chat completion
     */
    public function testChat(Request $request)
    {
        $request->validate([
            'message' => 'required|string|max:1000',
            'model' => 'nullable|string',
            'use_case' => 'nullable|in:campaigns,articles,seo,internal_links,default',
        ]);

        try {
            $messages = [
                ['role' => 'user', 'content' => $request->message]
            ];

            $options = [];
            if ($request->use_case) {
                $options['use_case'] = $request->use_case;
            }

            $result = $this->openAIService->chat($messages, $request->model, $options);

            return response()->json([
                'success' => true,
                'result' => $result,
                'timestamp' => now()->toISOString(),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
                'timestamp' => now()->toISOString(),
            ], 500);
        }
    }

    /**
     * Test simple completion
     */
    public function testCompletion(Request $request)
    {
        $request->validate([
            'prompt' => 'required|string|max:1000',
            'system_message' => 'nullable|string|max:500',
            'model' => 'nullable|string',
        ]);

        try {
            $options = [];
            if ($request->system_message) {
                $options['system_message'] = $request->system_message;
            }

            $result = $this->openAIService->completion($request->prompt, $request->model, $options);

            return response()->json([
                'success' => true,
                'result' => $result,
                'timestamp' => now()->toISOString(),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
                'timestamp' => now()->toISOString(),
            ], 500);
        }
    }

    /**
     * Test JSON parsing
     */
    public function testJSON(Request $request)
    {
        $request->validate([
            'prompt' => 'required|string|max:1000',
        ]);

        try {
            $messages = [
                ['role' => 'user', 'content' => $request->prompt . ' Return only valid JSON.']
            ];

            $result = $this->openAIService->parseJSON($messages);

            return response()->json([
                'success' => true,
                'result' => $result,
                'timestamp' => now()->toISOString(),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
                'timestamp' => now()->toISOString(),
            ], 500);
        }
    }

    /**
     * Test embeddings
     */
    public function testEmbeddings(Request $request)
    {
        $request->validate([
            'text' => 'required|string|max:2000',
        ]);

        try {
            $result = $this->openAIService->embeddings($request->text);

            // Don't return full embeddings array (too large), just metadata
            return response()->json([
                'success' => true,
                'result' => [
                    'embeddings_count' => count($result['embeddings']),
                    'embedding_dimensions' => count($result['embeddings'][0] ?? []),
                    'tokens_used' => $result['tokens_used'],
                    'model' => $result['model'],
                ],
                'timestamp' => now()->toISOString(),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
                'timestamp' => now()->toISOString(),
            ], 500);
        }
    }

    /**
     * Test error handling
     */
    public function testError(Request $request)
    {
        try {
            // Send very long prompt to trigger error
            $longPrompt = str_repeat('a', 100000);

            $result = $this->openAIService->completion($longPrompt);

            return response()->json([
                'success' => true,
                'result' => $result,
                'timestamp' => now()->toISOString(),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
                'error_handled' => true,
                'timestamp' => now()->toISOString(),
            ], 500);
        }
    }
}
