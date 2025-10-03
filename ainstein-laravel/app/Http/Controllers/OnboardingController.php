<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class OnboardingController extends Controller
{
    /**
     * Mark onboarding as completed for the current user.
     */
    public function complete(Request $request)
    {
        $user = Auth::user();

        $user->update([
            'onboarding_completed' => true
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Onboarding completed successfully'
        ]);
    }

    /**
     * Reset onboarding (for testing or user request).
     */
    public function reset(Request $request)
    {
        $user = Auth::user();

        $user->update([
            'onboarding_completed' => false
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Onboarding reset successfully'
        ]);
    }

    /**
     * Mark tool onboarding as completed.
     */
    public function completeToolOnboarding(Request $request, string $tool)
    {
        $user = Auth::user();

        // Validate tool name
        $validTools = ['pages', 'content-generation', 'prompts', 'api-keys'];

        if (!in_array($tool, $validTools)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid tool name'
            ], 400);
        }

        $user->markToolOnboardingComplete($tool);

        return response()->json([
            'success' => true,
            'message' => "Tool onboarding '{$tool}' marked as completed"
        ]);
    }

    /**
     * Reset tool onboarding.
     */
    public function resetToolOnboarding(Request $request, string $tool = null)
    {
        $user = Auth::user();

        $user->resetToolOnboarding($tool);

        return response()->json([
            'success' => true,
            'message' => $tool ? "Tool onboarding '{$tool}' reset successfully" : 'All tool onboardings reset successfully'
        ]);
    }

    /**
     * Get onboarding status.
     */
    public function status(Request $request)
    {
        $user = Auth::user();

        return response()->json([
            'onboarding_completed' => $user->onboarding_completed,
            'tools_completed' => $user->onboarding_tools_completed ?? []
        ]);
    }
}
