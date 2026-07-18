<?php

use Livewire\Volt\Component;

new class extends Component {
    public function upgrade()
    {
        $user = auth()->user();
        if ($user && !$user->hasRole('school_admin')) {
            $user->assignRole('school_admin');
            session()->flash('message', 'Successfully upgraded to School Admin! You can now manage school profiles, grades, and divisions.');
        }

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
                    <p class="text-xs text-gray-550 dark:text-gray-405 mt-1.5 leading-relaxed max-w-xl">
                        {{ __('Upgrade your account to a School Admin to register a new school profile, invite teachers, manage classes, and generate student ID cards.') }}
                    </p>
                </div>
            </div>
            <div class="shrink-0 self-end md:self-auto">
                <button type="button" wire:click="upgrade" class="px-5 py-2.5 bg-gradient-to-r from-violet-600 to-indigo-600 hover:from-violet-700 hover:to-indigo-700 text-white font-extrabold text-xs uppercase tracking-wider rounded-xl transition shadow-md shadow-indigo-600/10 hover:shadow-lg">
                    {{ __('Become School Admin') }}
                </button>
            </div>
        </div>
    @endif
</div>
