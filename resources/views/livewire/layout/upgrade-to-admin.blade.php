<?php

use Livewire\Volt\Component;

new class extends Component {
    public bool $showConfirmModal = false;

    public function openConfirmModal()
    {
        $this->showConfirmModal = true;
    }

    public function upgrade()
    {
        $user = auth()->user();
        if ($user && !$user->hasRole('school_admin')) {
            $user->assignRole('school_admin');
            session()->flash('message', 'Successfully upgraded to School Admin! You can now manage school profiles, grades, and divisions.');
        }

        $this->showConfirmModal = false;
        $this->redirect(route('dashboard'), navigate: true);
    }
};

?>

<div>
    @if (!auth()->user()->hasRole('school_admin') && !auth()->user()->hasRole('saas_admin'))
        <div class="bg-gradient-to-r from-violet-50 to-indigo-50 dark:from-violet-950/20 dark:to-indigo-950/20 rounded-3xl p-6 border border-violet-100/70 dark:border-violet-900/30 flex flex-col md:flex-row md:items-center md:justify-between gap-6 shadow-sm mt-6">
            <div class="flex items-start space-x-4">
                <div class="p-3.5 bg-gradient-to-br from-violet-500 to-indigo-600 text-white rounded-2xl shrink-0 shadow-md shadow-indigo-500/10">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                    </svg>
                </div>
                <div>
                    <h4 class="font-extrabold text-gray-900 dark:text-gray-100 text-base leading-tight">
                        {{ __('Want to manage a School?') }}
                    </h4>
                    <p class="text-xs text-gray-600 dark:text-gray-400 mt-1.5 leading-relaxed max-w-xl">
                        {{ __('Upgrade your account to a School Admin to register a new school profile, invite teachers, manage classes, and generate student ID cards.') }}
                    </p>
                </div>
            </div>
            <div class="shrink-0 self-end md:self-auto">
                <button type="button" wire:click="openConfirmModal" class="px-5 py-2.5 bg-gradient-to-r from-violet-600 to-indigo-600 hover:from-violet-700 hover:to-indigo-700 text-white font-extrabold text-xs uppercase tracking-wider rounded-xl transition shadow-md shadow-indigo-600/10 hover:shadow-lg">
                    {{ __('Become School Admin') }}
                </button>
            </div>
        </div>

        <!-- Confirmation Modal -->
        @if ($showConfirmModal)
            <div class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center p-4">
                <!-- Backdrop -->
                <div class="fixed inset-0 bg-gray-950/60 backdrop-blur-sm transition-opacity" wire:click="$set('showConfirmModal', false)"></div>

                <!-- Modal Container -->
                <div class="bg-white dark:bg-gray-800 rounded-3xl overflow-hidden shadow-xl transform transition-all w-full max-w-md z-50 border border-gray-100 dark:border-gray-700 p-6 sm:p-8">
                    <div class="flex items-center gap-4 text-amber-600 dark:text-amber-400 mb-4">
                        <div class="h-12 w-12 rounded-2xl bg-amber-50 dark:bg-amber-950/30 flex items-center justify-center border border-amber-100/50 dark:border-amber-950/50 shrink-0">
                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-base font-bold text-gray-900 dark:text-gray-100">
                                {{ __('Become School Admin') }}
                            </h3>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                {{ __('Action Confirmation Required') }}
                            </p>
                        </div>
                    </div>

                    <p class="text-xs text-gray-600 dark:text-gray-300 mb-6 leading-relaxed">
                        {{ __('Are you sure you want to upgrade your account to School Admin? This will grant you access to create and manage school profiles, divisions, and students.') }}
                    </p>

                    <!-- Form Actions -->
                    <div class="flex justify-end gap-3 pt-4 border-t border-gray-100 dark:border-gray-700/50">
                        <button type="button" wire:click="$set('showConfirmModal', false)" class="px-5 py-2.5 border border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700/60 rounded-xl font-bold text-xs uppercase text-gray-700 dark:text-gray-300 transition duration-150 cursor-pointer">
                            {{ __('Cancel') }}
                        </button>
                        <button type="button" wire:click="upgrade" class="px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl font-bold text-xs uppercase shadow transition duration-150 cursor-pointer">
                            {{ __('Confirm') }}
                        </button>
                    </div>
                </div>
            </div>
        @endif
    @endif
</div>
