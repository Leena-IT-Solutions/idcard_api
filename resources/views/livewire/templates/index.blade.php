<?php

use Livewire\Volt\Component;

new class extends Component {
    public string $search = '';
    public string $selectedCategory = 'all';

    public function with(): array
    {
        return [
            'templates' => [
                [
                    'id' => 1,
                    'title' => 'Standard Student Badge (Portrait)',
                    'category' => 'student',
                    'orientation' => 'Portrait (85.6 x 54 mm)',
                    'dimensions' => 'CR-80 Standard',
                    'is_default' => true,
                    'preview_color' => 'from-indigo-600 to-blue-800',
                    'badge' => 'Most Popular',
                    'updated_at' => '2026-07-20',
                ],
                [
                    'id' => 2,
                    'title' => 'Modern Student Badge (Landscape)',
                    'category' => 'student',
                    'orientation' => 'Landscape (54 x 85.6 mm)',
                    'dimensions' => 'CR-80 Standard',
                    'is_default' => false,
                    'preview_color' => 'from-purple-600 to-indigo-900',
                    'badge' => 'New',
                    'updated_at' => '2026-07-21',
                ],
                [
                    'id' => 3,
                    'title' => 'Executive Staff / Teacher Pass',
                    'category' => 'staff',
                    'orientation' => 'Portrait (85.6 x 54 mm)',
                    'dimensions' => 'CR-80 Standard',
                    'is_default' => true,
                    'preview_color' => 'from-amber-500 to-slate-900',
                    'badge' => 'Staff Default',
                    'updated_at' => '2026-07-18',
                ],
                [
                    'id' => 4,
                    'title' => 'Visitor & Event Temporary Pass',
                    'category' => 'visitor',
                    'orientation' => 'Portrait (85.6 x 54 mm)',
                    'dimensions' => 'CR-80 Standard',
                    'is_default' => false,
                    'preview_color' => 'from-emerald-600 to-teal-900',
                    'badge' => 'Visitor',
                    'updated_at' => '2026-07-15',
                ],
            ]
        ];
    }
}; ?>

