<?php

use Livewire\Volt\Component;
use App\Models\SchoolInvitation;
use App\Models\SchoolUserRole;
use App\Models\SchoolUserRoleAssignment;
use App\Models\User;

new class extends Component {
    public $invitations = [];

    public function mount()
    {
        $this->loadInvitations();
    }

    public function loadInvitations()
    {
        $user = auth()->user();
        if (!$user) {
            $this->invitations = [];
            return;
        }

        $this->invitations = SchoolInvitation::with(['school', 'role', 'assignments.grade', 'assignments.division'])
            ->where('status', 'pending')
            ->where(function ($q) use ($user) {
                $q->where('user_id', $user->id);
                if ($user->email) {
                    $q->orWhere('email', $user->email);
                }
                if ($user->mobile) {
                    $q->orWhere('mobile', $user->mobile);
                }
            })
            ->get();
    }

    public function acceptInvite($id)
    {
        $user = auth()->user();
        $invite = SchoolInvitation::with('assignments')->findOrFail($id);

        // 1. Create mapping under school user roles
        $userRole = SchoolUserRole::create([
            'user_id' => $user->id,
            'school_id' => $invite->school_id,
            'role_id' => $invite->role_id,
            'grade_id' => $invite->grade_id,
            'division_id' => $invite->division_id,
        ]);

        foreach ($invite->assignments as $asg) {
            SchoolUserRoleAssignment::create([
                'school_user_role_id' => $userRole->id,
                'grade_id' => $asg->grade_id,
                'division_id' => $asg->division_id,
            ]);
        }

        // 2. Sync to user's standard roles pivot table
        $user->roles()->syncWithoutDetaching([$invite->role_id]);

        // 3. Mark invite as accepted
        $invite->update([
            'status' => 'accepted',
            'user_id' => $user->id,
        ]);

        // 4. Set this school as active in session
        session(['active_school_id' => $invite->school_id]);

        session()->flash('message', "Successfully joined {$invite->school->name}!");

        // Refresh the page
        $this->redirect(route('dashboard'), navigate: true);
    }

    public function declineInvite($id)
    {
        $user = auth()->user();
        $invite = SchoolInvitation::findOrFail($id);

        // Mark invite as declined
        $invite->update([
            'status' => 'declined',
            'user_id' => $user->id,
        ]);

        session()->flash('message', "Declined join invitation to {$invite->school->name}.");
        $this->loadInvitations();
    }
};

?>

<div>
    @if (count($invitations) > 0)
        <div class="space-y-4 mb-6">
            @foreach ($invitations as $invite)
                <div class="bg-gradient-to-r from-indigo-50 to-indigo-100/50 dark:from-indigo-950/20 dark:to-indigo-900/10 rounded-3xl p-6 border border-indigo-100/60 dark:border-indigo-900/30 flex flex-col md:flex-row md:items-center md:justify-between gap-4 shadow-sm">
                    <div class="flex items-center space-x-4">
                        <div class="p-3.5 bg-indigo-600 text-white rounded-2xl shrink-0 shadow-md shadow-indigo-600/10">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                            </svg>
                        </div>
                        <div>
                            <span class="text-[9px] uppercase font-black tracking-widest text-indigo-600 dark:text-indigo-400 block mb-0.5">{{ __('Join Invitation') }}</span>
                            <h4 class="font-extrabold text-gray-900 dark:text-gray-100 text-base leading-tight">
                                {{ __('Invitation from') }} <span class="text-indigo-600 dark:text-indigo-400">{{ $invite->school->name }}</span>
                            </h4>
                            <p class="text-xs text-gray-600 dark:text-gray-400 mt-1">
                                {{ __('You are invited to join as a') }} 
                                <span class="font-bold text-gray-800 dark:text-gray-300">{{ $invite->role->name }}</span>
                                @if ($invite->role->slug === 'teacher')
                                    @if (count($invite->assignments) > 0)
                                        {{ __('for') }}
                                        @foreach ($invite->assignments as $asg)
                                            <span class="font-bold text-indigo-600 dark:text-indigo-400">{{ $asg->grade->name }} - {{ $asg->division->name }}</span>{{ !$loop->last ? ', ' : '' }}
                                        @endforeach
                                    @elseif ($invite->grade && $invite->division)
                                        {{ __('for') }} <span class="font-bold text-indigo-600 dark:text-indigo-400">{{ $invite->grade->name }} - {{ $invite->division->name }}</span>
                                    @endif
                                @endif.
                            </p>
                        </div>
                    </div>
                    <div class="flex items-center gap-3 shrink-0 self-end md:self-auto">
                        <button type="button" wire:click="declineInvite({{ $invite->id }})" class="px-4 py-2 bg-transparent hover:bg-gray-150/40 dark:hover:bg-gray-800 border border-gray-300 dark:border-gray-700 text-gray-700 dark:text-gray-300 font-bold text-xs uppercase tracking-wider rounded-xl transition">
                            {{ __('Decline') }}
                        </button>
                        <button type="button" wire:click="acceptInvite({{ $invite->id }})" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-xs uppercase tracking-wider rounded-xl transition shadow shadow-indigo-600/10">
                            {{ __('Accept') }}
                        </button>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>
