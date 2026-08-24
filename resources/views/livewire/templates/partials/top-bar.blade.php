{{-- Top Action Bar (Pro Studio Header) --}}
@if(session()->has('message'))
    <div class="bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 p-4 rounded-2xl flex items-center justify-between text-sm font-semibold mb-4">
        <span>{{ session('message') }}</span>
        <button type="button" onclick="this.parentElement.remove()" class="text-emerald-400 hover:text-white">&times;</button>
    </div>
@endif

<div class="bg-white/90 backdrop-blur-xl border border-slate-200/80 rounded-2xl p-4 flex flex-wrap items-center justify-between gap-4 shadow-sm">
    <div class="flex items-center space-x-4">
        <a href="{{ route('templates') }}" class="p-2.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-600 hover:text-slate-900 transition flex items-center justify-center border border-slate-200/60" title="Back to Templates">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
            </svg>
        </a>
        <div>
            <div class="flex items-center space-x-2.5">
                <input type="text" wire:model.live="templateName" class="bg-transparent border-b border-dashed border-slate-300 hover:border-indigo-500 text-lg font-black text-slate-900 focus:outline-none focus:border-indigo-600 transition px-1 py-0.5" placeholder="Untitled Template">
                <span class="text-[10px] font-black text-indigo-700 bg-indigo-50 border border-indigo-200/80 px-2.5 py-0.5 rounded-full uppercase tracking-wider">
                    {{ strtoupper($type) }} • CR-80
                </span>
            </div>
            <p class="text-xs text-slate-500 mt-0.5 font-medium flex items-center gap-1.5">
                <span class="w-2 h-2 rounded-full bg-emerald-500 inline-block animate-pulse"></span>
                <span>Canva Studio • Drag layers, snap to guides, edit text & shapes</span>
            </p>
        </div>
    </div>

    <!-- Studio Quick Tools Bar -->
    <div class="flex items-center space-x-2.5">
        <!-- Orientation Switcher -->
        <div class="flex items-center bg-slate-100 border border-slate-200/80 p-1 rounded-xl">
            <button type="button" wire:click="setOrientation('landscape')" title="Landscape Orientation (85.6mm x 54mm)" class="px-3 py-1.5 rounded-lg text-xs font-bold transition flex items-center {{ $orientation === 'landscape' ? 'bg-indigo-600 text-white shadow-sm shadow-indigo-600/30' : 'text-slate-600 hover:text-slate-900' }}">
                <svg class="w-3.5 h-3.5 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 18h16"/></svg>
                Landscape
            </button>
            <button type="button" wire:click="setOrientation('portrait')" title="Portrait Orientation (54mm x 85.6mm)" class="px-3 py-1.5 rounded-lg text-xs font-bold transition flex items-center {{ $orientation === 'portrait' ? 'bg-indigo-600 text-white shadow-sm shadow-indigo-600/30' : 'text-slate-600 hover:text-slate-900' }}">
                <svg class="w-3.5 h-3.5 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 4v16M18 4v16"/></svg>
                Portrait
            </button>
        </div>

        <button type="button" wire:click="$toggle('showGrid')" class="px-3.5 py-2 rounded-xl text-xs font-bold transition flex items-center border {{ $showGrid ? 'bg-indigo-50 border-indigo-200 text-indigo-700 shadow-sm' : 'bg-slate-50 border-slate-200 text-slate-600 hover:bg-slate-100' }}">
            <svg class="w-3.5 h-3.5 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
            </svg>
            Grid: {{ $showGrid ? 'ON' : 'OFF' }}
        </button>

        <button type="button" wire:click="$toggle('enableSnapping')" class="px-3.5 py-2 rounded-xl text-xs font-bold transition flex items-center border {{ $enableSnapping ? 'bg-indigo-50 border-indigo-200 text-indigo-700 shadow-sm' : 'bg-slate-50 border-slate-200 text-slate-600 hover:bg-slate-100' }}">
            <svg class="w-3.5 h-3.5 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 3v6a6 6 0 0012 0V3M4 3h4m8 0h4M4 8h4m8 0h4"/>
            </svg>
            Snap: {{ $enableSnapping ? 'ON' : 'OFF' }}
        </button>

        <button type="button" wire:click="$toggle('livePreviewMode')" class="px-3.5 py-2 rounded-xl text-xs font-bold transition flex items-center border {{ $livePreviewMode ? 'bg-emerald-50 border-emerald-200 text-emerald-700 shadow-sm' : 'bg-slate-50 border-slate-200 text-slate-600 hover:bg-slate-100' }}">
            <svg class="w-3.5 h-3.5 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
            </svg>
            Preview: {{ $livePreviewMode ? 'Live Data' : 'Tags' }}
        </button>

        <button type="button" wire:click="saveStudioDesign" class="px-5 py-2.5 bg-gradient-to-r from-indigo-600 to-violet-600 hover:from-indigo-700 hover:to-violet-700 text-white rounded-xl text-xs font-black transition shadow-lg shadow-indigo-600/25 flex items-center transform hover:-translate-y-0.5">
            <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
            </svg>
            Save Design
        </button>
    </div>
</div>