<div class="space-y-6">
    <!-- Top Header Banner & Stats -->
    <div class="bg-gradient-to-r from-slate-900 via-indigo-950 to-slate-900 border border-slate-800 rounded-3xl p-6 sm:p-8 shadow-xl relative overflow-hidden">
        <div class="absolute top-0 right-0 -mt-10 -mr-10 w-64 h-64 bg-indigo-500/10 rounded-full blur-3xl pointer-events-none"></div>
        <div class="relative z-10 flex flex-col md:flex-row md:items-center justify-between gap-6">
            <div>
                <div class="inline-flex items-center space-x-2 px-3 py-1 rounded-full bg-indigo-500/10 border border-indigo-500/20 text-xs font-semibold text-indigo-400 mb-3">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM4 13a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H5a1 1 0 01-1-1v-6zM16 13a1 1 0 011-1h2a1 1 0 011 1v6a1 1 0 01-1 1h-2a1 1 0 01-1-1v-6z"/>
                    </svg>
                    <span>ID Card Design Library</span>
                </div>
                <h1 class="text-2xl sm:text-3xl font-black text-white tracking-tight">ID Card Templates</h1>
                <p class="text-slate-400 text-sm mt-1 max-w-2xl">
                    Manage and customize layout templates for student ID cards, teacher passes, and visitor badges. Select default styles for your institution.
                </p>
            </div>
            
            <div class="flex items-center space-x-3 shrink-0">
                <button type="button" class="inline-flex items-center justify-center px-5 py-3 text-xs font-bold text-slate-950 bg-gradient-to-r from-amber-400 to-amber-500 hover:from-amber-300 hover:to-amber-400 rounded-xl transition duration-200 shadow-lg shadow-amber-500/10 focus:outline-none focus:ring-2 focus:ring-amber-500">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    Create Template
                </button>
            </div>
        </div>
    </div>

    <!-- Filter & Search Controls -->
    <div class="flex flex-col sm:flex-row items-center justify-between gap-4 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-4 shadow-sm">
        <!-- Search Input -->
        <div class="relative w-full sm:w-80">
            <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none">
                <svg class="h-4 w-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
            </div>
            <input wire:model.live="search" type="text" placeholder="Search templates..." class="block w-full pl-10 pr-4 py-2.5 bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl text-xs text-slate-800 dark:text-slate-200 placeholder-slate-400 focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
        </div>

        <!-- Filter Pills -->
        <div class="flex items-center space-x-2 w-full sm:w-auto overflow-x-auto pb-1 sm:pb-0">
            <button wire:click="$set('selectedCategory', 'all')" class="px-3.5 py-2 rounded-xl text-xs font-semibold transition {{ $selectedCategory === 'all' ? 'bg-indigo-600 text-white shadow-sm' : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 hover:bg-slate-200 dark:hover:bg-slate-700' }}">
                All Templates
            </button>
            <button wire:click="$set('selectedCategory', 'student')" class="px-3.5 py-2 rounded-xl text-xs font-semibold transition {{ $selectedCategory === 'student' ? 'bg-indigo-600 text-white shadow-sm' : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 hover:bg-slate-200 dark:hover:bg-slate-700' }}">
                Students
            </button>
            <button wire:click="$set('selectedCategory', 'staff')" class="px-3.5 py-2 rounded-xl text-xs font-semibold transition {{ $selectedCategory === 'staff' ? 'bg-indigo-600 text-white shadow-sm' : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 hover:bg-slate-200 dark:hover:bg-slate-700' }}">
                Staff & Teachers
            </button>
            <button wire:click="$set('selectedCategory', 'visitor')" class="px-3.5 py-2 rounded-xl text-xs font-semibold transition {{ $selectedCategory === 'visitor' ? 'bg-indigo-600 text-white shadow-sm' : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 hover:bg-slate-200 dark:hover:bg-slate-700' }}">
                Visitors
            </button>
        </div>
    </div>

    <!-- Templates Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        @foreach($templates as $tpl)
            @if(($selectedCategory === 'all' || $tpl['category'] === $selectedCategory) && (empty($search) || stripos($tpl['title'], $search) !== false))
                <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl p-5 shadow-sm hover:shadow-md transition duration-200 flex flex-col justify-between group">
                    <div>
                        <!-- Card Visual Mockup Thumbnail -->
                        <div class="h-44 w-full rounded-2xl bg-gradient-to-br {{ $tpl['preview_color'] }} p-4 relative flex flex-col justify-between overflow-hidden shadow-inner mb-4">
                            <!-- Overlay Pattern -->
                            <div class="absolute inset-0 bg-slate-950/20 backdrop-blur-[1px]"></div>
                            
                            <!-- Header Mockup -->
                            <div class="relative z-10 flex items-center justify-between">
                                <div class="flex items-center space-x-1.5">
                                    <div class="w-5 h-5 rounded-full bg-white/20 flex items-center justify-center">
                                        <div class="w-2.5 h-2.5 rounded-full bg-amber-400"></div>
                                    </div>
                                    <span class="text-[10px] font-bold text-white tracking-wider uppercase">iCard</span>
                                </div>
                                <span class="text-[9px] font-medium px-2 py-0.5 rounded-full bg-white/10 text-white backdrop-blur-md border border-white/10">
                                    {{ $tpl['badge'] }}
                                </span>
                            </div>

                            <!-- Avatar & Details Mockup -->
                            <div class="relative z-10 flex items-center space-x-3 my-auto">
                                <div class="w-12 h-12 rounded-xl bg-white/20 border border-white/30 flex items-center justify-center text-white font-bold text-sm shadow-md shrink-0">
                                    ID
                                </div>
                                <div class="space-y-1">
                                    <div class="h-2.5 w-24 bg-white/80 rounded-full"></div>
                                    <div class="h-2 w-16 bg-white/50 rounded-full"></div>
                                    <div class="h-1.5 w-20 bg-amber-400/80 rounded-full"></div>
                                </div>
                            </div>

                            <!-- Footer Bar Mockup -->
                            <div class="relative z-10 flex items-center justify-between border-t border-white/10 pt-2">
                                <div class="h-1.5 w-14 bg-white/40 rounded-full"></div>
                                <div class="h-2 w-8 bg-white/60 rounded"></div>
                            </div>
                        </div>

                        <!-- Info -->
                        <div class="space-y-2">
                            <div class="flex items-center justify-between">
                                <span class="text-[10px] font-bold uppercase tracking-wider text-indigo-600 dark:text-indigo-400 bg-indigo-50 dark:bg-indigo-950/50 px-2 py-0.5 rounded-md">
                                    {{ $tpl['category'] }}
                                </span>
                                @if($tpl['is_default'])
                                    <span class="text-[10px] font-semibold text-emerald-600 dark:text-emerald-400 flex items-center">
                                        <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                        </svg>
                                        Active Default
                                    </span>
                                @endif
                            </div>
                            
                            <h3 class="text-sm font-bold text-slate-800 dark:text-slate-200 group-hover:text-indigo-600 dark:group-hover:text-indigo-400 transition">
                                {{ $tpl['title'] }}
                            </h3>
                            <p class="text-xs text-slate-500 dark:text-slate-400">
                                {{ $tpl['orientation'] }}
                            </p>
                        </div>
                    </div>

                    <!-- Action buttons -->
                    <div class="pt-4 mt-4 border-t border-slate-100 dark:border-slate-800/80 flex items-center justify-between">
                        <button type="button" class="text-xs font-semibold text-indigo-600 dark:text-indigo-400 hover:underline">
                            Edit Template
                        </button>
                        <button type="button" class="text-xs font-semibold text-slate-400 hover:text-slate-600 dark:hover:text-slate-200">
                            Preview
                        </button>
                    </div>
                </div>
            @endif
        @endforeach
    </div>
</div>
