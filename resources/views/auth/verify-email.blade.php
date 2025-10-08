<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify Email - {{ config('app.name') }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <meta name="csrf-token" content="{{ csrf_token() }}">
</head>
<body class="bg-gray-50">
    <div class="min-h-screen flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-md w-full space-y-8">
            <div>
                <div class="mx-auto h-16 w-16 bg-amber-500 rounded-full flex items-center justify-center">
                    <i class="fas fa-envelope text-white text-2xl"></i>
                </div>
                <h2 class="mt-6 text-center text-3xl font-extrabold text-gray-900">
                    Verify Your Email Address
                </h2>
                <p class="mt-2 text-center text-sm text-gray-600">
                    We've sent a verification link to your email address
                </p>
            </div>

            @if (session('message'))
                <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded mb-4">
                    {{ session('message') }}
                </div>
            @endif

            @if (session('error'))
                <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded mb-4">
                    {{ session('error') }}
                </div>
            @endif

            <div class="bg-white p-8 rounded-lg shadow">
                <div class="text-center">
                    <div class="mb-6">
                        <i class="fas fa-envelope-open-text text-6xl text-amber-500 mb-4"></i>
                        <h3 class="text-lg font-semibold text-gray-900 mb-2">Check Your Email</h3>
                        <p class="text-gray-600 mb-4">
                            We've sent a verification link to <strong>{{ auth()->user()->email }}</strong>.
                            Click the link in your email to verify your account.
                        </p>
                    </div>

                    <div class="space-y-4">
                        <form method="POST" action="{{ route('verification.send') }}">
                            @csrf
                            <button type="submit" class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-amber-600 hover:bg-amber-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-amber-500">
                                <i class="fas fa-paper-plane mr-2"></i>
                                Resend Verification Email
                            </button>
                        </form>

                        <div class="text-sm">
                            <a href="{{ route('login') }}" class="font-medium text-amber-600 hover:text-amber-500">
                                <i class="fas fa-arrow-left mr-1"></i>
                                Back to Login
                            </a>
                        </div>

                        <div class="text-sm">
                            <form method="POST" action="{{ route('logout') }}" class="inline">
                                @csrf
                                <button type="submit" class="font-medium text-gray-600 hover:text-gray-500">
                                    <i class="fas fa-sign-out-alt mr-1"></i>
                                    Sign Out
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="mt-8 pt-6 border-t border-gray-200">
                    <div class="bg-blue-50 p-4 rounded-md">
                        <h4 class="text-sm font-medium text-blue-900 mb-2">
                            <i class="fas fa-info-circle mr-1"></i>
                            Didn't receive the email?
                        </h4>
                        <ul class="text-sm text-blue-800 space-y-1">
                            <li>• Check your spam/junk folder</li>
                            <li>• Make sure {{ config('mail.from.address') }} is not blocked</li>
                            <li>• Wait a few minutes for the email to arrive</li>
                            <li>• Click "Resend" if you still don't see it</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>