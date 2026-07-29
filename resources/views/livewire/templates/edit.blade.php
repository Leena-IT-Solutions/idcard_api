<?php

use Livewire\Volt\Component;
use Livewire\WithFileUploads;
use App\Models\Template;
use App\Models\SchoolTemplate;
use App\Models\School;

new class extends Component {
    use WithFileUploads;

    public $templateId;
    public string $type = 'master'; // 'master' or 'school'
    public $template = null;

    // Editable template properties
    public string $templateName = '';
    public string $orientation = 'landscape';
    public float $widthMm = 85.60;
    public float $heightMm = 54.00;
    public array $layers = [];
    public $bgUpload = null;

    // Studio Canvas Settings
    public bool $showGrid = true;
    public bool $enableSnapping = true;
    public bool $livePreviewMode = true; // Show mock data vs placeholder text
    public ?int $selectedLayerIndex = null;

    public function mount($templateId, $type = 'master')
    {
        $this->templateId = $templateId;
        $this->type = request()->query('type', $type);

        if ($this->type === 'school') {
            $this->template = SchoolTemplate::find($templateId);
        } else {
            $this->template = Template::find($templateId);
        }

        if (!$this->template) {
            session()->flash('error', 'Template not found.');
            return redirect()->route('templates');
        }

        $this->templateName = $this->template->name;
        $this->orientation = $this->template->orientation ?? 'landscape';
        $this->widthMm = (float)($this->template->width_mm ?? 85.60);
        $this->heightMm = (float)($this->template->height_mm ?? 54.00);

        $config = $this->template->layout_config;
        $this->layers = is_array($config) ? $config : (is_string($config) ? json_decode($config, true) : []);

        if (empty($this->layers)) {
            $this->layers = [
                [
                    'id' => 'student_name',
                    'type' => 'text',
                    'label' => 'Student Name',
                    'text' => '{First Name} {Middle Name} {Last Name}',
                    'x' => 130,
                    'y' => 82,
                    'font_size' => 16,
                    'font_weight' => 'bold',
                    'font_family' => 'Inter',
                    'color' => '#ffffff',
                    'align' => 'left',
                    'rotation' => 0,
                ]
            ];
        }

        foreach ($this->layers as $idx => &$l) {
            if (empty($l['id'])) {
                $l['id'] = 'layer_' . $idx . '_' . rand(1000, 9999);
            }
        }
        unset($l);
    }

    public function selectLayer(int $index)
    {
        if (isset($this->layers[$index])) {
            $this->selectedLayerIndex = $index;
        } else {
            $this->selectedLayerIndex = null;
        }
    }

    public function addTextLayer()
    {
        $newIndex = count($this->layers);
        $this->layers[] = [
            'id' => 'text_' . microtime(true) . '_' . rand(1000, 9999),
            'type' => 'text',
            'label' => 'Text Layer ' . ($newIndex + 1),
            'text' => 'Sample Text Layer',
            'x' => 100,
            'y' => 100,
            'font_size' => 14,
            'font_weight' => 'bold',
            'font_family' => 'Inter',
            'color' => '#ffffff',
            'align' => 'left',
            'rotation' => 0,
        ];
        $this->selectedLayerIndex = $newIndex;
    }

    public function removeLayer(int $index)
    {
        if (isset($this->layers[$index])) {
            array_splice($this->layers, $index, 1);
            if ($this->selectedLayerIndex === $index) {
                $this->selectedLayerIndex = null;
            } elseif ($this->selectedLayerIndex > $index) {
                $this->selectedLayerIndex--;
            }
        }
    }

    public function moveLayer(int $index, string $direction)
    {
        if (!isset($this->layers[$index])) return;

        if ($direction === 'up' && $index > 0) {
            $temp = $this->layers[$index];
            $this->layers[$index] = $this->layers[$index - 1];
            $this->layers[$index - 1] = $temp;
            $this->selectedLayerIndex = $index - 1;
        } elseif ($direction === 'down' && $index < count($this->layers) - 1) {
            $temp = $this->layers[$index];
            $this->layers[$index] = $this->layers[$index + 1];
            $this->layers[$index + 1] = $temp;
            $this->selectedLayerIndex = $index + 1;
        }
    }

    public function alignSelectedLayer(string $alignment)
    {
        if ($this->selectedLayerIndex === null || !isset($this->layers[$this->selectedLayerIndex])) return;

        $canvasWidth = $this->orientation === 'portrait' ? 638 : 1011;
        $canvasHeight = $this->orientation === 'portrait' ? 1011 : 638;

        $layer = &$this->layers[$this->selectedLayerIndex];
        $layerW = $layer['width'] ?? 150;
        $layerH = $layer['height'] ?? 30;

        switch ($alignment) {
            case 'left':
                $layer['x'] = 20;
                break;
            case 'center_h':
                $layer['x'] = (int)(($canvasWidth - $layerW) / 2);
                break;
            case 'right':
                $layer['x'] = (int)($canvasWidth - $layerW - 20);
                break;
            case 'top':
                $layer['y'] = 20;
                break;
            case 'center_v':
                $layer['y'] = (int)(($canvasHeight - $layerH) / 2);
                break;
            case 'bottom':
                $layer['y'] = (int)($canvasHeight - $layerH - 20);
                break;
        }
    }

    public function appendVariableToSelected(string $tag)
    {
        if ($this->selectedLayerIndex !== null && isset($this->layers[$this->selectedLayerIndex])) {
            if (($this->layers[$this->selectedLayerIndex]['type'] ?? '') === 'text') {
                $this->layers[$this->selectedLayerIndex]['text'] .= ' ' . $tag;
            }
        }
    }

    public function updateLayerCoordinates($index, $x = 0, $y = 0)
    {
        $idx = (int)($index ?? -1);
        if ($idx >= 0 && isset($this->layers[$idx])) {
            $this->layers[$idx]['x'] = max(0, (int)round((float)($x ?? 0)));
            $this->layers[$idx]['y'] = max(0, (int)round((float)($y ?? 0)));
        }
    }

    public function saveStudioDesign()
    {
        if (!$this->template) return;

        $bgPath = $this->template->background_image;
        if ($this->bgUpload) {
            $bgPath = $this->bgUpload->store('templates/backgrounds', 'public');
        }

        $this->template->update([
            'name' => $this->templateName,
            'orientation' => $this->orientation,
            'width_mm' => $this->widthMm,
            'height_mm' => $this->heightMm,
            'background_image' => $bgPath,
            'layout_config' => $this->layers,
        ]);

        session()->flash('message', 'Canvas studio design saved successfully!');
    }

    public function with(): array
    {
        $activeSchoolId = session('active_school_id');
        $activeSchool = $activeSchoolId ? School::find($activeSchoolId) : null;

        $mockStudent = (object)[
            'first_name' => 'Aaditya',
            'middle_name' => 'Sonu',
            'last_name' => 'Thakur',
            'dob' => '2017-10-27',
            'contact_number' => '9730777244',
            'blood_group' => 'AB+',
            'gender' => 'Male',
            'address' => 'Sarvodhya Nagar Phase 3 Flat No 704',
            'pincode' => '400001',
            'photo_path' => '',
            'campaignStudents' => collect([
                (object)[
                    'grade' => (object)['name' => 'V'],
                    'division' => (object)['name' => 'B'],
                    'roll_no' => '202',
                    'serial_number' => '202',
                ]
            ])
        ];

        return [
            'activeSchool' => $activeSchool,
            'mockStudent' => $mockStudent,
        ];
    }
}; ?>

