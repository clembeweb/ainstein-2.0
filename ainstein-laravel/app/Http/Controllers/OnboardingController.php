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
}
