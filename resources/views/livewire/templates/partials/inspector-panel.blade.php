{{-- Canva Studio Tabbed Inspector Panel (5 Cols) --}}
<div class="bg-white border border-slate-200/80 rounded-3xl shadow-xl shadow-slate-200/40 p-5 space-y-4 flex-1 flex flex-col min-h-[620px]">
    
    <!-- Tab Navigation Segmented Bar (4 Tabs) -->
    <div class="flex items-center bg-slate-100/90 p-1.5 rounded-2xl border border-slate-200/80 gap-1">
        <button type="button" 
            @click="activeInspectorTab = 'layers'" 
            :class="activeInspectorTab === 'layers' ? 'bg-white text-indigo-700 shadow-sm font-black border border-slate-200/60' : 'text-slate-600 hover:text-slate-900 font-bold'"
            class="flex-1 py-2 text-center text-xs rounded-xl transition flex items-center justify-center space-x-1"
        >
            <span>📑</span>
            <span class="truncate">Layers</span>
        </button>

        <button type="button" 
            @click="activeInspectorTab = 'controls'" 
            :class="activeInspectorTab === 'controls' ? 'bg-white text-indigo-700 shadow-sm font-black border border-slate-200/60' : 'text-slate-600 hover:text-slate-900 font-bold'"
            class="flex-1 py-2 text-center text-xs rounded-xl transition flex items-center justify-center space-x-1 relative"
        >
            <span>🎛️</span>
            <span class="truncate max-w-[100px]">
                @if($selectedLayerIndex !== null && isset($layers[$selectedLayerIndex]))
                    {{ $layers[$selectedLayerIndex]['label'] ?? 'Controls' }}
                @else
                    Controls
                @endif
            </span>
            @if($selectedLayerIndex !== null || count($selectedLayerIndices) > 0)
                <span class="w-2 h-2 rounded-full bg-indigo-600 animate-pulse shrink-0"></span>
            @endif
        </button>

        <button type="button" 
            @click="activeInspectorTab = 'background'" 
            :class="activeInspectorTab === 'background' ? 'bg-white text-indigo-700 shadow-sm font-black border border-slate-200/60' : 'text-slate-600 hover:text-slate-900 font-bold'"
            class="flex-1 py-2 text-center text-xs rounded-xl transition flex items-center justify-center space-x-1"
        >
            <span>🖼️</span>
            <span class="truncate">Background</span>
        </button>

        <button type="button" 
            @click="activeInspectorTab = 'uploads'" 
            :class="activeInspectorTab === 'uploads' ? 'bg-white text-indigo-700 shadow-sm font-black border border-slate-200/60' : 'text-slate-600 hover:text-slate-900 font-bold'"
            class="flex-1 py-2 text-center text-xs rounded-xl transition flex items-center justify-center space-x-1"
        >
            <span>📁</span>
            <span class="truncate">Uploads</span>
        </button>
    </div>

    <!-- Tab Contents -->
    @include('livewire.templates.partials.tab-layers')
    @include('livewire.templates.partials.tab-controls')
    @include('livewire.templates.partials.tab-background')
    @include('livewire.templates.partials.tab-uploads')
</div>
