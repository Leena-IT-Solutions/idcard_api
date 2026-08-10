{{-- TAB 1: Templates Layers & Assets --}}
<div x-show="activeInspectorTab === 'layers'" x-transition:enter="transition ease-out duration-150 transform opacity-0 scale-95" class="space-y-4 flex-1 flex flex-col">
    <div class="border-b border-slate-100 pb-3 space-y-2.5">
        <div>
            <h3 class="text-sm font-black text-slate-900">Template Layers & Assets</h3>
            <p class="text-[11px] text-slate-500 font-medium">Add, reorder, or select elements on your ID card</p>
        </div>

        <!-- Dedicated Quick Add Buttons Row -->
        <div class="flex items-center flex-wrap gap-2 pt-1">
            <button type="button" wire:click="addTextLayer" class="px-3 py-1.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-bold transition flex items-center shadow-md shadow-indigo-600/20">
                + Text
            </button>
            <label class="px-3 py-1.5 bg-amber-600 hover:bg-amber-700 text-white rounded-xl text-xs font-bold transition flex items-center shadow-md shadow-amber-600/20 cursor-pointer">
                <span>+ Image</span>
                <input type="file" wire:model.live="uploadedImage" accept="image/png,image/jpeg,image/jpg,image/webp,image/svg+xml" class="hidden">
            </label>
            <button type="button" wire:click="addQrLayer" class="px-3 py-1.5 bg-violet-600 hover:bg-violet-700 text-white rounded-xl text-xs font-bold transition flex items-center shadow-md shadow-violet-600/20">
                + QR Code
            </button>
            <button type="button" wire:click="addBarcodeLayer" class="px-3 py-1.5 bg-cyan-600 hover:bg-cyan-700 text-white rounded-xl text-xs font-bold transition flex items-center shadow-md shadow-cyan-600/20">
                + Barcode
            </button>
            <div class="relative" x-data="{ shapeMenuOpen: false }" @click.outside="shapeMenuOpen = false">
                <button type="button" @click="shapeMenuOpen = !shapeMenuOpen" class="px-3 py-1.5 bg-emerald-600 text-white hover:bg-emerald-700 rounded-xl text-xs font-bold transition flex items-center shadow-md shadow-emerald-600/20">
                    + Shape ▾
                </button>
                <div x-show="shapeMenuOpen" @click="shapeMenuOpen = false" class="absolute left-0 mt-1.5 w-40 bg-white border border-slate-200 rounded-2xl shadow-2xl z-50 overflow-hidden py-1">
                    <button type="button" wire:click="addShapeLayer('rectangle')" class="w-full text-left px-3.5 py-2 text-xs font-bold text-slate-700 hover:bg-slate-50 flex items-center space-x-2.5">
                        <span class="w-3.5 h-3.5 rounded bg-indigo-500 inline-block shadow-sm"></span>
                        <span>Rectangle Box</span>
                    </button>
                    <button type="button" wire:click="addShapeLayer('circle')" class="w-full text-left px-3.5 py-2 text-xs font-bold text-slate-700 hover:bg-slate-50 flex items-center space-x-2.5">
                        <span class="w-3.5 h-3.5 rounded-full bg-indigo-500 inline-block shadow-sm"></span>
                        <span>Circle Shape</span>
                    </button>
                    <button type="button" wire:click="addShapeLayer('line')" class="w-full text-left px-3.5 py-2 text-xs font-bold text-slate-700 hover:bg-slate-50 flex items-center space-x-2.5">
                        <span class="w-4 h-1 bg-indigo-500 rounded-full inline-block"></span>
                        <span>Line Divider</span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Layer List Cards -->
    <div class="space-y-2 flex-1 overflow-y-auto pr-1 min-h-0">
        @foreach($layers as $idx => $layer)
            @php
                $isSelectedInList = in_array($idx, $selectedLayerIndices);
                $layerTypeDisplay = ($layer['type'] ?? 'layer') === 'shape' ? ($layer['shape_type'] ?? 'shape') : ($layer['type'] ?? 'layer');
            @endphp
            <div 
                wire:key="list-layer-{{ $layer['id'] ?? $idx }}"
                @click.prevent="$wire.selectLayer({{ $idx }}, $event.shiftKey)"
                class="p-3 rounded-2xl border transition flex items-center justify-between cursor-pointer {{ $isSelectedInList ? 'bg-indigo-50/90 border-indigo-300 text-indigo-950 shadow-sm' : 'bg-slate-50/70 border-slate-200/80 text-slate-700 hover:bg-slate-100 hover:border-slate-300' }}"
            >
                <div class="flex items-center space-x-3">
                    <span class="text-[10px] font-black uppercase tracking-wider px-2 py-0.5 rounded-md border {{ $isSelectedInList ? 'bg-indigo-600 text-white border-indigo-500' : 'bg-indigo-50 text-indigo-700 border-indigo-200' }}">
                        {{ $layerTypeDisplay }}
                    </span>
                    <div>
                        <span class="text-xs font-bold block text-slate-900">{{ $layer['label'] ?? 'Layer #' . ($idx + 1) }}</span>
                        <span class="text-[10px] text-slate-500 font-mono">X: {{ $layer['x'] ?? 0 }}px, Y: {{ $layer['y'] ?? 0 }}px</span>
                    </div>
                </div>
                <span class="text-xs font-bold {{ $isSelectedInList ? 'text-indigo-600' : 'text-slate-400' }}">&rarr;</span>
            </div>
        @endforeach
    </div>
</div>
