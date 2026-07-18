<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Dashboard') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="mx-auto sm:px-6 lg:px-8 space-y-6">
            <!-- Pending Invitations -->
            <livewire:layout.pending-invitations />

            <!-- Upgrade to School Admin Option -->
            <livewire:layout.upgrade-to-admin />

            <!-- Welcome Card -->
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow sm:rounded-3xl">
                <div class="p-6 text-gray-900 dark:text-gray-100 font-medium">
                    {{ __("Welcome back, ") . auth()->user()->name . "!" }}
                </div>
            </div>

            <!-- Parent Portal CRUD & Enrollment Panel -->
            <livewire:dashboard.parent-portal />
        </div>
    </div>
</x-app-layout>
