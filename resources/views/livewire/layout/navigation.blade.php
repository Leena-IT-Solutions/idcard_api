<?php

use App\Livewire\Actions\Logout;
use Livewire\Volt\Component;

new class extends Component
{
    /**
     * Log the current user out of the application.
     */
    public function logout(Logout $logout): void
    {
        $logout();

        $this->redirect('/', navigate: true);
    }
}; ?>

<div x-data="{ open: false }">
    <!-- Mobile Navigation Top Bar -->
    <div class="lg:hidden flex items-center justify-between bg-white dark:bg-gray-900 border-b border-gray-200 dark:border-gray-700 px-4 py-3 h-16 w-full fixed top-0 z-40">
        <div class="flex items-center">
            <a href="{{ route('dashboard') }}" wire:navigate>
                <x-application-logo class="block h-8 w-auto fill-current text-gray-800 dark:text-gray-200" />
            </a>
        </div>
        <button @click="open = true" class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 focus:outline-none">
            <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
            </svg>
        </button>
    </div>

    <!-- Off-canvas background for mobile -->
    <div x-show="open" class="fixed inset-0 z-40 bg-gray-900/80 lg:hidden" @click="open = false" x-transition.opacity></div>

    <!-- Sidebar -->
    <nav :class="open ? 'translate-x-0' : '-translate-x-full'" class="fixed inset-y-0 left-0 z-50 w-64 bg-white dark:bg-gray-900 border-r border-gray-200 dark:border-gray-800 transition-transform duration-300 ease-in-out lg:translate-x-0 lg:flex lg:flex-col justify-between">
        <!-- Top Section: Logo & Links -->
        <div>
            <div class="flex items-center justify-between h-16 px-6 border-b border-gray-200 dark:border-gray-800">
                <a href="{{ route('dashboard') }}" wire:navigate class="flex items-center space-x-2 text-indigo-600 dark:text-indigo-400 font-bold text-xl">
                    <x-application-logo class="h-8 w-8" />
                    <span>{{ config('app.name', 'IdCard') }}</span>
                </a>
                <button @click="open = false" class="lg:hidden text-gray-400 hover:text-gray-500">
                    <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <!-- Top Links -->
            <div class="px-3 py-6 space-y-1">
                @php
                    $user = auth()->user();
                    $activeSchoolId = session('active_school_id');
                    $isSaasAdmin = $user->hasRole('saas_admin');
                    
                    // Context-specific school_admin assignment in active school
                    $isSchoolAdmin = $activeSchoolId && \App\Models\SchoolUserRole::where('user_id', $user->id)
                        ->where('school_id', $activeSchoolId)
                        ->whereHas('role', function($q) { $q->where('slug', 'school_admin'); })
                        ->exists();

                    // Context-specific teacher assignment in active school
                    $isTeacher = $activeSchoolId && \App\Models\SchoolUserRole::where('user_id', $user->id)
                        ->where('school_id', $activeSchoolId)
                        ->whereHas('role', function($q) { $q->where('slug', 'teacher'); })
                        ->exists();

                    $isDashboard = request()->routeIs('dashboard');
                @endphp
                <a href="{{ route('dashboard') }}" wire:navigate class="group w-full flex items-center space-x-3 px-4 py-3 rounded-xl text-sm font-semibold transition-all duration-200 {{ $isDashboard ? 'bg-indigo-50 dark:bg-indigo-950/30 text-indigo-600 dark:text-indigo-400 shadow-sm' : 'text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-800/40 hover:text-gray-900 dark:hover:text-gray-200' }}">
                    <svg class="h-5 w-5 transition-colors duration-200 {{ $isDashboard ? 'text-indigo-600 dark:text-indigo-400' : 'text-gray-400 dark:text-gray-500 group-hover:text-gray-600 dark:group-hover:text-gray-300' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                    </svg>
                    <span>{{ __('Dashboard') }}</span>
                </a>

                @if($isSaasAdmin || $isSchoolAdmin)
                    @php
                        $isTemplates = request()->routeIs('templates');
                    @endphp
                    <a href="{{ route('templates') }}" wire:navigate class="group w-full flex items-center space-x-3 px-4 py-3 rounded-xl text-sm font-semibold transition-all duration-200 {{ $isTemplates ? 'bg-indigo-50 dark:bg-indigo-950/30 text-indigo-600 dark:text-indigo-400 shadow-sm' : 'text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-800/40 hover:text-gray-900 dark:hover:text-gray-200' }}">
                        <svg class="h-5 w-5 transition-colors duration-200 {{ $isTemplates ? 'text-indigo-600 dark:text-indigo-400' : 'text-gray-400 dark:text-gray-500 group-hover:text-gray-600 dark:group-hover:text-gray-300' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM4 13a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H5a1 1 0 01-1-1v-6zM16 13a1 1 0 011-1h2a1 1 0 011 1v6a1 1 0 01-1 1h-2a1 1 0 01-1-1v-6z"/>
                        </svg>
                        <span>{{ __('Templates') }}</span>
                    </a>
                @endif

                @if($isSaasAdmin)
                    @php
                        $isUsers = request()->routeIs('users.index');
                    @endphp
                    <a href="{{ route('users.index') }}" wire:navigate class="group w-full flex items-center space-x-3 px-4 py-3 rounded-xl text-sm font-semibold transition-all duration-200 {{ $isUsers ? 'bg-indigo-50 dark:bg-indigo-950/30 text-indigo-600 dark:text-indigo-400 shadow-sm' : 'text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-800/40 hover:text-gray-900 dark:hover:text-gray-200' }}">
                        <svg class="h-5 w-5 transition-colors duration-200 {{ $isUsers ? 'text-indigo-600 dark:text-indigo-400' : 'text-gray-400 dark:text-gray-500 group-hover:text-gray-600 dark:group-hover:text-gray-300' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                        </svg>
                        <span>{{ __('User Manager') }}</span>
                    </a>
                @endif

                @if($isSaasAdmin || $isSchoolAdmin || (!$activeSchoolId && $user->hasRole('school_admin')))
                    @php
                        $isSchools = request()->routeIs('schools');
                    @endphp
                    <a href="{{ route('schools') }}" wire:navigate class="group w-full flex items-center space-x-3 px-4 py-3 rounded-xl text-sm font-semibold transition-all duration-200 {{ $isSchools ? 'bg-indigo-50 dark:bg-indigo-950/30 text-indigo-600 dark:text-indigo-400 shadow-sm' : 'text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-800/40 hover:text-gray-900 dark:hover:text-gray-200' }}">
                        <svg class="h-5 w-5 transition-colors duration-200 {{ $isSchools ? 'text-indigo-600 dark:text-indigo-400' : 'text-gray-400 dark:text-gray-500 group-hover:text-gray-600 dark:group-hover:text-gray-300' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                        </svg>
                        <span>{{ __('School Profiles') }}</span>
                    </a>
                @endif

                @if($isSaasAdmin || $isSchoolAdmin)
                    @php
                        $isUserRoles = request()->routeIs('user-roles');
                    @endphp
                    <a href="{{ route('user-roles') }}" wire:navigate class="group w-full flex items-center space-x-3 px-4 py-3 rounded-xl text-sm font-semibold transition-all duration-200 {{ $isUserRoles ? 'bg-indigo-50 dark:bg-indigo-950/30 text-indigo-600 dark:text-indigo-400 shadow-sm' : 'text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-800/40 hover:text-gray-900 dark:hover:text-gray-200' }}">
                        <svg class="h-5 w-5 transition-colors duration-200 {{ $isUserRoles ? 'text-indigo-600 dark:text-indigo-400' : 'text-gray-400 dark:text-gray-500 group-hover:text-gray-600 dark:group-hover:text-gray-300' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 6a3 3 0 11-6 0 3 3 0 016 0zM17 6a3 3 0 11-6 0 3 3 0 016 0zM12.93 17c.046-.327.07-.66.07-1a6.97 6.97 0 00-1.5-4.33A5 5 0 0119 16v1h-6.07zM6 11a5 5 0 015 5v1H1v-1a5 5 0 015-5z"/>
                        </svg>
                        <span>{{ __('User Roles') }}</span>
                    </a>
                @endif

                @if($isSaasAdmin || $isSchoolAdmin)
                    @php
                        $isGradesDivisions = request()->routeIs('grades-divisions');
                    @endphp
                    <a href="{{ route('grades-divisions') }}" wire:navigate class="group w-full flex items-center space-x-3 px-4 py-3 rounded-xl text-sm font-semibold transition-all duration-200 {{ $isGradesDivisions ? 'bg-indigo-50 dark:bg-indigo-950/30 text-indigo-600 dark:text-indigo-400 shadow-sm' : 'text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-800/40 hover:text-gray-900 dark:hover:text-gray-200' }}">
                        <svg class="h-5 w-5 transition-colors duration-200 {{ $isGradesDivisions ? 'text-indigo-600 dark:text-indigo-400' : 'text-gray-400 dark:text-gray-500 group-hover:text-gray-600 dark:group-hover:text-gray-300' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                        </svg>
                        <span>{{ __('Grade & Division') }}</span>
                    </a>
                @endif

                @if($isSaasAdmin || $isSchoolAdmin)
                    @php
                        $isCampaigns = request()->routeIs('campaigns');
                    @endphp
                    <a href="{{ route('campaigns') }}" wire:navigate class="group w-full flex items-center space-x-3 px-4 py-3 rounded-xl text-sm font-semibold transition-all duration-200 {{ $isCampaigns ? 'bg-indigo-50 dark:bg-indigo-950/30 text-indigo-600 dark:text-indigo-400 shadow-sm' : 'text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-800/40 hover:text-gray-900 dark:hover:text-gray-200' }}">
                        <svg class="h-5 w-5 transition-colors duration-200 {{ $isCampaigns ? 'text-indigo-600 dark:text-indigo-400' : 'text-gray-400 dark:text-gray-500 group-hover:text-gray-600 dark:group-hover:text-gray-300' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                        <span>{{ __('Campaigns') }}</span>
                    </a>
                @endif

                @if($isSaasAdmin || $isSchoolAdmin)
                    @php
                        $isParentAccess = request()->routeIs('parent-access');
                    @endphp
                    <a href="{{ route('parent-access') }}" wire:navigate class="group w-full flex items-center space-x-3 px-4 py-3 rounded-xl text-sm font-semibold transition-all duration-200 {{ $isParentAccess ? 'bg-indigo-50 dark:bg-indigo-950/30 text-indigo-600 dark:text-indigo-400 shadow-sm' : 'text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-800/40 hover:text-gray-900 dark:hover:text-gray-200' }}">
                        <svg class="h-5 w-5 transition-colors duration-200 {{ $isParentAccess ? 'text-indigo-600 dark:text-indigo-400' : 'text-gray-400 dark:text-gray-500 group-hover:text-gray-600 dark:group-hover:text-gray-300' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                        </svg>
                        <span>{{ __('Parent Access') }}</span>
                    </a>
                @endif

                @if($isSaasAdmin || $isSchoolAdmin || $isTeacher)
                    @php
                        $isStudents = request()->routeIs('students');
                    @endphp
                    <a href="{{ route('students') }}" wire:navigate class="group w-full flex items-center space-x-3 px-4 py-3 rounded-xl text-sm font-semibold transition-all duration-200 {{ $isStudents ? 'bg-indigo-50 dark:bg-indigo-950/30 text-indigo-600 dark:text-indigo-400 shadow-sm' : 'text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-800/40 hover:text-gray-900 dark:hover:text-gray-200' }}">
                        <svg class="h-5 w-5 transition-colors duration-200 {{ $isStudents ? 'text-indigo-600 dark:text-indigo-400' : 'text-gray-400 dark:text-gray-500 group-hover:text-gray-600 dark:group-hover:text-gray-300' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                        </svg>
                        <span>{{ __('Students') }}</span>
                    </a>
                @endif
            </div>
        </div>

        <!-- Bottom Section: Profile & User Card -->
        <div>
            <div class="px-3 py-2 space-y-1">
                @if(auth()->user()->hasRole('saas_admin'))
                    @php
                        $isUpdateSystem = request()->routeIs('update-system');
                    @endphp
                    <a href="{{ route('update-system') }}" wire:navigate class="group w-full flex items-center space-x-3 px-4 py-3 rounded-xl text-sm font-semibold transition-all duration-200 {{ $isUpdateSystem ? 'bg-indigo-50 dark:bg-indigo-950/30 text-indigo-600 dark:text-indigo-400 shadow-sm' : 'text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-800/40 hover:text-gray-900 dark:hover:text-gray-200' }}">
                        <svg class="h-5 w-5 transition-colors duration-200 {{ $isUpdateSystem ? 'text-indigo-600 dark:text-indigo-400' : 'text-gray-400 dark:text-gray-500 group-hover:text-gray-600 dark:group-hover:text-gray-300' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 1121.21 7.89H17v4.11"/>
                        </svg>
                        <span>{{ __('Update System') }}</span>
                    </a>
                @endif

                @php
                    $isProfile = request()->routeIs('profile');
                @endphp
                <a href="{{ route('profile') }}" wire:navigate class="group w-full flex items-center space-x-3 px-4 py-3 rounded-xl text-sm font-semibold transition-all duration-200 {{ $isProfile ? 'bg-indigo-50 dark:bg-indigo-950/30 text-indigo-600 dark:text-indigo-400 shadow-sm' : 'text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-800/40 hover:text-gray-900 dark:hover:text-gray-200' }}">
                    <svg class="h-5 w-5 transition-colors duration-200 {{ $isProfile ? 'text-indigo-600 dark:text-indigo-400' : 'text-gray-400 dark:text-gray-500 group-hover:text-gray-600 dark:group-hover:text-gray-300' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                    </svg>
                    <span>{{ __('Profile') }}</span>
                </a>

                <!-- Log Out Link -->
                <button wire:click="logout" class="group w-full flex items-center space-x-3 px-4 py-3 rounded-xl text-sm font-semibold text-gray-600 dark:text-gray-400 hover:bg-red-50 dark:hover:bg-red-950/20 hover:text-red-600 dark:hover:text-red-400 transition-all duration-200 text-left">
                    <svg class="h-5 w-5 text-gray-400 dark:text-gray-500 group-hover:text-red-500 dark:group-hover:text-red-400 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                    </svg>
                    <span>{{ __('Log Out') }}</span>
                </button>
            </div>

            <!-- Divider below Log Out / above User Card -->
            <div class="border-t border-gray-200 dark:border-gray-800"></div>

            <div class="p-3">
                <div class="flex items-center w-full px-3 py-2.5 space-x-3 rounded-xl text-left">
                    @php
                        $initials = collect(explode(' ', auth()->user()->name))->map(fn($n) => substr($n, 0, 1))->take(2)->join('');
                    @endphp
                    <div class="h-9 w-9 rounded-full bg-indigo-100 dark:bg-indigo-950 flex items-center justify-center text-indigo-700 dark:text-indigo-300 font-bold shrink-0 shadow-sm">
                        {{ strtoupper($initials) }}
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-semibold text-gray-800 dark:text-gray-200 truncate" x-data="{{ json_encode(['name' => auth()->user()->name]) }}" x-text="name" x-on:profile-updated.window="name = $event.detail.name"></p>
                        <p class="text-xs text-gray-500 dark:text-gray-400 truncate">{{ auth()->user()->email }}</p>
                    </div>
                </div>
            </div>
        </div>
    </nav>
</div>
