<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

        <script>
            document.documentElement.classList.remove('dark');
            localStorage.removeItem('color-theme');
        </script>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />
        <link rel="stylesheet" href="{{ asset('css/fonts.css') }}">

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased text-gray-900 bg-gray-100">
        <div class="min-h-screen bg-gray-100 flex flex-col lg:flex-row">
            <!-- Sidebar Navigation -->
            <livewire:layout.navigation />

            <!-- Main Content Area with offset for sidebar -->
            <div class="flex-1 lg:ps-64 w-full pt-16 lg:pt-0">
                <!-- Page Heading -->
                @if (isset($header))
                    <header class="bg-white shadow border-b border-gray-200">
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
