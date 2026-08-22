<?php

use App\Models\User;
use App\Models\School;
use App\Models\Role;
use App\Models\Student;
use App\Models\Campaign;
use App\Models\CampaignStudent;
use App\Models\Grade;
use App\Models\Division;
use App\Models\Template;
use App\Models\Export;
use App\Models\SchoolUserRole;
use Livewire\Volt\Component;

new class extends Component
{
    public string $schoolSearch = '';
    public string $timeRange = 'all';

    public function switchSchool(int $schoolId)
    {
        session(['active_school_id' => $schoolId]);
        $this->redirect(route('students'), navigate: true);
    }

    public function with(): array
    {
        if (! auth()->user()->hasRole('saas_admin')) {
            abort(403);
        }

        // Global KPI Metrics
        $totalSchools = School::count();
        $totalUsers = User::count();
        $totalStudents = Student::count();
        $studentsWithPhoto = Student::whereNotNull('photo_path')->where('photo_path', '!=', '')->count();
        $studentsWithoutPhoto = max(0, $totalStudents - $studentsWithPhoto);
        $photoCoveragePct = $totalStudents > 0 ? round(($studentsWithPhoto / $totalStudents) * 100, 1) : 0;

        // Roles Breakdown
        $roleCounts = [
            'saas_admin' => User::whereHas('roles', fn($q) => $q->where('slug', 'saas_admin'))->count(),
            'school_admin' => User::whereHas('roles', fn($q) => $q->where('slug', 'school_admin'))->count(),
            'teacher' => User::whereHas('roles', fn($q) => $q->where('slug', 'teacher'))->count(),
            'parent' => User::whereHas('roles', fn($q) => $q->where('slug', 'parent'))->count(),
        ];

        // Academic Structure
        $today = now()->toDateString();
        $totalCampaigns = Campaign::count();
        $activeCampaigns = Campaign::where(function($q) use ($today) {
            $q->whereNull('registration_end_date')
              ->orWhere('registration_end_date', '>=', $today);
        })->count();
        $totalGrades = Grade::count();
        $totalDivisions = Division::count();
        $totalTemplates = Template::count();

        // Print & Export Engine
        $totalExports = Export::count();
        $completedExports = Export::where('status', 'completed')->count();
        $totalCardsExported = (int) Export::where('status', 'completed')->sum('processed_items');

        // ID Card Lifecycle Breakdown (Across all campaign enrollments)
        $totalEnrollments = CampaignStudent::count();
        $lifecycle = [
            'drafting' => CampaignStudent::where('status', 'drafting')->count(),
            'verified' => CampaignStudent::where(function($q) {
                $q->where('status', 'verified')->orWhereNotNull('verified_at');
            })->count(),
            'sent_for_printing' => CampaignStudent::where('status', 'sent_for_printing')->count(),
            'printed' => CampaignStudent::where('status', 'printed')->count(),
            'distributed' => CampaignStudent::where('status', 'distributed')->count(),
        ];

        // School-by-School Analytics Table
        $schoolsQuery = School::withCount(['grades'])
            ->orderBy('name', 'asc');

        if (!empty(trim($this->schoolSearch))) {
            $s = '%' . trim($this->schoolSearch) . '%';
            $schoolsQuery->where(function($q) use ($s) {
                $q->where('name', 'like', $s)
                  ->orWhere('school_code', 'like', $s)
                  ->orWhere('email', 'like', $s)
                  ->orWhere('address', 'like', $s);
            });
        }

        $schoolsList = $schoolsQuery->get()->map(function($school) use ($today) {
            $studentCount = CampaignStudent::whereHas('campaign', fn($q) => $q->where('school_id', $school->id))
                ->distinct('student_id')
                ->count('student_id');

            $teacherCount = SchoolUserRole::where('school_id', $school->id)
                ->whereHas('role', fn($q) => $q->where('slug', 'teacher'))
                ->distinct('user_id')
                ->count('user_id');

            $schoolCampaigns = Campaign::where('school_id', $school->id)->orderBy('created_at', 'desc')->get();
            $activeCampaign = $schoolCampaigns->first(function($c) use ($today) {
                return empty($c->registration_end_date) || $c->registration_end_date->format('Y-m-d') >= $today;
            }) ?? $schoolCampaigns->first();

            $verifiedStudents = 0;
            if ($activeCampaign) {
                $verifiedStudents = CampaignStudent::where('campaign_id', $activeCampaign->id)
                    ->where(function($q) {
                        $q->where('status', 'verified')->orWhereNotNull('verified_at');
                    })->count();
            }

            $totalCampaignStudents = $activeCampaign ? CampaignStudent::where('campaign_id', $activeCampaign->id)->count() : $studentCount;
            $progressPct = $totalCampaignStudents > 0 ? round(($verifiedStudents / $totalCampaignStudents) * 100, 1) : 0;

            return [
                'id' => $school->id,
                'name' => $school->name,
                'school_code' => $school->school_code,
                'logo_path' => $school->logo_path,
                'email' => $school->email,
                'contact_number' => $school->contact_number,
                'address' => $school->address,
                'students_count' => $studentCount,
                'grades_count' => $school->grades_count,
                'teachers_count' => $teacherCount,
                'active_campaign' => $activeCampaign?->name ?? 'None',
                'verified_count' => $verifiedStudents,
                'progress_pct' => $progressPct,
            ];
        });

        // Recent System Activity
        $recentUsers = User::with('roles')->orderBy('created_at', 'desc')->take(5)->get();
        $recentExports = Export::with('school')->orderBy('created_at', 'desc')->take(5)->get();

        return [
            'totalSchools' => $totalSchools,
            'totalUsers' => $totalUsers,
            'totalStudents' => $totalStudents,
            'studentsWithPhoto' => $studentsWithPhoto,
            'studentsWithoutPhoto' => $studentsWithoutPhoto,
            'photoCoveragePct' => $photoCoveragePct,
            'roleCounts' => $roleCounts,
            'totalCampaigns' => $totalCampaigns,
            'activeCampaigns' => $activeCampaigns,
            'totalGrades' => $totalGrades,
            'totalDivisions' => $totalDivisions,
            'totalTemplates' => $totalTemplates,
            'totalExports' => $totalExports,
            'completedExports' => $completedExports,
            'totalCardsExported' => $totalCardsExported,
            'totalEnrollments' => $totalEnrollments,
            'lifecycle' => $lifecycle,
            'schoolsList' => $schoolsList,
            'recentUsers' => $recentUsers,
            'recentExports' => $recentExports,
        ];
    }
}; ?>

