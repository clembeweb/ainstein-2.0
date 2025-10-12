<nav class="bg-white shadow">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
            <div class="flex">
                <!-- Logo -->
                <div class="shrink-0 flex items-center">
                    <a href="{{ route('tenant.dashboard') }}">
                        <x-logo />
                    </a>
                </div>

                <!-- Navigation Links -->
                <div class="hidden space-x-8 sm:-my-px sm:ml-10 sm:flex">
                    <x-nav-link href="{{ route('tenant.dashboard') }}" :active="request()->routeIs('tenant.dashboard')">
                        {{ __('Dashboard') }}
                    </x-nav-link>

                    <x-nav-link href="{{ route('tenant.content.index') }}" :active="request()->routeIs('tenant.content.*') || request()->routeIs('tenant.pages.*') || request()->routeIs('tenant.prompts.*')">
                        {{ __('Content Generator') }}
                    </x-nav-link>

                    <x-nav-link href="{{ route('tenant.campaigns.index') }}" :active="request()->routeIs('tenant.campaigns.*')">
                        {{ __('Campaigns') }}
                    </x-nav-link>

                    <x-nav-link href="{{ route('tenant.crews.index') }}" :active="request()->routeIs('tenant.crews.*')">
                        {{ __('CrewAI') }}
                    </x-nav-link>

                    <x-nav-link href="{{ route('tenant.api-keys.index') }}" :active="request()->routeIs('tenant.api-keys.*')">
                        {{ __('API Keys') }}
                    </x-nav-link>
                </div>
            </div>

            <!-- Settings Dropdown -->
            <div class="hidden sm:flex sm:items-center sm:ml-6">
                <!-- Tenant Info -->
                <div class="flex items-center mr-4">
                    <div class="text-sm">
                        <span class="text-gray-500">{{ Auth::user()->tenant->name }}</span>
                        <span class="ml-2 px-2 py-1 text-xs font-medium bg-{{ Auth::user()->tenant->plan_type === 'free' ? 'gray' : 'blue' }}-100 text-{{ Auth::user()->tenant->plan_type === 'free' ? 'gray' : 'blue' }}-800 rounded-full">
                            {{ ucfirst(Auth::user()->tenant->plan_type) }}
                        </span>
                    </div>
                </div>

                <!-- User Dropdown -->
                <x-dropdown align="right" width="48">
                    <x-slot name="trigger">
                        <button class="flex items-center text-sm font-medium text-gray-500 hover:text-gray-700 hover:border-gray-300 focus:outline-none focus:text-gray-700 focus:border-gray-300 transition duration-150 ease-in-out">
                            <div class="flex items-center">
                                <img class="h-8 w-8 rounded-full object-cover mr-2" src="{{ Auth::user()->avatar_url }}" alt="{{ Auth::user()->name }}">
                                <div>{{ Auth::user()->name }}</div>
                                <div class="ml-1">
                                    <svg class="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                    </svg>
                                </div>
                            </div>
                        </button>
                    </x-slot>

                    <x-slot name="content">
                        <x-dropdown-link href="{{ route('tenant.settings') }}">
                            {{ __('Settings') }}
                        </x-dropdown-link>

                        <!-- Restart Onboarding Tour -->
                        <button onclick="window.startOnboardingTour()" class="block w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                            {{ __('Restart Tour') }}
                        </button>

                        <!-- Authentication -->
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <x-dropdown-link href="{{ route('logout') }}"
                                    onclick="event.preventDefault(); this.closest('form').submit();">
                                {{ __('Log Out') }}
                            </x-dropdown-link>
                        </form>
                    </x-slot>
                </x-dropdown>
            </div>

            <!-- Hamburger -->
            <div class="-mr-2 flex items-center sm:hidden">
                <button @click="open = ! open" class="inline-flex items-center justify-center p-2 rounded-md text-gray-400 hover:text-gray-500 hover:bg-gray-100 focus:outline-none focus:bg-gray-100 focus:text-gray-500 transition duration-150 ease-in-out">
                    <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                        <path :class="{'hidden': open, 'inline-flex': ! open }" class="inline-flex" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        <path :class="{'hidden': ! open, 'inline-flex': open }" class="hidden" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <!-- Responsive Navigation Menu -->
    <div :class="{'block': open, 'hidden': ! open}" class="hidden sm:hidden">
        <div class="pt-2 pb-3 space-y-1">
            <x-responsive-nav-link href="{{ route('tenant.dashboard') }}" :active="request()->routeIs('tenant.dashboard')">
                {{ __('Dashboard') }}
            </x-responsive-nav-link>

            <x-responsive-nav-link href="{{ route('tenant.content.index') }}" :active="request()->routeIs('tenant.content.*') || request()->routeIs('tenant.pages.*') || request()->routeIs('tenant.prompts.*')">
                {{ __('Content Generator') }}
            </x-responsive-nav-link>

            <x-responsive-nav-link href="{{ route('tenant.campaigns.index') }}" :active="request()->routeIs('tenant.campaigns.*')">
                {{ __('Campaigns') }}
            </x-responsive-nav-link>

            <x-responsive-nav-link href="{{ route('tenant.crews.index') }}" :active="request()->routeIs('tenant.crews.*')">
                {{ __('CrewAI') }}
            </x-responsive-nav-link>

            <x-responsive-nav-link href="{{ route('tenant.api-keys.index') }}" :active="request()->routeIs('tenant.api-keys.*')">
                {{ __('API Keys') }}
            </x-responsive-nav-link>
        </div>

        <!-- Responsive Settings Options -->
        <div class="pt-4 pb-1 border-t border-gray-200">
            <div class="px-4">
                <div class="font-medium text-base text-gray-800">{{ Auth::user()->name }}</div>
                <div class="font-medium text-sm text-gray-500">{{ Auth::user()->email }}</div>
            </div>

            <div class="mt-3 space-y-1">
                <x-responsive-nav-link href="{{ route('tenant.settings') }}">
                    {{ __('Settings') }}
                </x-responsive-nav-link>

                <!-- Authentication -->
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <x-responsive-nav-link href="{{ route('logout') }}"
                            onclick="event.preventDefault(); this.closest('form').submit();">
                        {{ __('Log Out') }}
                    </x-responsive-nav-link>
                </form>
            </div>
        </div>
    </div>
</nav>