<div class="space-y-6">
    @if(session()->has('message'))
        <div class="bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 p-4 rounded-2xl flex items-center justify-between text-sm font-semibold">
            <span>{{ session('message') }}</span>
            <button type="button" onclick="this.parentElement.remove()" class="text-emerald-400 hover:text-white">&times;</button>
        </div>
    @endif

    <!-- Top Action Bar -->
    <div class="bg-slate-900 border border-slate-800 rounded-3xl p-5 flex flex-wrap items-center justify-between gap-4 shadow-xl">
        <div class="flex items-center space-x-4">
            <a href="{{ route('templates') }}" class="p-2.5 rounded-2xl bg-slate-800 hover:bg-slate-700 text-slate-300 hover:text-white transition flex items-center justify-center">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
            </a>
            <div>
                <div class="flex items-center space-x-2">
                    <input type="text" wire:model.live="templateName" class="bg-transparent border-b border-slate-700 text-lg font-black text-white focus:outline-none focus:border-indigo-500">
                    <span class="text-[10px] font-black text-indigo-400 bg-indigo-500/10 border border-indigo-500/20 px-2.5 py-0.5 rounded-full uppercase tracking-wider">
                        {{ strtoupper($type) }} • CR-80
                    </span>
                </div>
                <p class="text-xs text-slate-400 mt-0.5">Canva Studio • Drag layers, snap to guides, edit text & formatting</p>
            </div>
        </div>

        <!-- Studio Quick Tools Bar -->
        <div class="flex items-center space-x-3">
            <button type="button" wire:click="$toggle('showGrid')" class="px-3.5 py-2 rounded-xl text-xs font-bold transition flex items-center {{ $showGrid ? 'bg-indigo-600/20 border border-indigo-500/30 text-indigo-400' : 'bg-slate-800 text-slate-400' }}">
                <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                </svg>
                Grid: {{ $showGrid ? 'ON' : 'OFF' }}
            </button>

            <button type="button" wire:click="$toggle('livePreviewMode')" class="px-3.5 py-2 rounded-xl text-xs font-bold transition flex items-center {{ $livePreviewMode ? 'bg-emerald-600/20 border border-emerald-500/30 text-emerald-400' : 'bg-slate-800 text-slate-400' }}">
                <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                </svg>
                Preview: {{ $livePreviewMode ? 'Live Data' : 'Tags' }}
            </button>

            <button type="button" wire:click="saveStudioDesign" class="px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-bold transition shadow-lg shadow-indigo-600/25 flex items-center">
                <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                </svg>
                Save Design
            </button>
        </div>
    </div>

    <!-- Main Workspace Split -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start w-full">
        <!-- Left: Interactive Canva Canvas Studio (7 Cols) -->
        <div class="lg:col-span-7 bg-slate-900 border border-slate-800 rounded-3xl p-6 flex flex-col items-center justify-center min-h-[620px] shadow-2xl relative overflow-hidden">
            <!-- Studio Canvas Header Info -->
            <div class="w-full flex items-center justify-between mb-4 px-2">
                <span class="text-[10px] font-black uppercase tracking-widest text-indigo-400 bg-indigo-500/10 border border-indigo-500/20 px-3 py-1 rounded-lg">
                    Interactive Canva Studio (CR-80 Scale)
                </span>
                <span class="text-xs text-slate-400 font-mono">85.6mm × 54mm</span>
            </div>

            <!-- Canvas Container with Drag & Snap Capabilities -->
            @php
                $isPortrait = $orientation === 'portrait';
                $canvasW = $isPortrait ? 638 : 1011;
                $canvasH = $isPortrait ? 1011 : 638;
                $bgPath = $template->background_image;
                $bgUrl = $bgPath ? (str_starts_with($bgPath, 'http') ? $bgPath : asset('storage/' . $bgPath)) : null;
            @endphp

            <div class="w-full flex items-center justify-center overflow-x-auto p-2">
                <div 
                    id="canva-studio-canvas"
                    class="relative select-none shadow-2xl rounded-2xl bg-slate-950 overflow-hidden shrink-0 my-auto transform transition-transform duration-200"
                    style="width: {{ $canvasW }}px; height: {{ $canvasH }}px;"
                    x-data="{
                        draggingIndex: null,
                        draggingEl: null,
                        startX: 0,
                        startY: 0,
                        origX: 0,
                        origY: 0,
                        curX: 0,
                        curY: 0,
                        startDrag(idx, event) {
                            this.draggingIndex = idx;
                            this.draggingEl = event.currentTarget;
                            this.startX = event.clientX;
                            this.startY = event.clientY;
                            const layer = ($wire.layers && $wire.layers[idx]) ? $wire.layers[idx] : {};
                            this.origX = parseInt(layer.x) || 0;
                            this.origY = parseInt(layer.y) || 0;
                            this.curX = this.origX;
                            this.curY = this.origY;
                        },
                        onDrag(event) {
                            if (this.draggingIndex === null || !this.draggingEl) return;
                            const dx = event.clientX - this.startX;
                            const dy = event.clientY - this.startY;
                            let newX = Math.max(0, Math.round(this.origX + dx));
                            let newY = Math.max(0, Math.round(this.origY + dy));

                            // Magnetic snap to center (within 10px)
                            const centerH = Math.round(({{ $canvasW }} - 150) / 2);
                            if (Math.abs(newX - centerH) < 10) newX = centerH;

                            this.curX = newX;
                            this.curY = newY;

                            // Direct DOM manipulation for zero latency 60fps dragging
                            this.draggingEl.style.left = newX + 'px';
                            this.draggingEl.style.top = newY + 'px';
                        },
                        stopDrag() {
                            if (this.draggingIndex !== null) {
                                const idx = this.draggingIndex;
                                const finalX = parseInt(this.curX) || 0;
                                const finalY = parseInt(this.curY) || 0;
                                this.draggingIndex = null;
                                this.draggingEl = null;
                                // Single Livewire POST sync on mouse release!
                                $wire.updateLayerCoordinates(idx, finalX, finalY);
                            }
                        }
                    }"
                    @mousemove.window="onDrag($event)"
                    @mouseup.window="stopDrag()"
                >
                    @if($bgUrl)
                        <img src="{{ $bgUrl }}" class="absolute inset-0 w-full h-full object-fill pointer-events-none z-0 rounded-2xl" alt="Background Graphic" />
                    @endif

                    <!-- Card Perimeter Border Overlay (Sits flush on top of background) -->
                    <div class="absolute inset-0 rounded-2xl border-2 pointer-events-none z-40 {{ $selectedLayerIndex !== null ? 'border-indigo-500' : 'border-slate-700/60' }}"></div>

                    <!-- Optional Visual Grid Lines -->
                    @if($showGrid)
                        <div class="absolute inset-0 pointer-events-none opacity-20 z-10 rounded-2xl" style="background-image: radial-gradient(#6366f1 1px, transparent 1px); background-size: 20px 20px;"></div>
                    @endif

                <!-- Center Snap Line (Visual Indicator when Selected) -->
                @if($selectedLayerIndex !== null)
                    <div class="absolute top-0 bottom-0 left-1/2 w-[1px] bg-indigo-500/40 pointer-events-none border-r border-dashed border-indigo-400"></div>
                @endif

                <!-- Render Interactive Canvas Layers -->
                @foreach($layers as $idx => $layer)
                    @php
                        $type = $layer['type'] ?? 'text';
                        $x = $layer['x'] ?? 0;
                        $y = $layer['y'] ?? 0;
                        $w = $layer['width'] ?? 'auto';
                        $h = $layer['height'] ?? 'auto';
                        $rot = $layer['rotation'] ?? 0;
                        $isSelected = ($selectedLayerIndex === $idx);
                    @endphp

                    <div 
                        wire:key="canvas-layer-{{ $layer['id'] ?? $idx }}"
                        wire:click="selectLayer({{ $idx }})"
                        @mousedown="startDrag({{ $idx }}, $event)"
                        class="absolute cursor-move transition-shadow {{ $isSelected ? 'ring-2 ring-indigo-500 ring-offset-2 ring-offset-slate-900 z-30' : 'hover:ring-1 hover:ring-indigo-400/50 z-10' }}"
                        style="left: {{ $x }}px; top: {{ $y }}px; transform: rotate({{ $rot }}deg); transform-origin: top left;"
                    >
                        @if($type === 'text')
                            @php
                                $rawText = $layer['text'] ?? '';
                                $displayText = $livePreviewMode 
                                    ? strtr($rawText, [
                                        '{first_name}' => 'Aaditya', '{middle_name}' => 'Sonu', '{last_name}' => 'Thakur',
                                        '{First Name}' => 'Aaditya', '{Middle Name}' => 'Sonu', '{Last Name}' => 'Thakur',
                                        '{dob}' => '2017-10-27', '{DOB}' => '2017-10-27',
                                        '{blood_group}' => 'AB+', '{Blood Group}' => 'AB+',
                                        '{gender}' => 'Male', '{Gender}' => 'Male',
                                        '{contact_number}' => '9730777244', '{Contact Number}' => '9730777244',
                                        '{address}' => 'Sarvodhya Nagar Flat 704', '{Address}' => 'Sarvodhya Nagar Flat 704',
                                        '{pincode}' => '400001', '{Pincode}' => '400001',
                                        '{grade}' => 'V', '{Grade}' => 'V', '{Standard}' => 'V',
                                        '{division}' => 'B', '{Division}' => 'B', '{Div}' => 'B',
                                        '{roll_no}' => '202', '{Roll No}' => '202', '{serial_number}' => '202', '{Ref No}' => '202',
                                        '{Campaign}' => 'iCard 2026-27',
                                        '{School Name}' => ($activeSchool->name ?? 'Sarvodya Vidyalay'),
                                        '{School Code}' => ($activeSchool->school_code ?? 'SV-2026'),
                                        '{Registration Code}' => ($activeSchool->school_code ?? 'SV-2026'),
                                        '{Principal Name}' => 'Dr. R. K. Sharma',
                                        '{School Contact}' => '9820198201',
                                        '{School Email}' => 'info@sarvodya.edu.in',
                                        '{School Website}' => 'www.sarvodya.edu.in',
                                        '{School Address}' => 'Station Road, Mumbai',
                                      ])
                                    : $rawText;

                                $fontSize = $layer['font_size'] ?? 14;
                                $fontWeight = $layer['font_weight'] ?? 'normal';
                                $fontFamily = $layer['font_family'] ?? 'Inter';
                                $color = $layer['color'] ?? '#ffffff';
                                $align = $layer['align'] ?? 'left';
                            @endphp
                            <div style="font-size: {{ $fontSize }}pt; font-weight: {{ $fontWeight }}; font-family: {{ $fontFamily }}, sans-serif; color: {{ $color }}; text-align: {{ $align }}; white-space: nowrap; padding: 2px 4px; border-radius: 4px; background: {{ $isSelected ? 'rgba(99, 102, 241, 0.15)' : 'transparent' }};">
                                {{ $displayText }}
                            </div>

                        @elseif($type === 'photo')
                            @php
                                $borderRadius = $layer['border_radius'] ?? 12;
                                $borderColor = $layer['border_color'] ?? '#818cf8';
                                $borderWidth = $layer['border_width'] ?? 2;
                            @endphp
                            <div style="width: {{ $w }}px; height: {{ $h }}px; border-radius: {{ $borderRadius }}px; border: {{ $borderWidth }}px solid {{ $borderColor }}; overflow: hidden; background: #1e293b;">
                                <img src="https://ui-avatars.com/api/?name=Aaditya+Thakur&background=6366f1&color=fff&size=200" style="width: 100%; height: 100%; object-fit: cover;" />
                            </div>

                        @elseif($type === 'logo')
                            <div style="width: {{ $w }}px; height: {{ $h }}px; border-radius: 8px; background: rgba(99, 102, 241, 0.2); display: flex; align-items: center; justify-content: center; color: #818cf8; font-weight: bold; font-size: 11px;">
                                LOGO
                            </div>

                        @elseif($type === 'qr')
                            <div style="width: {{ $w }}px; height: {{ $h }}px; background: white; padding: 4px; border-radius: 8px; display: flex; align-items: center; justify-content: center;">
                                <svg viewBox="0 0 24 24" style="width: 100%; height: 100%;" fill="#0f172a">
                                    <rect x="3" y="3" width="7" height="7" rx="1"/>
                                    <rect x="14" y="3" width="7" height="7" rx="1"/>
                                    <rect x="3" y="14" width="7" height="7" rx="1"/>
                                    <path d="M14 14h3v3h-3zM18 18h3v3h-3zM14 18h3v3h-3z"/>
                                </svg>
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>

            <!-- Clickable Variable Inserter Toolbar Pills -->
            <div class="w-full mt-6 space-y-3 bg-slate-950/60 border border-slate-800 rounded-2xl p-4">
                <div>
                    <span class="text-[11px] font-extrabold text-indigo-400 uppercase tracking-wider block mb-2">🏫 School Variable Tags:</span>
                    <div class="flex flex-wrap gap-1.5">
                        @php
                            $schoolVars = [
                                '{School Name}', '{Registration Code}', '{Principal Name}',
                                '{School Contact}', '{School Email}', '{School Website}', '{School Address}'
                            ];
                        @endphp
                        @foreach($schoolVars as $v)
                            <button type="button" wire:click="appendVariableToSelected('{{ $v }}')" class="px-2.5 py-1 bg-indigo-500/10 hover:bg-indigo-600 text-indigo-300 hover:text-white rounded-lg text-xs font-semibold transition border border-indigo-500/20 shadow-sm">
                                + {{ $v }}
                            </button>
                        @endforeach
                    </div>
                </div>

                <div>
                    <span class="text-[11px] font-extrabold text-amber-400 uppercase tracking-wider block mb-2">🎓 Student Variable Tags:</span>
                    <div class="flex flex-wrap gap-1.5">
                        @php
                            $studentVars = [
                                '{First Name}', '{Middle Name}', '{Last Name}',
                                '{Roll No}', '{Ref No}', '{Campaign}', '{Standard}', '{Division}',
                                'Grade ({grade}) Div ({division})', '{Blood Group}', '{Gender}',
                                '{DOB}', '{Contact Number}', '{Address}', '{Pincode}'
                            ];
                        @endphp
                        @foreach($studentVars as $v)
                            <button type="button" wire:click="appendVariableToSelected('{{ $v }}')" class="px-2.5 py-1 bg-amber-500/10 hover:bg-amber-600 text-amber-300 hover:text-white rounded-lg text-xs font-semibold transition border border-amber-500/20 shadow-sm">
                                + {{ $v }}
                            </button>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

        <!-- Right: Canva Element Control Panel (5 Cols) -->
        <div class="lg:col-span-5 space-y-5">
            <!-- Alignment & Layer Tools -->
            @if($selectedLayerIndex !== null && isset($layers[$selectedLayerIndex]))
                @php $selectedLayer = $layers[$selectedLayerIndex]; @endphp
                <div wire:key="controls-panel-{{ $selectedLayerIndex }}-{{ $selectedLayer['id'] ?? 'layer' }}" class="bg-slate-900 border border-indigo-500/40 rounded-3xl p-6 shadow-xl space-y-4">
                    <div class="flex items-center justify-between border-b border-slate-800 pb-3">
                        <div class="flex items-center space-x-2">
                            <span class="w-2.5 h-2.5 rounded-full bg-indigo-400"></span>
                            <h3 class="text-sm font-black text-white">Element Controls ({{ $selectedLayer['label'] ?? 'Layer' }})</h3>
                        </div>
                        <button type="button" wire:click="$set('selectedLayerIndex', null)" class="text-xs font-bold text-slate-400 hover:text-white">Deselect</button>
                    </div>

                    <!-- Alignment Actions Bar -->
                    <div class="space-y-2">
                        <span class="text-[11px] font-bold text-slate-400 block">Quick Align Canvas:</span>
                        <div class="grid grid-cols-6 gap-1.5">
                            <button type="button" wire:click="alignSelectedLayer('left')" title="Align Left" class="p-2 bg-slate-950 hover:bg-slate-800 text-slate-300 rounded-lg text-xs font-bold flex justify-center">Left</button>
                            <button type="button" wire:click="alignSelectedLayer('center_h')" title="Center Horizontally" class="p-2 bg-slate-950 hover:bg-slate-800 text-indigo-400 rounded-lg text-xs font-bold flex justify-center">Center H</button>
                            <button type="button" wire:click="alignSelectedLayer('right')" title="Align Right" class="p-2 bg-slate-950 hover:bg-slate-800 text-slate-300 rounded-lg text-xs font-bold flex justify-center">Right</button>
                            <button type="button" wire:click="alignSelectedLayer('top')" title="Align Top" class="p-2 bg-slate-950 hover:bg-slate-800 text-slate-300 rounded-lg text-xs font-bold flex justify-center">Top</button>
                            <button type="button" wire:click="alignSelectedLayer('center_v')" title="Center Vertically" class="p-2 bg-slate-950 hover:bg-slate-800 text-indigo-400 rounded-lg text-xs font-bold flex justify-center">Center V</button>
                            <button type="button" wire:click="alignSelectedLayer('bottom')" title="Align Bottom" class="p-2 bg-slate-950 hover:bg-slate-800 text-slate-300 rounded-lg text-xs font-bold flex justify-center">Bottom</button>
                        </div>
                    </div>

                    <!-- Layer Name / Label & Millimeter Position Controls -->
                    <div class="space-y-3 pt-1">
                        <div>
                            <label class="block text-[11px] font-bold text-indigo-400 mb-1">Layer Name / Label in List</label>
                            <input type="text" wire:key="input-label-{{ $selectedLayerIndex }}-{{ $selectedLayer['id'] ?? '' }}" wire:model.live="layers.{{ $selectedLayerIndex }}.label" placeholder="e.g. Header Title, Student Roll Tag" class="w-full bg-slate-950 border border-indigo-500/30 rounded-xl px-3.5 py-2 text-xs font-bold text-white focus:outline-none focus:border-indigo-500">
                        </div>

                        <div class="grid grid-cols-2 gap-3" x-data="{
                            updateX(val) {
                                $wire.layers[{{ $selectedLayerIndex }}].x = Math.round((parseFloat(val) || 0) * 11.8128);
                            },
                            updateY(val) {
                                $wire.layers[{{ $selectedLayerIndex }}].y = Math.round((parseFloat(val) || 0) * 11.8128);
                            }
                        }">
                            <div>
                                <label class="block text-[11px] font-bold text-slate-400 mb-1">Position X (mm)</label>
                                <div class="relative">
                                    <input type="number" step="0.1" wire:key="input-x-mm-{{ $selectedLayerIndex }}-{{ $selectedLayer['id'] ?? '' }}" :value="Math.round(($wire.layers[{{ $selectedLayerIndex }}].x || 0) / 11.8128 * 10) / 10" @input="updateX($event.target.value)" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-xs font-bold text-white focus:outline-none focus:border-indigo-500">
                                    <span class="absolute right-3 top-2 text-[10px] text-slate-500 font-mono">{{ $selectedLayer['x'] ?? 0 }}px</span>
                                </div>
                            </div>
                            <div>
                                <label class="block text-[11px] font-bold text-slate-400 mb-1">Position Y (mm)</label>
                                <div class="relative">
                                    <input type="number" step="0.1" wire:key="input-y-mm-{{ $selectedLayerIndex }}-{{ $selectedLayer['id'] ?? '' }}" :value="Math.round(($wire.layers[{{ $selectedLayerIndex }}].y || 0) / 11.8128 * 10) / 10" @input="updateY($event.target.value)" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-xs font-bold text-white focus:outline-none focus:border-indigo-500">
                                    <span class="absolute right-3 top-2 text-[10px] text-slate-500 font-mono">{{ $selectedLayer['y'] ?? 0 }}px</span>
                                </div>
                            </div>
                        </div>

                        @if(in_array($selectedLayer['type'] ?? '', ['photo', 'logo', 'qr']))
                            <div class="grid grid-cols-2 gap-3 pt-1" x-data="{
                                updateW(val) {
                                    $wire.layers[{{ $selectedLayerIndex }}].width = Math.round((parseFloat(val) || 0) * 11.8128);
                                },
                                updateH(val) {
                                    $wire.layers[{{ $selectedLayerIndex }}].height = Math.round((parseFloat(val) || 0) * 11.8128);
                                }
                            }">
                                <div>
                                    <label class="block text-[11px] font-bold text-slate-400 mb-1">Width (mm)</label>
                                    <div class="relative">
                                        <input type="number" step="0.1" wire:key="input-w-mm-{{ $selectedLayerIndex }}-{{ $selectedLayer['id'] ?? '' }}" :value="Math.round(($wire.layers[{{ $selectedLayerIndex }}].width || 0) / 11.8128 * 10) / 10" @input="updateW($event.target.value)" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-xs font-bold text-white focus:outline-none focus:border-indigo-500">
                                        <span class="absolute right-3 top-2 text-[10px] text-slate-500 font-mono">{{ $selectedLayer['width'] ?? 0 }}px</span>
                                    </div>
                                </div>
                                <div>
                                    <label class="block text-[11px] font-bold text-slate-400 mb-1">Height (mm)</label>
                                    <div class="relative">
                                        <input type="number" step="0.1" wire:key="input-h-mm-{{ $selectedLayerIndex }}-{{ $selectedLayer['id'] ?? '' }}" :value="Math.round(($wire.layers[{{ $selectedLayerIndex }}].height || 0) / 11.8128 * 10) / 10" @input="updateH($event.target.value)" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-xs font-bold text-white focus:outline-none focus:border-indigo-500">
                                        <span class="absolute right-3 top-2 text-[10px] text-slate-500 font-mono">{{ $selectedLayer['height'] ?? 0 }}px</span>
                                    </div>
                                </div>
                            </div>
                        @endif
                    </div>

                    <!-- Text Specific Formatting Controls -->
                    @if(($selectedLayer['type'] ?? '') === 'text')
                        <div class="space-y-3 pt-2">
                            <div>
                                <label class="block text-[11px] font-bold text-slate-400 mb-1">Text Content / Template Code</label>
                                <input type="text" wire:key="input-text-{{ $selectedLayerIndex }}-{{ $selectedLayer['id'] ?? '' }}" wire:model.live="layers.{{ $selectedLayerIndex }}.text" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3.5 py-2 text-xs font-bold text-white focus:outline-none focus:border-indigo-500">
                            </div>

                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label class="block text-[11px] font-bold text-slate-400 mb-1">Font Size (pt)</label>
                                    <div class="relative">
                                        <input type="number" min="4" max="120" step="1" wire:key="input-size-pt-{{ $selectedLayerIndex }}-{{ $selectedLayer['id'] ?? '' }}" wire:model.live="layers.{{ $selectedLayerIndex }}.font_size" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-xs font-bold text-white focus:outline-none focus:border-indigo-500">
                                        <span class="absolute right-3 top-2 text-[10px] text-indigo-400 font-mono font-bold">pt</span>
                                    </div>
                                </div>
                                <div>
                                    <label class="block text-[11px] font-bold text-slate-400 mb-1">Text Color (Hex)</label>
                                    <div class="flex items-center space-x-2">
                                        <input type="color" wire:key="input-color-picker-{{ $selectedLayerIndex }}-{{ $selectedLayer['id'] ?? '' }}" wire:model.live="layers.{{ $selectedLayerIndex }}.color" class="w-8 h-8 rounded-lg bg-slate-950 border border-slate-800 cursor-pointer">
                                        <input type="text" wire:key="input-color-text-{{ $selectedLayerIndex }}-{{ $selectedLayer['id'] ?? '' }}" wire:model.live="layers.{{ $selectedLayerIndex }}.color" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-xs font-bold text-white focus:outline-none focus:border-indigo-500 uppercase">
                                    </div>
                                </div>
                            </div>

                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label class="block text-[11px] font-bold text-slate-400 mb-1">Font Weight</label>
                                    <select wire:key="select-weight-{{ $selectedLayerIndex }}-{{ $selectedLayer['id'] ?? '' }}" wire:model.live="layers.{{ $selectedLayerIndex }}.font_weight" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-xs font-bold text-white focus:outline-none focus:border-indigo-500">
                                        <option value="normal">Normal</option>
                                        <option value="semibold">SemiBold</option>
                                        <option value="bold">Bold</option>
                                        <option value="extrabold">ExtraBold</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-[11px] font-bold text-slate-400 mb-1">Rotation Angle (°)</label>
                                    <input type="number" wire:key="input-rot-{{ $selectedLayerIndex }}-{{ $selectedLayer['id'] ?? '' }}" wire:model.live="layers.{{ $selectedLayerIndex }}.rotation" min="0" max="360" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-xs font-bold text-white focus:outline-none focus:border-indigo-500">
                                </div>
                            </div>
                        </div>
                    @endif

                    <!-- Layer Action Buttons -->
                    <div class="flex items-center justify-between pt-3 border-t border-slate-800">
                        <div class="flex space-x-2">
                            <button type="button" wire:click="moveLayer({{ $selectedLayerIndex }}, 'up')" class="px-2.5 py-1 bg-slate-950 hover:bg-slate-800 text-slate-300 rounded-lg text-xs font-bold">Move Up</button>
                            <button type="button" wire:click="moveLayer({{ $selectedLayerIndex }}, 'down')" class="px-2.5 py-1 bg-slate-950 hover:bg-slate-800 text-slate-300 rounded-lg text-xs font-bold">Move Down</button>
                        </div>
                        <button type="button" wire:click="removeLayer({{ $selectedLayerIndex }})" class="px-3 py-1 bg-red-500/10 hover:bg-red-500/20 text-red-400 rounded-lg text-xs font-bold">Delete Layer</button>
                    </div>
                </div>
            @endif

            <!-- Layers Directory & Add Elements -->
            <div class="bg-slate-900 border border-slate-800 rounded-3xl p-6 shadow-xl space-y-4">
                <div class="flex items-center justify-between border-b border-slate-800 pb-3">
                    <h3 class="text-sm font-black text-white">Template Layers List</h3>
                    <button type="button" wire:click="addTextLayer" class="px-3 py-1.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-bold transition flex items-center">
                        + Add Custom Text Layer
                    </button>
                </div>

                <div class="space-y-2 max-h-60 overflow-y-auto pr-1">
                    @foreach($layers as $idx => $layer)
                        <div 
                            wire:key="list-layer-{{ $layer['id'] ?? $idx }}"
                            wire:click="selectLayer({{ $idx }})"
                            class="p-3 rounded-2xl border transition flex items-center justify-between cursor-pointer {{ $selectedLayerIndex === $idx ? 'bg-indigo-500/10 border-indigo-500/40 text-white' : 'bg-slate-950/60 border-slate-800 text-slate-300 hover:border-slate-700' }}"
                        >
                            <div class="flex items-center space-x-3">
                                <span class="text-[10px] font-black uppercase tracking-wider text-indigo-400 bg-indigo-500/10 px-2 py-0.5 rounded-md">
                                    {{ $layer['type'] ?? 'layer' }}
                                </span>
                                <div>
                                    <span class="text-xs font-bold block">{{ $layer['label'] ?? 'Layer #' . ($idx + 1) }}</span>
                                    <span class="text-[10px] text-slate-400">X: {{ $layer['x'] ?? 0 }}px, Y: {{ $layer['y'] ?? 0 }}px</span>
                                </div>
                            </div>
                            <span class="text-xs text-slate-500">&rarr;</span>
                        </div>
                    @endforeach
                </div>
            </div>

            <!-- Background Graphic Upload -->
            <div class="bg-slate-900 border border-slate-800 rounded-3xl p-6 shadow-xl space-y-3">
                <h3 class="text-sm font-black text-white">Background Template Image</h3>
                <div>
                    <input type="file" wire:model="bgUpload" accept="image/*" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3.5 py-2.5 text-xs font-bold text-white focus:outline-none focus:border-indigo-500 transition">
                    <span class="text-[10px] text-slate-500 mt-1 block">Upload custom background graphic (CR-80 85.6mm x 54mm equivalent ratio)</span>
                </div>
            </div>
        </div>
    </div>
</div>
