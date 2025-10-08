<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - {{ config('app.name') }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <meta name="csrf-token" content="{{ csrf_token() }}">
</head>
<body class="bg-gray-50">
    <div class="min-h-screen flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-md w-full space-y-8">
            <div>
                <div class="mx-auto h-16 w-16 bg-amber-500 rounded-full flex items-center justify-center">
                    <i class="fas fa-brain text-white text-2xl"></i>
                </div>
                <h2 class="mt-6 text-center text-3xl font-extrabold text-gray-900">
                    Create your account
                </h2>
                <p class="mt-2 text-center text-sm text-gray-600">
                    Start generating AI content for your business
                </p>
            </div>

            <form class="mt-8 space-y-6" action="{{ route('register') }}" method="POST">
                @csrf

                @if ($errors->any())
                    <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded">
                        <ul class="list-disc list-inside text-sm">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <div class="space-y-4">
                    <div>
                        <label for="name" class="block text-sm font-medium text-gray-700">Full Name</label>
                        <input id="name" name="name" type="text" autocomplete="name" required
                               class="mt-1 appearance-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-md focus:outline-none focus:ring-amber-500 focus:border-amber-500 focus:z-10 sm:text-sm"
                               placeholder="Enter your full name" value="{{ old('name') }}">
                    </div>

                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-700">Email Address</label>
                        <input id="email" name="email" type="email" autocomplete="email" required
                               class="mt-1 appearance-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-md focus:outline-none focus:ring-amber-500 focus:border-amber-500 focus:z-10 sm:text-sm"
                               placeholder="Enter your email address" value="{{ old('email') }}">
                    </div>

                    <div>
                        <label for="company_name" class="block text-sm font-medium text-gray-700">Organization Name</label>
                        <input id="company_name" name="company_name" type="text" required
                               class="mt-1 appearance-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-md focus:outline-none focus:ring-amber-500 focus:border-amber-500 focus:z-10 sm:text-sm"
                               placeholder="Enter your organization name" value="{{ old('company_name') }}">
                    </div>

                    <div>
                        <label for="password" class="block text-sm font-medium text-gray-700">Password</label>
                        <input id="password" name="password" type="password" autocomplete="new-password" required
                               class="mt-1 appearance-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-md focus:outline-none focus:ring-amber-500 focus:border-amber-500 focus:z-10 sm:text-sm"
                               placeholder="Enter your password">
                    </div>

                    <div>
                        <label for="password_confirmation" class="block text-sm font-medium text-gray-700">Confirm Password</label>
                        <input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" required
                               class="mt-1 appearance-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-md focus:outline-none focus:ring-amber-500 focus:border-amber-500 focus:z-10 sm:text-sm"
                               placeholder="Confirm your password">
                    </div>
                </div>

                <div class="flex items-center">
                    <input id="agree-terms" name="agree_terms" type="checkbox" required
                           class="h-4 w-4 text-amber-600 focus:ring-amber-500 border-gray-300 rounded">
                    <label for="agree-terms" class="ml-2 block text-sm text-gray-900">
                        I agree to the <a href="#" class="text-amber-600 hover:text-amber-500">Terms of Service</a> and
                        <a href="#" class="text-amber-600 hover:text-amber-500">Privacy Policy</a>
                    </label>
                </div>

                <div>
                    <button type="submit"
                            class="group relative w-full flex justify-center py-2 px-4 border border-transparent text-sm font-medium rounded-md text-white bg-amber-600 hover:bg-amber-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-amber-500">
                        <span class="absolute left-0 inset-y-0 flex items-center pl-3">
                            <i class="fas fa-user-plus text-amber-500 group-hover:text-amber-400"></i>
                        </span>
                        Create Account
                    </button>
                </div>

                <div class="text-center">
                    <p class="text-sm text-gray-600">
                        Already have an account?
                        <a href="{{ route('login') }}" class="font-medium text-amber-600 hover:text-amber-500">
                            Sign in here
                        </a>
                    </p>
                </div>
            </form>

            <!-- Features -->
            <div class="mt-8 border-t pt-6">
                <p class="text-center text-sm font-medium text-gray-700 mb-4">What you get:</p>
                <div class="grid grid-cols-1 gap-2 text-sm text-gray-600">
                    <div class="flex items-center">
                        <i class="fas fa-check text-green-500 mr-2"></i>
                        AI-powered content generation
                    </div>
                    <div class="flex items-center">
                        <i class="fas fa-check text-green-500 mr-2"></i>
                        SEO-optimized content
                    </div>
                    <div class="flex items-center">
                        <i class="fas fa-check text-green-500 mr-2"></i>
                        Multi-language support
                    </div>
                    <div class="flex items-center">
                        <i class="fas fa-check text-green-500 mr-2"></i>
                        Custom prompt templates
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>