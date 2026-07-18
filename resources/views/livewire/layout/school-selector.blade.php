<?php

use Livewire\Volt\Component;
use App\Models\School;
use App\Models\SchoolUserRole;

new class extends Component {
    public $activeSchoolId;

    public function mount()
    {
        $user = auth()->user();
        if (!$user) {
            session()->forget('active_school_id');
            $this->activeSchoolId = null;
            return;
        }

        $accessibleSchoolIds = $this->getAccessibleSchoolIds($user);

        $this->activeSchoolId = session('active_school_id');

        // If active school is not in the accessible list, try to default to the first accessible school
        if (!$this->activeSchoolId || !in_array($this->activeSchoolId, $accessibleSchoolIds)) {
            if (count($accessibleSchoolIds) > 0) {
                $this->activeSchoolId = $accessibleSchoolIds[0];
                session(['active_school_id' => $this->activeSchoolId]);
            } else {
                session()->forget('active_school_id');
                $this->activeSchoolId = null;
            }
        }
    }

    protected function getAccessibleSchoolIds($user)
    {
        if ($user->hasRole('saas_admin')) {
            return School::orderBy('name', 'asc')->pluck('id')->toArray();
        }

        return SchoolUserRole::where('user_id', $user->id)
            ->pluck('school_id')
            ->unique()
            ->toArray();
    }

    public function updatedActiveSchoolId($value)
    {
        $user = auth()->user();
        if ($user) {
            $accessibleSchoolIds = $this->getAccessibleSchoolIds($user);
            if (in_array($value, $accessibleSchoolIds)) {
                session(['active_school_id' => $value]);
            }
        }
        
        // Refresh the current page to reload context-aware listings
        $this->redirect(request()->header('Referer'), navigate: true);
    }
};

?>

<div class="w-full sm:w-72">
    @php
        $user = auth()->user();
        if ($user) {
            if ($user->hasRole('saas_admin')) {
                $schools = \App\Models\School::orderBy('name', 'asc')->get();
            } else {
                $schools = \App\Models\School::whereIn('id', \App\Models\SchoolUserRole::where('user_id', $user->id)->pluck('school_id'))->orderBy('name', 'asc')->get();
            }
        } else {
            $schools = collect();
        }
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
