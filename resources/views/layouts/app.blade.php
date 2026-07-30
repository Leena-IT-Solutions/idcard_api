<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

        <!-- Script to sync Dark Mode class based on system / localStorage -->
        <script>
            if (localStorage.getItem('color-theme') === 'dark' || (!('color-theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
                document.documentElement.classList.add('dark');
            } else {
                document.documentElement.classList.remove('dark');
            }
        </script>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />
        <link rel="stylesheet" href="{{ asset('css/fonts.css') }}">

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased text-gray-900 dark:text-gray-100 bg-gray-100 dark:bg-gray-900">
        <div class="min-h-screen bg-gray-100 dark:bg-gray-900 flex flex-col lg:flex-row">
            <!-- Sidebar Navigation -->
            <livewire:layout.navigation />

            <!-- Main Content Area with offset for sidebar -->
            <div class="flex-1 lg:ps-64 w-full">
                <!-- Page Heading -->
                @if (isset($header))
                    <header class="bg-white dark:bg-gray-800 shadow border-b border-gray-200 dark:border-gray-700">
                        <div class="w-full py-4 px-4 sm:px-6 lg:px-8 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                            <div>
                                {{ $header }}
                            </div>
                            <div class="flex items-center shrink-0">
                                <livewire:layout.school-selector />
                            </div>
                        </div>
                    </header>
                @endif

                <!-- Page Content -->
                <main class="w-full">
                    {{ $slot }}
                </main>
            </div>
        </div>
    </body>
</html>
