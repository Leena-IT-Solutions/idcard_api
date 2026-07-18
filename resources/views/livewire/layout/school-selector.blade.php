<?php

use Livewire\Volt\Component;
use App\Models\School;

new class extends Component {
    public $activeSchoolId;

    public function mount()
    {
        $this->activeSchoolId = session('active_school_id');

        // If no active school in session, default to first school
        if (!$this->activeSchoolId) {
            $firstSchool = School::first();
            if ($firstSchool) {
                $this->activeSchoolId = $firstSchool->id;
                session(['active_school_id' => $firstSchool->id]);
            }
        }
    }

    public function updatedActiveSchoolId($value)
    {
        session(['active_school_id' => $value]);
        
        // Refresh the current page to reload context-aware listings
        $this->redirect(request()->header('Referer'), navigate: true);
    }
};

?>

<div class="w-full sm:w-72">
    @php
        $schools = \App\Models\School::orderBy('name', 'asc')->get();
    @endphp

    @if ($schools->isNotEmpty())
        <div class="flex items-center space-x-2">
            <span class="text-xs font-black uppercase text-gray-400 dark:text-gray-500 tracking-wider hidden md:inline shrink-0">
                {{ __('Active School:') }}
            </span>
            <select wire:model.live="activeSchoolId" class="w-full text-xs font-bold border-gray-200 dark:border-gray-700 bg-gray-50/50 dark:bg-gray-900 text-gray-800 dark:text-gray-300 rounded-xl shadow-sm focus:ring-indigo-550 focus:border-indigo-550 py-1.5 pl-3 pr-8">
                @foreach ($schools as $school)
                    <option value="{{ $school->id }}">{{ $school->name }}</option>
                @endforeach
            </select>
        </div>
    @else
        <div class="flex items-center space-x-2">
            <span class="text-xs text-amber-500 font-semibold flex items-center gap-1">
                <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                </svg>
                <a href="{{ route('schools') }}" wire:navigate class="underline hover:text-amber-600 font-extrabold uppercase text-[10px] tracking-wider">
                    {{ __('Create School Profile') }}
                </a>
            </span>
        </div>
    @endif
</div>
