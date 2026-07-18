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
                    $isDashboard = request()->routeIs('dashboard');
                @endphp
                <a href="{{ route('dashboard') }}" wire:navigate class="group w-full flex items-center space-x-3 px-4 py-3 rounded-xl text-sm font-semibold transition-all duration-200 {{ $isDashboard ? 'bg-indigo-50 dark:bg-indigo-950/30 text-indigo-600 dark:text-indigo-400 shadow-sm' : 'text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-800/40 hover:text-gray-900 dark:hover:text-gray-200' }}">
                    <svg class="h-5 w-5 transition-colors duration-200 {{ $isDashboard ? 'text-indigo-600 dark:text-indigo-400' : 'text-gray-400 dark:text-gray-500 group-hover:text-gray-600 dark:group-hover:text-gray-300' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                    </svg>
                    <span>{{ __('Dashboard') }}</span>
                </a>

                @if(auth()->user()->hasAnyRole(['saas_admin', 'school_admin']))
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
            </div>
        </div>

        <!-- Bottom Section: Profile & User Card -->
        <div>
            <div class="px-3 py-2 space-y-1">
                @php
                    $isUpdateSystem = request()->routeIs('update-system');
                @endphp
                <a href="{{ route('update-system') }}" wire:navigate class="group w-full flex items-center space-x-3 px-4 py-3 rounded-xl text-sm font-semibold transition-all duration-200 {{ $isUpdateSystem ? 'bg-indigo-50 dark:bg-indigo-950/30 text-indigo-600 dark:text-indigo-400 shadow-sm' : 'text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-800/40 hover:text-gray-900 dark:hover:text-gray-200' }}">
                    <svg class="h-5 w-5 transition-colors duration-200 {{ $isUpdateSystem ? 'text-indigo-600 dark:text-indigo-400' : 'text-gray-400 dark:text-gray-500 group-hover:text-gray-600 dark:group-hover:text-gray-300' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 1121.21 7.89H17v4.11"/>
                    </svg>
                    <span>{{ __('Update System') }}</span>
                </a>

                @php
                    $isProfile = request()->routeIs('profile');
                @endphp
                <a href="{{ route('profile') }}" wire:navigate class="group w-full flex items-center space-x-3 px-4 py-3 rounded-xl text-sm font-semibold transition-all duration-200 {{ $isProfile ? 'bg-indigo-50 dark:bg-indigo-950/30 text-indigo-600 dark:text-indigo-400 shadow-sm' : 'text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-800/40 hover:text-gray-900 dark:hover:text-gray-200' }}">
                    <svg class="h-5 w-5 transition-colors duration-200 {{ $isProfile ? 'text-indigo-600 dark:text-indigo-400' : 'text-gray-400 dark:text-gray-500 group-hover:text-gray-600 dark:group-hover:text-gray-300' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                    </svg>
                    <span>{{ __('Profile') }}</span>
                </a>
            </div>

            <!-- Divider below Profile / above User Card -->
            <div class="border-t border-gray-200 dark:border-gray-800"></div>

            <div class="p-3">
                <x-dropdown align="bottom" width="48" contentClasses="mb-14">
                    <x-slot name="trigger">
                        <button class="flex items-center w-full px-3 py-2.5 space-x-3 rounded-xl hover:bg-gray-100 dark:hover:bg-gray-800 transition text-left group">
                            @php
                                $initials = collect(explode(' ', auth()->user()->name))->map(fn($n) => substr($n, 0, 1))->take(2)->join('');
                            @endphp
                            <div class="h-9 w-9 rounded-full bg-indigo-100 dark:bg-indigo-950 flex items-center justify-center text-indigo-700 dark:text-indigo-300 font-bold shrink-0 shadow-sm group-hover:scale-105 transition-transform duration-200">
                                {{ strtoupper($initials) }}
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-semibold text-gray-800 dark:text-gray-200 truncate" x-data="{{ json_encode(['name' => auth()->user()->name]) }}" x-text="name" x-on:profile-updated.window="name = $event.detail.name"></p>
                                <p class="text-xs text-gray-500 dark:text-gray-400 truncate">{{ auth()->user()->email }}</p>
                            </div>
                            <svg class="h-4 w-4 text-gray-400 group-hover:text-gray-600 dark:group-hover:text-gray-300 transition-colors" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M10 3a1 1 0 01.707.293l3 3a1 1 0 01-1.414 1.414L10 5.414 7.707 7.707a1 1 0 01-1.414-1.414l3-3A1 1 0 0110 3zm-3.707 9.293a1 1 0 011.414 0L10 14.586l2.293-2.293a1 1 0 011.414 1.414l-3 3a1 1 0 01-1.414 0l-3-3a1 1 0 010-1.414z" clip-rule="evenodd" />
                            </svg>
                        </button>
                    </x-slot>

                    <x-slot name="content">
                        <x-dropdown-link :href="route('profile')" wire:navigate>
                            {{ __('Profile') }}
                        </x-dropdown-link>

                        <button wire:click="logout" class="w-full text-start">
                            <x-dropdown-link>
                                {{ __('Log Out') }}
                            </x-dropdown-link>
                        </button>
                    </x-slot>
                </x-dropdown>
            </div>
        </div>
    </nav>
</div>