<div class="space-y-8">
    <!-- SaaS Business Intelligence Header -->
    <div class="relative overflow-hidden bg-gradient-to-r from-indigo-900 via-indigo-800 to-slate-900 rounded-3xl p-6 sm:p-8 text-white shadow-2xl border border-indigo-700/40">
        <div class="absolute -right-10 -bottom-10 w-72 h-72 bg-indigo-500/20 rounded-full blur-3xl pointer-events-none"></div>
        <div class="absolute -left-10 -top-10 w-72 h-72 bg-emerald-500/10 rounded-full blur-3xl pointer-events-none"></div>

        <div class="relative z-10 flex flex-col md:flex-row md:items-center md:justify-between gap-6">
            <div class="space-y-2">
                <div class="inline-flex items-center gap-2 px-3 py-1 bg-white/10 backdrop-blur-md rounded-full text-xs font-bold uppercase tracking-wider text-indigo-200 border border-white/10">
                    <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                    <span>{{ __('SaaS Platform Intelligence') }}</span>
                </div>
                <h1 class="text-2xl sm:text-3xl font-black tracking-tight text-white">
                    {{ __('Business & System Administration') }}
                </h1>
                <p class="text-sm text-indigo-200/90 max-w-2xl leading-relaxed">
                    {{ __('Comprehensive live overview of multi-school operations, user base, student enrollment pipelines, card print engine throughput, and infrastructure statistics.') }}
                </p>
            </div>

            <div class="flex flex-wrap items-center gap-3">
                <a href="{{ route('schools') }}" wire:navigate class="px-4 py-2.5 bg-white text-indigo-900 hover:bg-indigo-50 font-bold text-xs uppercase tracking-wider rounded-xl transition shadow-md flex items-center gap-2">
                    <svg class="w-4 h-4 text-indigo-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                    </svg>
                    <span>{{ __('Manage Schools') }}</span>
                </a>
                <a href="{{ route('users.index') }}" wire:navigate class="px-4 py-2.5 bg-indigo-600/80 hover:bg-indigo-600 text-white font-bold text-xs uppercase tracking-wider rounded-xl transition border border-indigo-400/30 flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                    </svg>
                    <span>{{ __('User Manager') }}</span>
                </a>
            </div>
        </div>
    </div>

    <!-- 4 Primary KPI Statistics Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
        <!-- Total Schools Card -->
        <div class="bg-white dark:bg-gray-800 rounded-3xl p-6 shadow-xl shadow-gray-200/40 dark:shadow-none border border-gray-100 dark:border-gray-700 relative overflow-hidden group hover:border-indigo-400/40 transition">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Total Schools') }}</span>
                <div class="w-10 h-10 rounded-2xl bg-amber-50 dark:bg-amber-950/40 text-amber-600 dark:text-amber-400 flex items-center justify-center font-bold">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                    </svg>
                </div>
            </div>
            <div class="mt-4">
                <span class="text-3xl font-black text-gray-900 dark:text-gray-100 tracking-tight">{{ number_format($totalSchools) }}</span>
            </div>
            <div class="mt-3 flex items-center justify-between text-xs text-gray-500 dark:text-gray-400 pt-3 border-t border-gray-100 dark:border-gray-700/60">
                <span>{{ __('Active Institutions') }}</span>
                <span class="font-bold text-amber-600 dark:text-amber-400">{{ $totalSchools }} {{ __('Onboarded') }}</span>
            </div>
        </div>

        <!-- Total System Users Card -->
        <div class="bg-white dark:bg-gray-800 rounded-3xl p-6 shadow-xl shadow-gray-200/40 dark:shadow-none border border-gray-100 dark:border-gray-700 relative overflow-hidden group hover:border-indigo-400/40 transition">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Total Users') }}</span>
                <div class="w-10 h-10 rounded-2xl bg-indigo-50 dark:bg-indigo-950/40 text-indigo-600 dark:text-indigo-400 flex items-center justify-center font-bold">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                    </svg>
                </div>
            </div>
            <div class="mt-4">
                <span class="text-3xl font-black text-gray-900 dark:text-gray-100 tracking-tight">{{ number_format($totalUsers) }}</span>
            </div>
            <div class="mt-3 flex items-center justify-between text-xs text-gray-500 dark:text-gray-400 pt-3 border-t border-gray-100 dark:border-gray-700/60">
                <span class="truncate">{{ $roleCounts['school_admin'] }} Admins • {{ $roleCounts['teacher'] }} Teachers</span>
                <span class="font-bold text-indigo-600 dark:text-indigo-400">{{ $roleCounts['parent'] }} {{ __('Parents') }}</span>
            </div>
        </div>

        <!-- Total Students Card -->
        <div class="bg-white dark:bg-gray-800 rounded-3xl p-6 shadow-xl shadow-gray-200/40 dark:shadow-none border border-gray-100 dark:border-gray-700 relative overflow-hidden group hover:border-emerald-400/40 transition">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Total Students') }}</span>
                <div class="w-10 h-10 rounded-2xl bg-emerald-50 dark:bg-emerald-950/40 text-emerald-600 dark:text-emerald-400 flex items-center justify-center font-bold">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                    </svg>
                </div>
            </div>
            <div class="mt-4 flex items-baseline justify-between">
                <span class="text-3xl font-black text-gray-900 dark:text-gray-100 tracking-tight">{{ number_format($totalStudents) }}</span>
                <span class="text-xs font-bold text-emerald-600 dark:text-emerald-400">{{ $photoCoveragePct }}% {{ __('Photos') }}</span>
            </div>
            <div class="mt-3 flex items-center justify-between text-xs text-gray-500 dark:text-gray-400 pt-3 border-t border-gray-100 dark:border-gray-700/60">
                <span>{{ $studentsWithPhoto }} {{ __('With Photo') }}</span>
                <span class="text-rose-500 font-bold">{{ $studentsWithoutPhoto }} {{ __('Missing') }}</span>
            </div>
        </div>

        <!-- Cards Exported / Printed Card -->
        <div class="bg-white dark:bg-gray-800 rounded-3xl p-6 shadow-xl shadow-gray-200/40 dark:shadow-none border border-gray-100 dark:border-gray-700 relative overflow-hidden group hover:border-purple-400/40 transition">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Cards Processed') }}</span>
                <div class="w-10 h-10 rounded-2xl bg-purple-50 dark:bg-purple-950/40 text-purple-600 dark:text-purple-400 flex items-center justify-center font-bold">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                    </svg>
                </div>
            </div>
            <div class="mt-4">
                <span class="text-3xl font-black text-gray-900 dark:text-gray-100 tracking-tight">{{ number_format($totalCardsExported) }}</span>
            </div>
            <div class="mt-3 flex items-center justify-between text-xs text-gray-500 dark:text-gray-400 pt-3 border-t border-gray-100 dark:border-gray-700/60">
                <span>{{ $completedExports }} {{ __('Export Jobs') }}</span>
                <span class="font-bold text-purple-600 dark:text-purple-400">{{ $totalTemplates }} {{ __('Templates') }}</span>
            </div>
        </div>
    </div>

    <!-- 5-Stage Lifecycle Processing Pipeline -->
    <div class="bg-white dark:bg-gray-800 rounded-3xl p-6 sm:p-8 shadow-xl shadow-gray-200/40 dark:shadow-none border border-gray-100 dark:border-gray-700 space-y-6">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h3 class="text-lg font-bold text-gray-900 dark:text-gray-100 flex items-center gap-2">
                    <svg class="w-5 h-5 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <span>{{ __('Platform ID Card Lifecycle Pipeline') }}</span>
                </h3>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                    {{ __('Real-time stage distribution across all :count total active student campaign enrollments.', ['count' => number_format($totalEnrollments)]) }}
                </p>
            </div>
            <span class="px-3 py-1 bg-indigo-50 dark:bg-indigo-950/40 text-indigo-600 dark:text-indigo-400 font-extrabold text-xs rounded-xl self-start sm:self-auto">
                {{ number_format($totalEnrollments) }} {{ __('Total Enrolled Cards') }}
            </span>
        </div>

        <!-- Visual Multi-segment Progress Bar -->
        @php
            $pctDrafting = $totalEnrollments > 0 ? round(($lifecycle['drafting'] / $totalEnrollments) * 100, 1) : 0;
            $pctVerified = $totalEnrollments > 0 ? round(($lifecycle['verified'] / $totalEnrollments) * 100, 1) : 0;
            $pctSent = $totalEnrollments > 0 ? round(($lifecycle['sent_for_printing'] / $totalEnrollments) * 100, 1) : 0;
            $pctPrinted = $totalEnrollments > 0 ? round(($lifecycle['printed'] / $totalEnrollments) * 100, 1) : 0;
            $pctDistributed = $totalEnrollments > 0 ? round(($lifecycle['distributed'] / $totalEnrollments) * 100, 1) : 0;
        @endphp
        <div class="w-full h-4 bg-gray-100 dark:bg-gray-700/60 rounded-full overflow-hidden flex shadow-inner">
            <div style="width: {{ $pctDrafting }}%" class="bg-slate-400 transition-all duration-500" title="Drafting: {{ $lifecycle['drafting'] }} ({{ $pctDrafting }}%)"></div>
            <div style="width: {{ $pctVerified }}%" class="bg-emerald-500 transition-all duration-500" title="Verified: {{ $lifecycle['verified'] }} ({{ $pctVerified }}%)"></div>
            <div style="width: {{ $pctSent }}%" class="bg-blue-500 transition-all duration-500" title="Sent to Print: {{ $lifecycle['sent_for_printing'] }} ({{ $pctSent }}%)"></div>
            <div style="width: {{ $pctPrinted }}%" class="bg-purple-500 transition-all duration-500" title="Printed: {{ $lifecycle['printed'] }} ({{ $pctPrinted }}%)"></div>
            <div style="width: {{ $pctDistributed }}%" class="bg-teal-500 transition-all duration-500" title="Distributed: {{ $lifecycle['distributed'] }} ({{ $pctDistributed }}%)"></div>
        </div>

        <!-- 5-Stage Counters Cards Grid -->
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-4">
            <!-- 1. Drafting -->
            <div class="p-4 rounded-2xl bg-slate-50 dark:bg-slate-900/50 border border-slate-200/80 dark:border-slate-800">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-[10px] font-extrabold uppercase tracking-wider text-slate-600 dark:text-slate-400">{{ __('1. Drafting') }}</span>
                    <span class="w-2 h-2 rounded-full bg-slate-400"></span>
                </div>
                <div class="text-xl font-black text-slate-900 dark:text-slate-100">{{ number_format($lifecycle['drafting']) }}</div>
                <div class="text-[11px] text-slate-500 dark:text-slate-400 mt-1 font-semibold">{{ $pctDrafting }}% {{ __('of total') }}</div>
            </div>

            <!-- 2. Verified -->
            <div class="p-4 rounded-2xl bg-emerald-50 dark:bg-emerald-950/30 border border-emerald-200/80 dark:border-emerald-900/40">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-[10px] font-extrabold uppercase tracking-wider text-emerald-700 dark:text-emerald-400">{{ __('2. Verified') }}</span>
                    <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                </div>
                <div class="text-xl font-black text-emerald-900 dark:text-emerald-100">{{ number_format($lifecycle['verified']) }}</div>
                <div class="text-[11px] text-emerald-600 dark:text-emerald-400 mt-1 font-semibold">{{ $pctVerified }}% {{ __('ready to print') }}</div>
            </div>

            <!-- 3. Sent to Print -->
            <div class="p-4 rounded-2xl bg-blue-50 dark:bg-blue-950/30 border border-blue-200/80 dark:border-blue-900/40">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-[10px] font-extrabold uppercase tracking-wider text-blue-700 dark:text-blue-400">{{ __('3. Sent to Print') }}</span>
                    <span class="w-2 h-2 rounded-full bg-blue-500"></span>
                </div>
                <div class="text-xl font-black text-blue-900 dark:text-blue-100">{{ number_format($lifecycle['sent_for_printing']) }}</div>
                <div class="text-[11px] text-blue-600 dark:text-blue-400 mt-1 font-semibold">{{ $pctSent }}% {{ __('at press') }}</div>
            </div>

            <!-- 4. Printed -->
            <div class="p-4 rounded-2xl bg-purple-50 dark:bg-purple-950/30 border border-purple-200/80 dark:border-purple-900/40">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-[10px] font-extrabold uppercase tracking-wider text-purple-700 dark:text-purple-400">{{ __('4. Printed') }}</span>
                    <span class="w-2 h-2 rounded-full bg-purple-500"></span>
                </div>
                <div class="text-xl font-black text-purple-900 dark:text-purple-100">{{ number_format($lifecycle['printed']) }}</div>
                <div class="text-[11px] text-purple-600 dark:text-purple-400 mt-1 font-semibold">{{ $pctPrinted }}% {{ __('completed') }}</div>
            </div>

            <!-- 5. Distributed -->
            <div class="p-4 rounded-2xl bg-teal-50 dark:bg-teal-950/30 border border-teal-200/80 dark:border-teal-900/40 col-span-2 sm:col-span-1">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-[10px] font-extrabold uppercase tracking-wider text-teal-700 dark:text-teal-400">{{ __('5. Distributed') }}</span>
                    <span class="w-2 h-2 rounded-full bg-teal-500"></span>
                </div>
                <div class="text-xl font-black text-teal-900 dark:text-teal-100">{{ number_format($lifecycle['distributed']) }}</div>
                <div class="text-[11px] text-teal-600 dark:text-teal-400 mt-1 font-semibold">{{ $pctDistributed }}% {{ __('delivered') }}</div>
            </div>
        </div>
    </div>

    <!-- Secondary Operations Breakdown Grid -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <!-- Academic Structure Breakdown -->
        <div class="bg-white dark:bg-gray-800 rounded-3xl p-6 shadow-xl shadow-gray-200/40 dark:shadow-none border border-gray-100 dark:border-gray-700 space-y-4">
            <h4 class="text-sm font-extrabold uppercase tracking-wider text-gray-500 dark:text-gray-400 flex items-center gap-2">
                <svg class="w-4 h-4 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                </svg>
                <span>{{ __('Academic Setup') }}</span>
            </h4>
            <div class="space-y-3">
                <div class="flex items-center justify-between p-3 rounded-2xl bg-gray-50 dark:bg-gray-900/50">
                    <span class="text-xs text-gray-600 dark:text-gray-400 font-medium">{{ __('Active Campaigns') }}</span>
                    <span class="text-xs font-bold text-gray-900 dark:text-gray-100">{{ $activeCampaigns }} / {{ $totalCampaigns }}</span>
                </div>
                <div class="flex items-center justify-between p-3 rounded-2xl bg-gray-50 dark:bg-gray-900/50">
                    <span class="text-xs text-gray-600 dark:text-gray-400 font-medium">{{ __('Classes & Standards') }}</span>
                    <span class="text-xs font-bold text-gray-900 dark:text-gray-100">{{ $totalGrades }}</span>
                </div>
                <div class="flex items-center justify-between p-3 rounded-2xl bg-gray-50 dark:bg-gray-900/50">
                    <span class="text-xs text-gray-600 dark:text-gray-400 font-medium">{{ __('Class Divisions') }}</span>
                    <span class="text-xs font-bold text-gray-900 dark:text-gray-100">{{ $totalDivisions }}</span>
                </div>
            </div>
        </div>

        <!-- User Roles Breakdown -->
        <div class="bg-white dark:bg-gray-800 rounded-3xl p-6 shadow-xl shadow-gray-200/40 dark:shadow-none border border-gray-100 dark:border-gray-700 space-y-4">
            <h4 class="text-sm font-extrabold uppercase tracking-wider text-gray-500 dark:text-gray-400 flex items-center gap-2">
                <svg class="w-4 h-4 text-amber-600 dark:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                </svg>
                <span>{{ __('User Role Distribution') }}</span>
            </h4>
            <div class="space-y-3">
                <div class="flex items-center justify-between p-3 rounded-2xl bg-amber-50/50 dark:bg-amber-950/20">
                    <span class="text-xs text-amber-800 dark:text-amber-300 font-medium">{{ __('School Admins') }}</span>
                    <span class="text-xs font-bold text-amber-900 dark:text-amber-200">{{ $roleCounts['school_admin'] }}</span>
                </div>
                <div class="flex items-center justify-between p-3 rounded-2xl bg-emerald-50/50 dark:bg-emerald-950/20">
                    <span class="text-xs text-emerald-800 dark:text-emerald-300 font-medium">{{ __('Teachers & Staff') }}</span>
                    <span class="text-xs font-bold text-emerald-900 dark:text-emerald-200">{{ $roleCounts['teacher'] }}</span>
                </div>
                <div class="flex items-center justify-between p-3 rounded-2xl bg-purple-50/50 dark:bg-purple-950/20">
                    <span class="text-xs text-purple-800 dark:text-purple-300 font-medium">{{ __('Parent Accounts') }}</span>
                    <span class="text-xs font-bold text-purple-900 dark:text-purple-200">{{ $roleCounts['parent'] }}</span>
                </div>
            </div>
        </div>

        <!-- Export & Print Engine Health -->
        <div class="bg-white dark:bg-gray-800 rounded-3xl p-6 shadow-xl shadow-gray-200/40 dark:shadow-none border border-gray-100 dark:border-gray-700 space-y-4">
            <h4 class="text-sm font-extrabold uppercase tracking-wider text-gray-500 dark:text-gray-400 flex items-center gap-2">
                <svg class="w-4 h-4 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                </svg>
                <span>{{ __('Export Engine Health') }}</span>
            </h4>
            <div class="space-y-3">
                <div class="flex items-center justify-between p-3 rounded-2xl bg-gray-50 dark:bg-gray-900/50">
                    <span class="text-xs text-gray-600 dark:text-gray-400 font-medium">{{ __('Total Export Jobs') }}</span>
                    <span class="text-xs font-bold text-gray-900 dark:text-gray-100">{{ $totalExports }}</span>
                </div>
                <div class="flex items-center justify-between p-3 rounded-2xl bg-emerald-50/50 dark:bg-emerald-950/20">
                    <span class="text-xs text-emerald-800 dark:text-emerald-300 font-medium">{{ __('Successful Exports') }}</span>
                    <span class="text-xs font-bold text-emerald-900 dark:text-emerald-200">{{ $completedExports }} ({{ $totalExports > 0 ? round(($completedExports / $totalExports) * 100) : 100 }}%)</span>
                </div>
                <div class="flex items-center justify-between p-3 rounded-2xl bg-gray-50 dark:bg-gray-900/50">
                    <span class="text-xs text-gray-600 dark:text-gray-400 font-medium">{{ __('ID Card Templates') }}</span>
                    <span class="text-xs font-bold text-gray-900 dark:text-gray-100">{{ $totalTemplates }}</span>
                </div>
            </div>
        </div>
    </div>

    <!-- School Performance & Analytics Directory -->
    <div class="bg-white dark:bg-gray-800 rounded-3xl p-6 sm:p-8 shadow-xl shadow-gray-200/40 dark:shadow-none border border-gray-100 dark:border-gray-700 space-y-6">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h3 class="text-lg font-bold text-gray-900 dark:text-gray-100 flex items-center gap-2">
                    <svg class="w-5 h-5 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                    </svg>
                    <span>{{ __('School-by-School Overview') }}</span>
                </h3>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                    {{ __('Detailed breakdown of student enrollments, verification status, and staff per institution.') }}
                </p>
            </div>

            <!-- Search School Field -->
            <div class="w-full sm:w-72">
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <svg class="h-4 w-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                    </div>
                    <input type="text" wire:model.live.debounce.300ms="schoolSearch" placeholder="{{ __('Search School Name / Code...') }}" class="w-full pl-9 pr-4 py-2 border-gray-200 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-xl text-xs focus:ring-indigo-500 focus:border-indigo-500 placeholder-gray-400" />
                </div>
            </div>
        </div>

        <!-- Schools Table -->
        <div class="overflow-x-auto rounded-2xl border border-gray-100 dark:border-gray-700">
            <table class="w-full text-left text-xs">
                <thead class="bg-gray-50 dark:bg-gray-900/60 text-gray-500 dark:text-gray-400 uppercase font-extrabold text-[10px] tracking-wider border-b border-gray-100 dark:border-gray-700">
                    <tr>
                        <th class="px-4 py-3.5">{{ __('Institution') }}</th>
                        <th class="px-4 py-3.5">{{ __('Students') }}</th>
                        <th class="px-4 py-3.5">{{ __('Teachers') }}</th>
                        <th class="px-4 py-3.5">{{ __('Active Campaign') }}</th>
                        <th class="px-4 py-3.5">{{ __('Verification Progress') }}</th>
                        <th class="px-4 py-3.5 text-right">{{ __('Action') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700/60">
                    @forelse ($schoolsList as $sch)
                        <tr class="hover:bg-gray-50/80 dark:hover:bg-gray-700/30 transition">
                            <td class="px-4 py-3.5">
                                <div class="flex items-center gap-3">
                                    <div class="w-9 h-9 rounded-xl bg-indigo-50 dark:bg-indigo-950/40 text-indigo-600 dark:text-indigo-400 flex items-center justify-center font-bold text-xs shrink-0">
                                        @if ($sch['logo_path'])
                                            <img src="{{ asset('storage/' . $sch['logo_path']) }}" class="w-full h-full object-cover rounded-xl" alt="Logo">
                                        @else
                                            {{ strtoupper(substr($sch['name'], 0, 2)) }}
                                        @endif
                                    </div>
                                    <div>
                                        <div class="font-bold text-gray-900 dark:text-gray-100 text-xs">{{ $sch['name'] }}</div>
                                        <div class="text-[10px] text-gray-400 font-mono">{{ $sch['school_code'] ?: 'CODE-'.$sch['id'] }} • {{ $sch['grades_count'] }} {{ __('Classes') }}</div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-3.5 font-bold text-gray-900 dark:text-gray-100">
                                {{ number_format($sch['students_count']) }}
                            </td>
                            <td class="px-4 py-3.5 font-semibold text-gray-600 dark:text-gray-300">
                                {{ $sch['teachers_count'] }}
                            </td>
                            <td class="px-4 py-3.5">
                                <span class="px-2.5 py-1 bg-indigo-50 dark:bg-indigo-950/40 text-indigo-600 dark:text-indigo-400 font-bold rounded-lg text-[10px]">
                                    {{ $sch['active_campaign'] }}
                                </span>
                            </td>
                            <td class="px-4 py-3.5">
                                <div class="w-36 space-y-1">
                                    <div class="flex items-center justify-between text-[10px]">
                                        <span class="text-gray-500">{{ $sch['verified_count'] }} {{ __('verified') }}</span>
                                        <span class="font-bold text-emerald-600 dark:text-emerald-400">{{ $sch['progress_pct'] }}%</span>
                                    </div>
                                    <div class="w-full h-1.5 bg-gray-100 dark:bg-gray-700 rounded-full overflow-hidden">
                                        <div style="width: {{ $sch['progress_pct'] }}%" class="h-full bg-emerald-500 rounded-full"></div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-3.5 text-right">
                                <button wire:click="switchSchool({{ $sch['id'] }})" class="px-3 py-1.5 bg-indigo-50 hover:bg-indigo-100 dark:bg-indigo-950/50 dark:hover:bg-indigo-900/60 text-indigo-600 dark:text-indigo-400 font-bold text-[11px] rounded-xl transition cursor-pointer" title="Switch to this school">
                                    {{ __('Switch School') }}
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-8 text-center text-gray-400">
                                {{ __('No schools found matching your search.') }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Recent Activity Dual Columns (Recent Signups & Recent Exports) -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Recent Registered Users -->
        <div class="bg-white dark:bg-gray-800 rounded-3xl p-6 shadow-xl shadow-gray-200/40 dark:shadow-none border border-gray-100 dark:border-gray-700 space-y-4">
            <div class="flex items-center justify-between">
                <h4 class="text-sm font-extrabold uppercase tracking-wider text-gray-500 dark:text-gray-400 flex items-center gap-2">
                    <svg class="w-4 h-4 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/>
                    </svg>
                    <span>{{ __('Recent User Signups') }}</span>
                </h4>
                <a href="{{ route('users.index') }}" wire:navigate class="text-xs font-bold text-indigo-600 hover:text-indigo-700 dark:text-indigo-400">
                    {{ __('View All') }} &rarr;
                </a>
            </div>

            <div class="space-y-3">
                @forelse ($recentUsers as $ru)
                    <div class="p-3 bg-gray-50 dark:bg-gray-900/50 rounded-2xl flex items-center justify-between text-xs">
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 rounded-xl bg-indigo-100 dark:bg-indigo-950/70 text-indigo-700 dark:text-indigo-300 font-bold text-xs flex items-center justify-center">
                                {{ strtoupper(substr($ru->name, 0, 1)) }}
                            </div>
                            <div>
                                <div class="font-bold text-gray-900 dark:text-gray-100">{{ $ru->name }}</div>
                                <div class="text-[10px] text-gray-400">{{ $ru->email }} • {{ $ru->mobile }}</div>
                            </div>
                        </div>
                        <div class="text-right">
                            <div class="flex flex-wrap gap-1 justify-end">
                                @foreach ($ru->roles as $role)
                                    <span class="px-2 py-0.5 rounded text-[8px] font-bold uppercase tracking-wider bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300">
                                        {{ $role->name }}
                                    </span>
                                @endforeach
                            </div>
                            <span class="text-[9px] text-gray-400 mt-1 block">{{ $ru->created_at->diffForHumans() }}</span>
                        </div>
                    </div>
                @empty
                    <div class="text-center py-4 text-xs text-gray-400">
                        {{ __('No recent users.') }}
                    </div>
                @endforelse
            </div>
        </div>

        <!-- Recent Bulk Export Operations -->
        <div class="bg-white dark:bg-gray-800 rounded-3xl p-6 shadow-xl shadow-gray-200/40 dark:shadow-none border border-gray-100 dark:border-gray-700 space-y-4">
            <div class="flex items-center justify-between">
                <h4 class="text-sm font-extrabold uppercase tracking-wider text-gray-500 dark:text-gray-400 flex items-center gap-2">
                    <svg class="w-4 h-4 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                    </svg>
                    <span>{{ __('Recent Export Tasks') }}</span>
                </h4>
            </div>

            <div class="space-y-3">
                @forelse ($recentExports as $re)
                    <div class="p-3 bg-gray-50 dark:bg-gray-900/50 rounded-2xl flex items-center justify-between text-xs">
                        <div>
                            <div class="font-bold text-gray-900 dark:text-gray-100 uppercase text-[11px] flex items-center gap-1.5">
                                {{ str_replace('_', ' ', $re->type) }}
                                <span class="text-[10px] text-gray-400 font-normal lowercase">({{ $re->school?->name ?? 'Global' }})</span>
                            </div>
                            <div class="text-[10px] text-gray-400">{{ $re->processed_items }}/{{ $re->total_items ?? 0 }} items • {{ $re->created_at->diffForHumans() }}</div>
                        </div>
                        <div>
                            @if ($re->status === 'completed')
                                <span class="px-2.5 py-1 bg-emerald-50 dark:bg-emerald-950/40 text-emerald-600 dark:text-emerald-400 font-bold rounded-lg text-[10px]">
                                    COMPLETED
                                </span>
                            @elseif ($re->status === 'failed')
                                <span class="px-2.5 py-1 bg-rose-50 dark:bg-rose-950/40 text-rose-600 dark:text-rose-400 font-bold rounded-lg text-[10px]">
                                    FAILED
                                </span>
                            @else
                                <span class="px-2.5 py-1 bg-blue-50 dark:bg-blue-950/40 text-blue-600 dark:text-blue-400 font-bold rounded-lg text-[10px]">
                                    {{ strtoupper($re->status) }}
                                </span>
                            @endif
                        </div>
                    </div>
                @empty
                    <div class="text-center py-4 text-xs text-gray-400">
                        {{ __('No recent exports.') }}
                    </div>
                @endforelse
            </div>
        </div>
    </div>
</div>
