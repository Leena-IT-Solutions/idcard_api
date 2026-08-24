{{-- TAB 2: Element Controls --}}
<div x-show="activeInspectorTab === 'controls'" x-transition:enter="transition ease-out duration-150 transform opacity-0 scale-95" class="space-y-4 flex-1 flex flex-col min-h-0">
    <!-- Alignment & Layer Tools for Multiple Selections -->
    @if(count($selectedLayerIndices) > 1)
        <div wire:key="controls-panel-multi" class="space-y-4">
            <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                <div class="flex items-center space-x-2">
                    <span class="w-2.5 h-2.5 rounded-full bg-indigo-600 animate-ping"></span>
                    <h3 class="text-sm font-black text-slate-900 flex items-center">
                        Multiple Selection
                        <span class="ml-2 text-[10px] bg-indigo-100 text-indigo-700 px-2 py-0.5 rounded-full font-bold">
                            {{ count($selectedLayerIndices) }} elements
                        </span>
                    </h3>
                </div>
                <button type="button" wire:click="selectLayer(null)" class="text-xs font-bold text-slate-500 hover:text-slate-900">Deselect</button>
            </div>

            <!-- Alignment Section -->
            <div class="space-y-3">
                <!-- Align Target Selector Segment -->
                <div class="flex bg-slate-100 p-1 rounded-xl border border-slate-200">
                    <button type="button" @click="alignMode = 'page'" class="flex-1 py-1.5 text-center text-xs font-bold rounded-lg transition" :class="alignMode === 'page' ? 'bg-indigo-600 text-white shadow-sm' : 'text-slate-600 hover:text-slate-900'">
                        Align to page
                    </button>
                    <button type="button" @click="alignMode = 'selection'" class="flex-1 py-1.5 text-center text-xs font-bold rounded-lg transition" :class="alignMode === 'selection' ? 'bg-indigo-600 text-white shadow-sm' : 'text-slate-600 hover:text-slate-900'">
                        Align selection
                    </button>
                </div>

                <div class="grid grid-cols-2 gap-2.5">
                    <button type="button" @click="alignMode === 'page' ? alignSelectedToPage('top') : alignSelectedToSelection('top')" class="flex items-center justify-center space-x-2 px-3 py-2 bg-slate-50 hover:bg-slate-100 border border-slate-200 rounded-xl text-slate-700 text-xs font-bold transition">
                        <span>Top</span>
                    </button>
                    <button type="button" @click="alignMode === 'page' ? alignSelectedToPage('left') : alignSelectedToSelection('left')" class="flex items-center justify-center space-x-2 px-3 py-2 bg-slate-50 hover:bg-slate-100 border border-slate-200 rounded-xl text-slate-700 text-xs font-bold transition">
                        <span>Left</span>
                    </button>
                    <button type="button" @click="alignMode === 'page' ? alignSelectedToPage('middle') : alignSelectedToSelection('middle')" class="flex items-center justify-center space-x-2 px-3 py-2 bg-slate-50 hover:bg-slate-100 border border-slate-200 rounded-xl text-slate-700 text-xs font-bold transition">
                        <span>Middle</span>
                    </button>
                    <button type="button" @click="alignMode === 'page' ? alignSelectedToPage('center') : alignSelectedToSelection('center')" class="flex items-center justify-center space-x-2 px-3 py-2 bg-slate-50 hover:bg-slate-100 border border-slate-200 rounded-xl text-slate-700 text-xs font-bold transition">
                        <span>Center</span>
                    </button>
                    <button type="button" @click="alignMode === 'page' ? alignSelectedToPage('bottom') : alignSelectedToSelection('bottom')" class="flex items-center justify-center space-x-2 px-3 py-2 bg-slate-50 hover:bg-slate-100 border border-slate-200 rounded-xl text-slate-700 text-xs font-bold transition">
                        <span>Bottom</span>
                    </button>
                    <button type="button" @click="alignMode === 'page' ? alignSelectedToPage('right') : alignSelectedToSelection('right')" class="flex items-center justify-center space-x-2 px-3 py-2 bg-slate-50 hover:bg-slate-100 border border-slate-200 rounded-xl text-slate-700 text-xs font-bold transition">
                        <span>Right</span>
                    </button>
                </div>
            </div>

            <!-- Space Evenly / Distribute Section -->
            <div class="space-y-2 pt-2 border-t border-slate-100">
                <label class="text-[10px] uppercase font-extrabold text-slate-500 tracking-wider block">Space Evenly / Distribute</label>
                <div class="grid grid-cols-2 gap-2.5">
                    <button type="button" @click="distributeSelected('vertically')" class="flex items-center justify-center space-x-2 px-3 py-2 bg-slate-50 hover:bg-indigo-50 hover:border-indigo-200 hover:text-indigo-700 border border-slate-200 rounded-xl text-slate-700 text-xs font-bold transition shadow-sm" title="Distribute layers with equal vertical space">
                        <svg class="w-3.5 h-3.5 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        </svg>
                        <span>Vertically</span>
                    </button>
                    <button type="button" @click="distributeSelected('horizontally')" class="flex items-center justify-center space-x-2 px-3 py-2 bg-slate-50 hover:bg-indigo-50 hover:border-indigo-200 hover:text-indigo-700 border border-slate-200 rounded-xl text-slate-700 text-xs font-bold transition shadow-sm" title="Distribute layers with equal horizontal space">
                        <svg class="w-3.5 h-3.5 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 4v16M12 4v16M18 4v16" />
                        </svg>
                        <span>Horizontally</span>
                    </button>
                </div>
            </div>

            <div class="flex items-center justify-between pt-3 border-t border-slate-100">
                @php
                    $hasGroup = false;
                    foreach ($selectedLayerIndices as $idx) {
                        if (!empty($layers[$idx]['group_id'])) {
                            $hasGroup = true;
                            break;
                        }
                    }
                @endphp
                <span class="text-[10px] text-slate-500 font-medium">Bulk operations affect selection.</span>
                <div class="flex items-center space-x-2">
                    @if($hasGroup)
                        <button type="button" 
                            wire:click="ungroupSelected" 
                            class="px-3.5 py-1.5 bg-amber-50 hover:bg-amber-100 text-amber-800 border border-amber-200 rounded-xl text-xs font-bold transition"
                        >
                            Ungroup
                        </button>
                    @else
                        <button type="button" 
                            wire:click="groupSelected" 
                            class="px-3.5 py-1.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-bold transition shadow-sm"
                        >
                            Group
                        </button>
                    @endif
                    <button type="button" 
                        wire:click="duplicateSelected" 
                        class="px-3.5 py-1.5 bg-indigo-50 hover:bg-indigo-100 text-indigo-700 border border-indigo-200 rounded-xl text-xs font-bold transition"
                    >
                        Duplicate
                    </button>
                    <button type="button" 
                        wire:click="removeLayer(-1)" 
                        class="px-3.5 py-1.5 bg-red-50 hover:bg-red-100 text-red-700 border border-red-200 rounded-xl text-xs font-bold transition"
                    >
                        Delete
                    </button>
                </div>
            </div>
        </div>
    @elseif($selectedLayerIndex !== null && isset($layers[$selectedLayerIndex]))
        @php $selectedLayer = $layers[$selectedLayerIndex]; @endphp
        <div wire:key="controls-panel-{{ $selectedLayerIndex }}-{{ $selectedLayer['id'] ?? 'layer' }}" class="space-y-4">
            <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                <div class="flex items-center space-x-2">
                    <span class="w-3 h-3 rounded-full bg-indigo-600 inline-block shadow-sm"></span>
                    <h3 class="text-sm font-black text-slate-900">Element Controls ({{ $selectedLayer['label'] ?? 'Layer' }})</h3>
                </div>
                <button type="button" wire:click="$set('selectedLayerIndex', null)" class="text-xs font-bold text-slate-500 hover:text-slate-900">Deselect</button>
            </div>

            <!-- Alignment Actions Bar -->
            <div class="space-y-2">
                <span class="text-[11px] font-bold text-slate-500 block">Quick Align Canvas:</span>
                <div class="grid grid-cols-6 gap-1.5">
                    <button type="button" wire:click="alignSelectedLayer('left')" title="Align Left" class="p-2 bg-slate-50 hover:bg-slate-100 text-slate-700 border border-slate-200 rounded-lg text-xs font-bold flex justify-center">Left</button>
                    <button type="button" wire:click="alignSelectedLayer('center_h')" title="Center Horizontally" class="p-2 bg-indigo-50 hover:bg-indigo-100 text-indigo-700 border border-indigo-200 rounded-lg text-xs font-bold flex justify-center">Center H</button>
                    <button type="button" wire:click="alignSelectedLayer('right')" title="Align Right" class="p-2 bg-slate-50 hover:bg-slate-100 text-slate-700 border border-slate-200 rounded-lg text-xs font-bold flex justify-center">Right</button>
                    <button type="button" wire:click="alignSelectedLayer('top')" title="Align Top" class="p-2 bg-slate-50 hover:bg-slate-100 text-slate-700 border border-slate-200 rounded-lg text-xs font-bold flex justify-center">Top</button>
                    <button type="button" wire:click="alignSelectedLayer('center_v')" title="Center Vertically" class="p-2 bg-indigo-50 hover:bg-indigo-100 text-indigo-700 border border-indigo-200 rounded-lg text-xs font-bold flex justify-center">Center V</button>
                    <button type="button" wire:click="alignSelectedLayer('bottom')" title="Align Bottom" class="p-2 bg-slate-50 hover:bg-slate-100 text-slate-700 border border-slate-200 rounded-lg text-xs font-bold flex justify-center">Bottom</button>
                </div>
            </div>

            <!-- Universal Transparency & Fade Effects Controls (ALL Layer Types) -->
            <div class="space-y-3 pt-2 border-t border-slate-100">
                <span class="text-xs font-black text-indigo-700 uppercase tracking-wider block">✨ Transparency & Fade Effects:</span>
                
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <div class="flex justify-between items-center mb-1">
                            <label class="text-[11px] font-bold text-slate-500">Overall Opacity</label>
                            <span class="text-xs font-mono font-bold text-indigo-600">{{ $selectedLayer['opacity'] ?? 100 }}%</span>
                        </div>
                        <input type="range" min="0" max="100" wire:key="universal-opacity-{{ $selectedLayerIndex }}-{{ $selectedLayer['id'] ?? '' }}" wire:model.live="layers.{{ $selectedLayerIndex }}.opacity" class="w-full accent-indigo-600">
                    </div>

                    <div>
                        <label class="block text-[11px] font-bold text-slate-500 mb-1">Fade Gradient Mask</label>
                        <select wire:key="universal-fademode-{{ $selectedLayerIndex }}-{{ $selectedLayer['id'] ?? '' }}" wire:model.live="layers.{{ $selectedLayerIndex }}.fade_mode" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs font-bold text-slate-900 focus:outline-none focus:border-indigo-600">
                            <option value="none">Solid (No Fade)</option>
                            <option value="fade_bottom">Linear Fade ⬇️ (Top to Bottom)</option>
                            <option value="fade_top">Linear Fade ⬆️ (Bottom to Top)</option>
                            <option value="fade_right">Linear Fade ➡️ (Left to Right)</option>
                            <option value="fade_left">Linear Fade ⬅️ (Right to Left)</option>
                            <option value="radial">Radial Center Fade ⭕</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Layer Name / Label & Millimeter Position Controls -->
            <div class="space-y-3 pt-1">
                <div>
                    <label class="block text-[11px] font-bold text-indigo-700 mb-1">Layer Label Name</label>
                    <input type="text" wire:key="input-label-{{ $selectedLayerIndex }}-{{ $selectedLayer['id'] ?? '' }}" wire:model.live="layers.{{ $selectedLayerIndex }}.label" placeholder="e.g. Header Title, Student Roll Tag" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2 text-xs font-bold text-slate-900 focus:outline-none focus:border-indigo-600 transition">
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-[11px] font-bold text-slate-500 mb-1">Position X (mm)</label>
                        <div class="relative">
                            <input type="number" step="0.1" wire:key="input-x-mm-{{ $selectedLayerIndex }}-{{ $selectedLayer['id'] ?? '' }}" :value="Math.round(curX / 11.8128 * 10) / 10" @input="curX = Math.round((parseFloat($event.target.value) || 0) * 11.8128); $wire.layers[{{ $selectedLayerIndex }}].x = curX; $wire.updateLayerCoordinates({{ $selectedLayerIndex }}, curX, curY);" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs font-bold text-slate-900 focus:outline-none focus:border-indigo-600">
                            <span class="absolute right-3 top-2 text-[10px] text-slate-400 font-mono" x-text="curX + 'px'"></span>
                        </div>
                    </div>
                    <div>
                        <label class="block text-[11px] font-bold text-slate-500 mb-1">Position Y (mm)</label>
                        <div class="relative">
                            <input type="number" step="0.1" wire:key="input-y-mm-{{ $selectedLayerIndex }}-{{ $selectedLayer['id'] ?? '' }}" :value="Math.round(curY / 11.8128 * 10) / 10" @input="curY = Math.round((parseFloat($event.target.value) || 0) * 11.8128); $wire.layers[{{ $selectedLayerIndex }}].y = curY; $wire.updateLayerCoordinates({{ $selectedLayerIndex }}, curX, curY);" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs font-bold text-slate-900 focus:outline-none focus:border-indigo-600">
                            <span class="absolute right-3 top-2 text-[10px] text-slate-400 font-mono" x-text="curY + 'px'"></span>
                        </div>
                    </div>
                </div>

                @if(in_array($selectedLayer['type'] ?? '', ['photo', 'logo', 'qr', 'text', 'shape']))
                    <div class="grid grid-cols-2 gap-3 pt-1">
                        <div>
                            <label class="block text-[11px] font-bold text-slate-500 mb-1">
                                {{ ($selectedLayer['type'] ?? '') === 'text' ? 'Max Width (mm)' : 'Width (mm)' }}
                            </label>
                            <div class="relative">
                                <input type="number" step="0.1" wire:key="input-w-mm-{{ $selectedLayerIndex }}-{{ $selectedLayer['id'] ?? '' }}" :value="Math.round(curW / 11.8128 * 10) / 10" @input="curW = Math.round((parseFloat($event.target.value) || 0) * 11.8128); $wire.layers[{{ $selectedLayerIndex }}].width = curW; $wire.updateLayerDimensions({{ $selectedLayerIndex }}, curW, curH, curFontSize, curX, curY);" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs font-bold text-slate-900 focus:outline-none focus:border-indigo-600">
                                <span class="absolute right-3 top-2 text-[10px] text-slate-400 font-mono" x-text="curW + 'px'"></span>
                            </div>
                        </div>
                        @if(($selectedLayer['type'] ?? '') !== 'text')
                            <div>
                                <label class="block text-[11px] font-bold text-slate-500 mb-1">Height (mm)</label>
                                <div class="relative">
                                    <input type="number" step="0.1" wire:key="input-h-mm-{{ $selectedLayerIndex }}-{{ $selectedLayer['id'] ?? '' }}" :value="Math.round(curH / 11.8128 * 10) / 10" @input="curH = Math.round((parseFloat($event.target.value) || 0) * 11.8128); $wire.layers[{{ $selectedLayerIndex }}].height = curH; $wire.updateLayerDimensions({{ $selectedLayerIndex }}, curW, curH, curFontSize, curX, curY);" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs font-bold text-slate-900 focus:outline-none focus:border-indigo-600">
                                    <span class="absolute right-3 top-2 text-[10px] text-slate-400 font-mono" x-text="curH + 'px'"></span>
                                </div>
                            </div>
                        @endif
                    </div>
                @endif
            </div>

            <!-- Text Specific Formatting Controls -->
            @if(($selectedLayer['type'] ?? '') === 'text')
                <div class="space-y-3 pt-2">
                    <div>
                        <label class="block text-[11px] font-bold text-slate-500 mb-1">Text Content / Template Code</label>
                        <input type="text" wire:key="input-text-{{ $selectedLayerIndex }}-{{ $selectedLayer['id'] ?? '' }}" wire:model.live="layers.{{ $selectedLayerIndex }}.text" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2 text-xs font-bold text-slate-900 focus:outline-none focus:border-indigo-600">
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-[11px] font-bold text-slate-500 mb-1">Font Family</label>
                            <select wire:key="select-font-{{ $selectedLayerIndex }}-{{ $selectedLayer['id'] ?? '' }}" wire:model.live="layers.{{ $selectedLayerIndex }}.font_family" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs font-bold text-slate-900 focus:outline-none focus:border-indigo-600">
                                <optgroup label="Sans-Serif (Modern & Clean)">
                                    <option value="Inter">Inter</option>
                                    <option value="Poppins">Poppins</option>
                                    <option value="Roboto">Roboto</option>
                                    <option value="Outfit">Outfit</option>
                                    <option value="Montserrat">Montserrat</option>
                                    <option value="Lato">Lato</option>
                                    <option value="Open Sans">Open Sans</option>
                                    <option value="Raleway">Raleway</option>
                                    <option value="Nunito">Nunito</option>
                                </optgroup>
                            </select>
                        </div>
                        <div>
                            <label class="block text-[11px] font-bold text-slate-500 mb-1">Font Size (pt)</label>
                            <input type="number" min="6" max="140" wire:key="input-fsize-{{ $selectedLayerIndex }}-{{ $selectedLayer['id'] ?? '' }}" wire:model.live="layers.{{ $selectedLayerIndex }}.font_size" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs font-bold text-slate-900 focus:outline-none focus:border-indigo-600">
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-[11px] font-bold text-slate-500 mb-1">Font Weight</label>
                            <select wire:key="select-weight-{{ $selectedLayerIndex }}-{{ $selectedLayer['id'] ?? '' }}" wire:model.live="layers.{{ $selectedLayerIndex }}.font_weight" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs font-bold text-slate-900 focus:outline-none focus:border-indigo-600">
                                <option value="normal">Normal</option>
                                <option value="semibold">Semi Bold</option>
                                <option value="bold">Bold</option>
                                <option value="extrabold">Extra Bold</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-[11px] font-bold text-slate-500 mb-1">Text Alignment</label>
                            <select wire:key="select-align-{{ $selectedLayerIndex }}-{{ $selectedLayer['id'] ?? '' }}" wire:model.live="layers.{{ $selectedLayerIndex }}.align" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs font-bold text-slate-900 focus:outline-none focus:border-indigo-600">
                                <option value="left">Left Align</option>
                                <option value="center">Center Align</option>
                                <option value="right">Right Align</option>
                            </select>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-[11px] font-bold text-slate-500 mb-1">Text Case</label>
                            <select wire:key="select-case-{{ $selectedLayerIndex }}-{{ $selectedLayer['id'] ?? '' }}" wire:model.live="layers.{{ $selectedLayerIndex }}.text_transform" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs font-bold text-slate-900 focus:outline-none focus:border-indigo-600">
                                <option value="none">Normal (As Typed)</option>
                                <option value="uppercase">Uppercase (UPPERCASE)</option>
                                <option value="lowercase">Lowercase (lowercase)</option>
                                <option value="capitalize">Capitalize (Title Case)</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-[11px] font-bold text-slate-500 mb-1">Text Color (Hex)</label>
                            <div class="flex items-center space-x-2">
                                <input type="color" wire:key="input-color-picker-{{ $selectedLayerIndex }}-{{ $selectedLayer['id'] ?? '' }}" wire:model.live="layers.{{ $selectedLayerIndex }}.color" class="w-9 h-9 rounded-xl bg-slate-50 border border-slate-200 cursor-pointer shadow-sm">
                                <input type="text" wire:key="input-color-text-{{ $selectedLayerIndex }}-{{ $selectedLayer['id'] ?? '' }}" wire:model.live="layers.{{ $selectedLayerIndex }}.color" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs font-bold text-slate-900 focus:outline-none focus:border-indigo-600 uppercase">
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            <!-- QR Code Specific Controls -->
            @if(($selectedLayer['type'] ?? '') === 'qr')
                <div class="space-y-3 pt-2 border-t border-slate-100">
                    <span class="text-xs font-extrabold text-violet-700 block">QR Code Data Settings:</span>
                    <div>
                        <label class="block text-[11px] font-bold text-slate-500 mb-1">QR Encoded Value / Variable Tag</label>
                        <input type="text" wire:key="input-qr-value-{{ $selectedLayerIndex }}-{{ $selectedLayer['id'] ?? '' }}" wire:model.live="layers.{{ $selectedLayerIndex }}.value" placeholder="e.g. {Ref No}, {Roll No}" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2 text-xs font-bold text-slate-900 focus:outline-none focus:border-indigo-600">
                        <span class="text-[10px] text-slate-500 mt-1 block">Specify variable tag (click any tag below to insert) or static text encoded in the QR code.</span>
                    </div>
                </div>
            @endif

            <!-- Barcode Specific Controls -->
            @if(($selectedLayer['type'] ?? '') === 'barcode')
                <div class="space-y-3 pt-2 border-t border-slate-100">
                    <span class="text-xs font-extrabold text-cyan-700 block">1D Barcode Data Settings:</span>
                    <div>
                        <label class="block text-[11px] font-bold text-slate-500 mb-1">Barcode Encoded Value / Variable Tag</label>
                        <input type="text" wire:key="input-barcode-value-{{ $selectedLayerIndex }}-{{ $selectedLayer['id'] ?? '' }}" wire:model.live="layers.{{ $selectedLayerIndex }}.value" placeholder="e.g. {Ref No}, {Roll No}" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2 text-xs font-bold text-slate-900 focus:outline-none focus:border-indigo-600">
                        <span class="text-[10px] text-slate-500 mt-1 block">Specify variable tag (click any tag below to insert) or static text encoded in Code 128 barcode.</span>
                    </div>
                    <div class="flex items-center justify-between pt-1">
                        <label class="text-xs font-bold text-slate-700">Display Text Value below Barcode</label>
                        <input type="checkbox" wire:key="check-barcode-showtext-{{ $selectedLayerIndex }}-{{ $selectedLayer['id'] ?? '' }}" wire:model.live="layers.{{ $selectedLayerIndex }}.show_text" class="w-4 h-4 text-indigo-600 rounded border-slate-300 focus:ring-indigo-500">
                    </div>
                </div>
            @endif

            <!-- Image / Custom Upload Specific Controls -->
            @if(($selectedLayer['type'] ?? '') === 'image')
                <div class="space-y-4 pt-2 border-t border-slate-100">
                    <span class="text-xs font-extrabold text-amber-700 block">Custom Uploaded Image Settings:</span>

                    <!-- Replace Image File Button -->
                    <div>
                        <label class="block text-[11px] font-bold text-slate-500 mb-1">Replace Image File (JPG, PNG, WEBP, SVG)</label>
                        <input type="file" wire:key="input-img-file-{{ $selectedLayerIndex }}-{{ $selectedLayer['id'] ?? '' }}" wire:model.live="uploadedImage" accept="image/png,image/jpeg,image/jpg,image/webp,image/svg+xml" class="w-full text-xs text-slate-500 file:mr-3 file:py-1.5 file:px-3 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-amber-50 file:text-amber-800 hover:file:bg-amber-100 cursor-pointer">
                        <div wire:loading wire:target="uploadedImage" class="text-[10px] text-amber-600 font-bold mt-1">Uploading image...</div>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-[11px] font-bold text-slate-500 mb-1">Object Fit Mode</label>
                            <select wire:key="select-fit-{{ $selectedLayerIndex }}-{{ $selectedLayer['id'] ?? '' }}" wire:model.live="layers.{{ $selectedLayerIndex }}.object_fit" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs font-bold text-slate-900 focus:outline-none focus:border-indigo-600">
                                <option value="contain">Contain (Keep Aspect)</option>
                                <option value="cover">Cover (Fill & Crop)</option>
                                <option value="fill">Fill (Stretch)</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-[11px] font-bold text-slate-500 mb-1">Corner Radius (px)</label>
                            <input type="number" min="0" max="999" wire:key="input-img-bradius-{{ $selectedLayerIndex }}-{{ $selectedLayer['id'] ?? '' }}" wire:model.live="layers.{{ $selectedLayerIndex }}.border_radius" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs font-bold text-slate-900 focus:outline-none focus:border-indigo-600">
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-[11px] font-bold text-slate-500 mb-1">Border Width (px)</label>
                            <input type="number" min="0" max="30" wire:key="input-img-bwidth-{{ $selectedLayerIndex }}-{{ $selectedLayer['id'] ?? '' }}" wire:model.live="layers.{{ $selectedLayerIndex }}.border_width" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs font-bold text-slate-900 focus:outline-none focus:border-indigo-600">
                        </div>
                        <div>
                            <label class="block text-[11px] font-bold text-slate-500 mb-1">Border Color</label>
                            <div class="flex items-center space-x-2">
                                <input type="color" wire:key="input-img-bcolor-picker-{{ $selectedLayerIndex }}-{{ $selectedLayer['id'] ?? '' }}" wire:model.live="layers.{{ $selectedLayerIndex }}.border_color" class="w-9 h-9 rounded-xl bg-slate-50 border border-slate-200 cursor-pointer shadow-sm">
                                <input type="text" wire:key="input-img-bcolor-text-{{ $selectedLayerIndex }}-{{ $selectedLayer['id'] ?? '' }}" wire:model.live="layers.{{ $selectedLayerIndex }}.border_color" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs font-bold text-slate-900 focus:outline-none focus:border-indigo-600 uppercase">
                            </div>
                        </div>
                    </div>

                    <div>
                        <div class="flex justify-between items-center mb-1">
                            <label class="text-[11px] font-bold text-slate-500">Opacity (%)</label>
                            <span class="text-xs font-mono font-bold text-indigo-600">{{ $selectedLayer['opacity'] ?? 100 }}%</span>
                        </div>
                        <input type="range" min="0" max="100" wire:key="input-img-opacity-{{ $selectedLayerIndex }}-{{ $selectedLayer['id'] ?? '' }}" wire:model.live="layers.{{ $selectedLayerIndex }}.opacity" class="w-full accent-indigo-600">
                    </div>
                </div>
            @endif

            <!-- Shape Specific Controls -->
            @if(($selectedLayer['type'] ?? '') === 'shape')
                @php
                    $shapeType = $selectedLayer['shape_type'] ?? 'rectangle';
                @endphp
                <div class="space-y-3 pt-2 border-t border-slate-100">
                    <span class="text-xs font-extrabold text-indigo-700 block">Shape Formatting ({{ ucfirst($shapeType) }}):</span>

                    @if($shapeType !== 'line')
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-[11px] font-bold text-slate-500 mb-1">Fill Mode</label>
                                <select wire:key="select-fill-type-{{ $selectedLayerIndex }}-{{ $selectedLayer['id'] ?? '' }}" wire:change="updateShapeProperty('fill_type', $event.target.value)" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs font-bold text-slate-900 focus:outline-none focus:border-indigo-600">
                                    <option value="solid" {{ ($selectedLayer['fill_type'] ?? 'solid') === 'solid' ? 'selected' : '' }}>Solid</option>
                                    <option value="none" {{ ($selectedLayer['fill_type'] ?? 'solid') === 'none' ? 'selected' : '' }}>None (Outline only)</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-[11px] font-bold text-slate-500 mb-1">Opacity (%)</label>
                                <input type="range" min="0" max="100" wire:key="range-fill-opacity-{{ $selectedLayerIndex }}-{{ $selectedLayer['id'] ?? '' }}" value="{{ $selectedLayer['fill_opacity'] ?? 100 }}" @change="$wire.updateShapeProperty('fill_opacity', parseInt($event.target.value))" class="w-full h-1.5 bg-slate-200 rounded-lg appearance-none cursor-pointer accent-indigo-600 mt-2.5">
                            </div>
                        </div>
                        @if(($selectedLayer['fill_type'] ?? 'solid') !== 'none')
                            <div>
                                <label class="block text-[11px] font-bold text-slate-500 mb-1">Fill Color (Hex)</label>
                                <div class="flex items-center space-x-2">
                                    <input type="color" wire:key="input-fill-picker-{{ $selectedLayerIndex }}-{{ $selectedLayer['id'] ?? '' }}" value="{{ $selectedLayer['fill_color'] ?? '#4f46e5' }}" @change="$wire.updateShapeProperty('fill_color', $event.target.value)" class="w-9 h-9 rounded-xl bg-slate-50 border border-slate-200 cursor-pointer shadow-sm">
                                    <input type="text" wire:key="input-fill-text-{{ $selectedLayerIndex }}-{{ $selectedLayer['id'] ?? '' }}" value="{{ $selectedLayer['fill_color'] ?? '#4f46e5' }}" @change="$wire.updateShapeProperty('fill_color', $event.target.value)" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs font-bold text-slate-900 focus:outline-none focus:border-indigo-600 uppercase">
                                </div>
                            </div>
                        @endif
                    @endif

                    <div>
                        <label class="block text-[11px] font-bold text-indigo-700 mb-1">Stroke Color (Hex)</label>
                        <div class="flex items-center space-x-2">
                            <input type="color" wire:key="input-stroke-picker-{{ $selectedLayerIndex }}-{{ $selectedLayer['id'] ?? '' }}" value="{{ $selectedLayer['stroke_color'] ?? '#312e81' }}" @change="$wire.updateShapeProperty('stroke_color', $event.target.value)" class="w-9 h-9 rounded-xl bg-slate-50 border border-slate-200 cursor-pointer shadow-sm">
                            <input type="text" wire:key="input-stroke-text-{{ $selectedLayerIndex }}-{{ $selectedLayer['id'] ?? '' }}" value="{{ $selectedLayer['stroke_color'] ?? '#312e81' }}" @change="$wire.updateShapeProperty('stroke_color', $event.target.value)" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs font-bold text-slate-900 focus:outline-none focus:border-indigo-600 uppercase">
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-[11px] font-bold text-slate-500 mb-1">Stroke Width (px)</label>
                            <input type="number" min="0" max="40" wire:key="input-stroke-width-{{ $selectedLayerIndex }}-{{ $selectedLayer['id'] ?? '' }}" value="{{ $selectedLayer['stroke_width'] ?? 0 }}" @change="$wire.updateShapeProperty('stroke_width', parseInt($event.target.value) || 0)" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs font-bold text-slate-900 focus:outline-none focus:border-indigo-600">
                        </div>
                        <div>
                            <label class="block text-[11px] font-bold text-slate-500 mb-1">Stroke Style</label>
                            <select wire:key="select-stroke-style-{{ $selectedLayerIndex }}-{{ $selectedLayer['id'] ?? '' }}" wire:change="updateShapeProperty('stroke_style', $event.target.value)" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs font-bold text-slate-900 focus:outline-none focus:border-indigo-600">
                                <option value="solid" {{ ($selectedLayer['stroke_style'] ?? 'solid') === 'solid' ? 'selected' : '' }}>Solid</option>
                                <option value="dashed" {{ ($selectedLayer['stroke_style'] ?? 'solid') === 'dashed' ? 'selected' : '' }}>Dashed</option>
                                <option value="dotted" {{ ($selectedLayer['stroke_style'] ?? 'solid') === 'dotted' ? 'selected' : '' }}>Dotted</option>
                            </select>
                        </div>
                    </div>

                    @if($shapeType === 'rectangle')
                        <div class="grid grid-cols-2 gap-3 items-end">
                            <div>
                                <label class="block text-[11px] font-bold text-slate-500 mb-1">Corner Radius (px)</label>
                                <input type="number" min="0" max="500" wire:key="input-corner-radius-{{ $selectedLayerIndex }}-{{ $selectedLayer['id'] ?? '' }}" value="{{ $selectedLayer['corner_radius'] ?? 0 }}" @change="$wire.updateShapeProperty('corner_radius', parseInt($event.target.value) || 0)" {{ !empty($selectedLayer['corner_radius_pill']) ? 'disabled' : '' }} class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs font-bold text-slate-900 focus:outline-none focus:border-indigo-600 disabled:opacity-40">
                            </div>
                            <label class="flex items-center space-x-2 pb-2 cursor-pointer">
                                <input type="checkbox" wire:key="check-pill-{{ $selectedLayerIndex }}-{{ $selectedLayer['id'] ?? '' }}" {{ !empty($selectedLayer['corner_radius_pill']) ? 'checked' : '' }} @change="$wire.updateShapeProperty('corner_radius_pill', $event.target.checked)" class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                                <span class="text-[11px] font-bold text-slate-700">Pill Shape</span>
                            </label>
                        </div>
                    @endif

                    <div>
                        <label class="block text-[11px] font-bold text-slate-500 mb-1">Layer Opacity (%)</label>
                        <input type="range" min="0" max="100" wire:key="range-opacity-{{ $selectedLayerIndex }}-{{ $selectedLayer['id'] ?? '' }}" value="{{ $selectedLayer['opacity'] ?? 100 }}" @change="$wire.updateShapeProperty('opacity', parseInt($event.target.value))" class="w-full h-1.5 bg-slate-200 rounded-lg appearance-none cursor-pointer accent-indigo-600">
                    </div>
                </div>
            @endif

            <!-- Photo Specific Shape & Frame Formatting Controls -->
            @if(($selectedLayer['type'] ?? '') === 'photo')
                <div class="space-y-3 pt-2 border-t border-slate-100">
                    <div>
                        <label class="block text-[11px] font-bold text-indigo-700 mb-1.5">Photo Frame Shape & Aspect Ratio</label>
                        <div class="grid grid-cols-3 gap-2">
                            <!-- 1:1 Square Option -->
                            <button 
                                type="button" 
                                @click="
                                    curH = curW;
                                    $wire.layers[{{ $selectedLayerIndex }}].shape = 'square';
                                    $wire.layers[{{ $selectedLayerIndex }}].height = curW;
                                    $wire.layers[{{ $selectedLayerIndex }}].border_radius = 12;
                                    $wire.updateLayerDimensions({{ $selectedLayerIndex }}, curW, curW, curFontSize, curX, curY);
                                "
                                class="py-2.5 px-2 rounded-xl border text-xs font-extrabold flex flex-col items-center justify-center space-y-1 transition active:scale-95"
                                :class="($wire.layers[{{ $selectedLayerIndex }}].shape === 'square' || ($wire.layers[{{ $selectedLayerIndex }}].border_radius < 999 && Math.abs(curW - curH) < 5)) ? 'bg-indigo-600 text-white border-indigo-500 shadow-md shadow-indigo-600/30' : 'bg-slate-50 text-slate-700 border-slate-200 hover:border-slate-300'"
                            >
                                <span class="w-4 h-4 rounded-md border-2 border-current block"></span>
                                <span>1:1 Square</span>
                            </button>

                            <!-- 3:4 Portrait Option -->
                            <button 
                                type="button" 
                                @click="
                                    curH = Math.round(curW * 4 / 3);
                                    $wire.layers[{{ $selectedLayerIndex }}].shape = 'portrait';
                                    $wire.layers[{{ $selectedLayerIndex }}].height = curH;
                                    $wire.layers[{{ $selectedLayerIndex }}].border_radius = 12;
                                    $wire.updateLayerDimensions({{ $selectedLayerIndex }}, curW, curH, curFontSize, curX, curY);
                                "
                                class="py-2.5 px-2 rounded-xl border text-xs font-extrabold flex flex-col items-center justify-center space-y-1 transition active:scale-95"
                                :class="($wire.layers[{{ $selectedLayerIndex }}].shape === 'portrait' || ($wire.layers[{{ $selectedLayerIndex }}].border_radius < 999 && curH > curW)) ? 'bg-indigo-600 text-white border-indigo-500 shadow-md shadow-indigo-600/30' : 'bg-slate-50 text-slate-700 border-slate-200 hover:border-slate-300'"
                            >
                                <span class="w-3.5 h-4.5 rounded-md border-2 border-current block"></span>
                                <span>3:4 Portrait</span>
                            </button>

                            <!-- Round / Circle Option -->
                            <button 
                                type="button" 
                                @click="
                                    curH = curW;
                                    $wire.layers[{{ $selectedLayerIndex }}].shape = 'round';
                                    $wire.layers[{{ $selectedLayerIndex }}].height = curW;
                                    $wire.layers[{{ $selectedLayerIndex }}].border_radius = 9999;
                                    $wire.updateLayerDimensions({{ $selectedLayerIndex }}, curW, curW, curFontSize, curX, curY);
                                "
                                class="py-2.5 px-2 rounded-xl border text-xs font-extrabold flex flex-col items-center justify-center space-y-1 transition active:scale-95"
                                :class="($wire.layers[{{ $selectedLayerIndex }}].shape === 'round' || $wire.layers[{{ $selectedLayerIndex }}].border_radius >= 999) ? 'bg-indigo-600 text-white border-indigo-500 shadow-md shadow-indigo-600/30' : 'bg-slate-50 text-slate-700 border-slate-200 hover:border-slate-300'"
                            >
                                <span class="w-4 h-4 rounded-full border-2 border-current block"></span>
                                <span>Round ⭕</span>
                            </button>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-[11px] font-bold text-slate-500 mb-1">Border Radius (px)</label>
                            <input type="number" min="0" max="9999" wire:key="input-radius-{{ $selectedLayerIndex }}-{{ $selectedLayer['id'] ?? '' }}" wire:model.live="layers.{{ $selectedLayerIndex }}.border_radius" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs font-bold text-slate-900 focus:outline-none focus:border-indigo-600">
                        </div>
                        <div>
                            <label class="block text-[11px] font-bold text-slate-500 mb-1">Border Width (px)</label>
                            <input type="number" min="0" max="20" wire:key="input-bwidth-{{ $selectedLayerIndex }}-{{ $selectedLayer['id'] ?? '' }}" wire:model.live="layers.{{ $selectedLayerIndex }}.border_width" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs font-bold text-slate-900 focus:outline-none focus:border-indigo-600">
                        </div>
                    </div>

                    <div>
                        <label class="block text-[11px] font-bold text-slate-500 mb-1">Border Color (Hex)</label>
                        <div class="flex items-center space-x-2">
                            <input type="color" wire:key="input-bcolor-picker-{{ $selectedLayerIndex }}-{{ $selectedLayer['id'] ?? '' }}" wire:model.live="layers.{{ $selectedLayerIndex }}.border_color" class="w-9 h-9 rounded-xl bg-slate-50 border border-slate-200 cursor-pointer shadow-sm">
                            <input type="text" wire:key="input-bcolor-text-{{ $selectedLayerIndex }}-{{ $selectedLayer['id'] ?? '' }}" wire:model.live="layers.{{ $selectedLayerIndex }}.border_color" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs font-bold text-slate-900 focus:outline-none focus:border-indigo-600 uppercase">
                        </div>
                    </div>
                </div>
            @endif

            <!-- Layer Action Buttons -->
            <div class="flex items-center justify-between pt-3 border-t border-slate-100">
                <div class="flex space-x-1.5">
                    <button type="button" wire:click="moveLayer({{ $selectedLayerIndex }}, 'up')" class="px-2.5 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 border border-slate-200 rounded-xl text-xs font-bold transition">Up</button>
                    <button type="button" wire:click="moveLayer({{ $selectedLayerIndex }}, 'down')" class="px-2.5 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 border border-slate-200 rounded-xl text-xs font-bold transition">Down</button>
                    <button type="button" wire:click="duplicateSelected" class="px-2.5 py-1.5 bg-indigo-50 hover:bg-indigo-100 text-indigo-700 border border-indigo-200 rounded-xl text-xs font-bold transition">Duplicate</button>
                    @if(!empty($selectedLayer['group_id']))
                        <button type="button" wire:click="ungroupSelected" class="px-2.5 py-1.5 bg-amber-50 hover:bg-amber-100 text-amber-800 border border-amber-200 rounded-xl text-xs font-bold transition">Ungroup</button>
                    @endif
                </div>
                <button type="button" wire:click="removeLayer({{ $selectedLayerIndex }})" class="px-3 py-1.5 bg-red-50 hover:bg-red-100 text-red-700 border border-red-200 rounded-xl text-xs font-bold transition">Delete</button>
            </div>
        </div>
    @else
        <!-- Empty State when no element is selected -->
        <div class="text-center py-10 px-4 bg-slate-50 border border-dashed border-slate-200 rounded-2xl space-y-3">
            <div class="w-12 h-12 rounded-full bg-indigo-50 border border-indigo-100 flex items-center justify-center mx-auto text-indigo-600 text-xl shadow-sm">
                🎛️
            </div>
            <div>
                <h4 class="text-xs font-black text-slate-900">No Element Selected</h4>
                <p class="text-[11px] text-slate-500 mt-1 max-w-xs mx-auto">Click on any element on the canvas or select a layer from the Layers tab to customize formatting & properties.</p>
            </div>
            <button type="button" @click="activeInspectorTab = 'layers'" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-bold transition shadow-md shadow-indigo-600/20">
                Open Layers Directory
            </button>
        </div>
    @endif
</div>
