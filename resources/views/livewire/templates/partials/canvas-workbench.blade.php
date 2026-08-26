{{-- Studio Canvas Viewport Box --}}
<div class="bg-[#0f172a] border border-slate-800 rounded-3xl p-5 flex flex-col items-center justify-center min-h-[620px] shadow-2xl relative overflow-hidden">
    <!-- Studio Canvas Header Info -->
    <div class="w-full flex items-center justify-between mb-3 px-1">
        <div class="flex items-center space-x-2">
            <span class="text-[10px] font-black uppercase tracking-widest text-indigo-400 bg-indigo-500/10 border border-indigo-500/20 px-3 py-1 rounded-lg">
                Interactive Studio (CR-80 Scale)
            </span>
            <!-- Undo / Redo Buttons -->
            <button type="button" 
                wire:click="undo" 
                title="Undo (Ctrl+Z)"
                @if(empty($undoStack)) disabled @endif
                class="p-1.5 rounded-lg bg-slate-900 hover:bg-slate-800 border border-slate-800 text-slate-300 hover:text-white disabled:opacity-30 disabled:hover:bg-slate-900 disabled:hover:text-slate-300 transition"
            >
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 15L3 9m0 0l6-6M3 9h12a6 6 0 010 12h-3"></path>
                </svg>
            </button>
            <button type="button" 
                wire:click="redo" 
                title="Redo (Ctrl+Y)"
                @if(empty($redoStack)) disabled @endif
                class="p-1.5 rounded-lg bg-slate-900 hover:bg-slate-800 border border-slate-800 text-slate-300 hover:text-white disabled:opacity-30 disabled:hover:bg-slate-900 disabled:hover:text-slate-300 transition"
            >
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 15l6-6m0 0l-6-6m6 6H9a6 6 0 000 12h3"></path>
                </svg>
            </button>

            <!-- 4 Direct Layer Order Buttons (Bigger & High Contrast) -->
            <div class="flex items-center space-x-1.5 pl-2 border-l border-slate-800">
                <button type="button" 
                    wire:click="moveLayer(null, 'front')" 
                    title="Bring to Front (Top Layer)"
                    @if($selectedLayerIndex === null) disabled @endif
                    class="w-8 h-8 rounded-xl bg-slate-800 hover:bg-indigo-600 border border-slate-700/80 text-white disabled:opacity-25 disabled:hover:bg-slate-800 transition flex items-center justify-center shadow-sm"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 11l7-7 7 7M5 19l7-7 7 7"></path>
                    </svg>
                </button>
                <button type="button" 
                    wire:click="moveLayer(null, 'up')" 
                    title="Bring Forward (1 Step Up)"
                    @if($selectedLayerIndex === null) disabled @endif
                    class="w-8 h-8 rounded-xl bg-slate-800 hover:bg-indigo-600 border border-slate-700/80 text-white disabled:opacity-25 disabled:hover:bg-slate-800 transition flex items-center justify-center shadow-sm"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 15l7-7 7 7"></path>
                    </svg>
                </button>
                <button type="button" 
                    wire:click="moveLayer(null, 'down')" 
                    title="Send Backward (1 Step Down)"
                    @if($selectedLayerIndex === null) disabled @endif
                    class="w-8 h-8 rounded-xl bg-slate-800 hover:bg-indigo-600 border border-slate-700/80 text-white disabled:opacity-25 disabled:hover:bg-slate-800 transition flex items-center justify-center shadow-sm"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"></path>
                    </svg>
                </button>
                <button type="button" 
                    wire:click="moveLayer(null, 'back')" 
                    title="Send to Back (Bottom Layer)"
                    @if($selectedLayerIndex === null) disabled @endif
                    class="w-8 h-8 rounded-xl bg-slate-800 hover:bg-indigo-600 border border-slate-700/80 text-white disabled:opacity-25 disabled:hover:bg-slate-800 transition flex items-center justify-center shadow-sm"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 13l-7 7-7-7M19 5l-7 7-7-7"></path>
                    </svg>
                </button>
            </div>
        </div>
        <span class="text-xs text-slate-400 font-mono bg-slate-900 px-2.5 py-1 rounded-lg border border-slate-800">85.6mm × 54.0mm</span>
    </div>

    <!-- Canvas Container with Drag & Snap Capabilities -->
    <div class="w-full space-y-4">
        <!-- Scrollable Canvas Viewport Container -->
        <div 
            id="canvas-viewport-container"
            @mousedown="onViewportMouseDown($event)"
            @mousemove="onViewportMouseMove($event)"
            @mouseup="onViewportMouseUp($event)"
            :class="(activeTool === 'pan' || isSpacePressed || isPanning) ? (isPanning ? 'cursor-grabbing' : 'cursor-grab') : ''"
            class="w-full flex items-center overflow-auto p-4 min-h-[460px] max-h-[700px] bg-slate-950/40 rounded-2xl border border-slate-800/60 shadow-inner select-none relative"
        >
            <div 
                id="canva-studio-canvas"
                class="relative mx-auto select-none shadow-2xl bg-white overflow-hidden shrink-0 my-auto transform"
                :class="$wire.showGrid ? 'canvas-grid-bg' : ''"
                :style="'width: {{ $canvasW }}px; height: {{ $canvasH }}px; transform: translate(' + panOffsetX + 'px, ' + panOffsetY + 'px) scale(' + ((parseFloat(zoomLevel) || 100) / 100) + '); transform-origin: center center;'"
            >
                <!-- Drag-to-Select Marquee Rectangle Overlay -->
                <div 
                    x-show="isSelectingBox"
                    class="absolute border border-indigo-500 bg-indigo-500/15 rounded shadow-sm z-50 pointer-events-none transition-none"
                    :style="'left: ' + boxRect.left + 'px; top: ' + boxRect.top + 'px; width: ' + boxRect.width + 'px; height: ' + boxRect.height + 'px;'"
                ></div>
                @if($bgUrl)
                    <img src="{{ $bgUrl }}" class="absolute inset-0 w-full h-full object-fill pointer-events-none z-0" alt="Background Graphic" />
                @endif

                @if($showPrintGuides)
                    {{-- Punch / cut-line guide: the canvas already represents the printer's punch size --}}
                    <div class="absolute inset-0 border-2 border-dashed border-red-500/80 pointer-events-none z-30" title="Cut Line (Punch Size)"></div>
                    <span class="absolute top-1 left-1 text-[9px] font-black uppercase tracking-wide text-red-500 bg-white/80 px-1.5 py-0.5 rounded pointer-events-none z-30">Cut Line</span>

                    {{-- Text-safe artwork guide: printer-specified safe area, centered inside the punch line --}}
                    <div
                        class="absolute border-2 border-dashed border-blue-500/80 pointer-events-none z-30"
                        style="top: {{ $safeInsetYPx }}px; left: {{ $safeInsetXPx }}px; right: {{ $safeInsetXPx }}px; bottom: {{ $safeInsetYPx }}px;"
                        title="Text Safe Area ({{ $safeWidthMm }}mm x {{ $safeHeightMm }}mm)"
                    ></div>
                    <span class="absolute text-[9px] font-black uppercase tracking-wide text-blue-500 bg-white/80 px-1.5 py-0.5 rounded pointer-events-none z-30" style="top: {{ $safeInsetYPx + 4 }}px; left: {{ $safeInsetXPx + 4 }}px;">Text Safe</span>
                @endif

                <!-- Dynamic Snapping Alignment Guide Lines (Canva / Figma Smart Guides) -->
                <template x-if="snapLines.x !== null">
                    <div class="absolute top-0 bottom-0 pointer-events-none z-50 border-r-2 border-dashed border-rose-500 shadow-[0_0_8px_rgba(244,63,94,0.8)]" :style="'left: ' + snapLines.x + 'px; width: 0px;'"></div>
                </template>
                <template x-if="snapLines.y !== null">
                    <div class="absolute left-0 right-0 pointer-events-none z-50 border-b-2 border-dashed border-rose-500 shadow-[0_0_8px_rgba(244,63,94,0.8)]" :style="'top: ' + snapLines.y + 'px; height: 0px;'"></div>
                </template>

                <!-- Render Interactive Canvas Layers -->
                @foreach($layers as $idx => $layer)
                    @php
                        $type = $layer['type'] ?? 'text';
                        $x = $layer['x'] ?? 0;
                        $y = $layer['y'] ?? 0;
                        $w = $layer['width'] ?? 150;
                        $h = $layer['height'] ?? 30;
                        $rot = $layer['rotation'] ?? 0;
                        $isSelected = in_array($idx, $selectedLayerIndices);
                    @endphp
                    <div 
                        wire:key="canvas-layer-{{ $layer['id'] ?? $idx }}"
                        @mousedown.stop.prevent="startDrag({{ $idx }}, $event)"
                        @touchstart.stop.prevent="startDrag({{ $idx }}, $event)"
                        @dragstart.prevent
                        @selectstart.prevent
                        data-layer-box
                        data-layer-index="{{ $idx }}"
                        data-layer-type="{{ $type }}"
                        :class="isLayerSelected({{ $idx }}) ? 'ring-2 ring-indigo-500 ring-offset-1 ring-offset-slate-900' : 'hover:ring-1 hover:ring-indigo-400/50'"
                        class="absolute cursor-move select-none transition-shadow group"
                        style="left: {{ $x }}px; top: {{ $y }}px; transform: rotate({{ $rot }}deg); transform-origin: center center; z-index: {{ $idx + 10 }};"
                    >
                        @php
                            $layerOpacity = max(0, min(100, (float)($layer['opacity'] ?? 100))) / 100;
                            $layerFadeMode = $layer['fade_mode'] ?? 'none';
                            
                            $transStyle = "opacity: {$layerOpacity};";
                            
                            $maskGrad = match($layerFadeMode) {
                                'fade_bottom' => 'linear-gradient(to bottom, rgba(0,0,0,1) 0%, rgba(0,0,0,0) 100%)',
                                'fade_top'    => 'linear-gradient(to top, rgba(0,0,0,1) 0%, rgba(0,0,0,0) 100%)',
                                'fade_right'  => 'linear-gradient(to right, rgba(0,0,0,1) 0%, rgba(0,0,0,0) 100%)',
                                'fade_left'   => 'linear-gradient(to left, rgba(0,0,0,1) 0%, rgba(0,0,0,0) 100%)',
                                'radial'      => 'radial-gradient(circle, rgba(0,0,0,1) 20%, rgba(0,0,0,0) 100%)',
                                default       => null,
                            };
                            if ($maskGrad) {
                                $transStyle .= " -webkit-mask-image: {$maskGrad}; mask-image: {$maskGrad};";
                            }
                        @endphp
                        <div data-layer-content style="width: {{ ($type === 'text') ? (!empty($layer['width']) ? ($layer['width'] . 'px') : 'max-content') : ($w . 'px') }}; height: {{ ($type === 'text') ? 'max-content' : ($h . 'px') }}; max-width: 100%; {{ $transStyle }}">
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
                                            '{Principal Name}' => ($activeSchool->principal_name ?? 'Dr. R. K. Sharma'),
                                            '{School Contact}' => ($activeSchool->contact_number ?? '9820198201'),
                                            '{School Email}' => ($activeSchool->email ?? 'info@sarvodya.edu.in'),
                                            '{School Website}' => ($activeSchool->website ?? 'www.sarvodya.edu.in'),
                                            '{School Address}' => ($activeSchool->address ?? 'Station Road, Mumbai'),
                                          ])
                                        : $rawText;

                                    $fontSize = $layer['font_size'] ?? 14;
                                    $fontWeight = $layer['font_weight'] ?? 'normal';
                                    $fontFamily = $layer['font_family'] ?? 'Inter';
                                    $color = $layer['color'] ?? '#ffffff';
                                    $align = $layer['align'] ?? 'left';
                                    $textTransform = $layer['text_transform'] ?? $layer['text_case'] ?? 'none';
                                @endphp
                                <div style="font-size: {{ $fontSize }}pt; font-weight: {{ $fontWeight }}; font-family: {{ $fontFamily }}, sans-serif; color: {{ $color }}; text-align: {{ $align }}; text-transform: {{ $textTransform }}; {{ !empty($layer['width']) ? ('width: ' . $layer['width'] . 'px; max-width: ' . $layer['width'] . 'px; white-space: normal; word-break: break-word;') : 'width: max-content; white-space: nowrap;' }} padding: 2px 4px; border-radius: 4px; box-sizing: border-box; background: {{ $isSelected ? 'rgba(99, 102, 241, 0.15)' : 'transparent' }};">
                                    {{ $displayText }}
                                </div>

                            @elseif($type === 'photo')
                                 @php
                                     $borderRadius = $layer['border_radius'] ?? 12;
                                     $borderColor = $layer['border_color'] ?? '#818cf8';
                                     $borderWidth = $layer['border_width'] ?? 2;
                                     $shape = $layer['shape'] ?? (($borderRadius >= 999) ? 'round' : 'square');
                                     $radiusStyle = ($borderRadius >= 999 || $shape === 'round') ? '50%' : ($borderRadius . 'px');
                                 @endphp
                                 <div style="width: 100%; height: 100%; border-radius: {{ $radiusStyle }}; border: {{ $borderWidth }}px solid {{ $borderColor }}; overflow: hidden; background: linear-gradient(135deg, #1e1b4b 0%, #312e81 100%); display: flex; flex-direction: column; align-items: center; justify-content: center; position: relative; box-sizing: border-box;">
                                     <svg viewBox="0 0 24 24" style="width: 40%; height: 40%; color: #818cf8;" fill="currentColor">
                                         <path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/>
                                     </svg>
                                     <span style="font-size: 8px; font-weight: 800; color: #a5b4fc; text-transform: uppercase; letter-spacing: 0.5px; margin-top: 2px;">STUDENT PHOTO</span>
                                 </div>

                            @elseif($type === 'logo')
                                @if($activeSchool && $activeSchool->logo_path)
                                    <div style="width: 100%; height: 100%; border-radius: 10px; overflow: hidden; display: flex; align-items: center; justify-content: center; box-sizing: border-box; background: {{ $isSelected ? 'rgba(99, 102, 241, 0.15)' : 'transparent' }};">
                                        <img src="{{ asset('storage/' . $activeSchool->logo_path) }}" alt="{{ $activeSchool->name }}" style="max-width: 100%; max-height: 100%; object-fit: contain;" />
                                    </div>
                                @else
                                    <div style="width: 100%; height: 100%; border-radius: 10px; background: linear-gradient(135deg, #312e81 0%, #4338ca 100%); border: 1.5px dashed #818cf8; display: flex; flex-direction: column; align-items: center; justify-content: center; color: #ffffff; padding: 2px; box-sizing: border-box;">
                                        <svg viewBox="0 0 24 24" style="width: 40%; height: 40%; color: #fbbf24;" fill="currentColor">
                                            <path d="M12 3L1 9l11 6 9-4.91V17h2V9L12 3zM3.82 9L12 4.54 20.18 9 12 13.46 3.82 9zM5 14.45v3.55l7 3.82 7-3.82v-3.55l-7 3.81-7-3.81z"/>
                                        </svg>
                                        <span style="font-size: 7px; font-weight: 800; color: #fbbf24; text-transform: uppercase; letter-spacing: 0.5px; margin-top: 2px; text-align: center;">SCHOOL LOGO</span>
                                    </div>
                                @endif

                            @elseif($type === 'qr')
                                 @php
                                     $rawQrValue = !empty($layer['value']) ? $layer['value'] : '{Ref No}';
                                     $qrData = strtr($rawQrValue, [
                                         '{Student Photo}' => 'PHOTO',
                                         '{QR Code}' => 'QR',
                                         '{First Name}' => 'Aaditya',
                                         '{Middle Name}' => 'Sunil',
                                         '{Last Name}' => 'Thakur',
                                         '{Roll No}' => '102',
                                         '{Ref No}' => 'REF-2026-0891',
                                         '{Campaign}' => 'iCard 2026-27',
                                         '{Standard}' => 'Grade V',
                                         '{Division}' => 'Div A',
                                         'Grade ({grade}) Div ({division})' => 'Grade V - A',
                                         '{Blood Group}' => 'B+',
                                         '{Gender}' => 'Male',
                                         '{DOB}' => '2017-05-12',
                                         '{Contact Number}' => '9876543210',
                                         '{Address}' => 'Samarth Nagar, Pune',
                                         '{Pincode}' => '411001',
                                         '{School Name}' => ($activeSchool->name ?? 'Sarvodya Vidyalay'),
                                         '{School Code}' => ($activeSchool->school_code ?? 'SV-2026'),
                                         '{Registration Code}' => ($activeSchool->school_code ?? 'SV-2026'),
                                         '{Principal Name}' => ($activeSchool->principal_name ?? 'Dr. R. K. Sharma'),
                                         '{School Contact}' => ($activeSchool->contact_number ?? '9820198201'),
                                         '{School Email}' => ($activeSchool->email ?? 'info@sarvodya.edu.in'),
                                         '{School Website}' => ($activeSchool->website ?? 'www.sarvodya.edu.in'),
                                         '{School Address}' => ($activeSchool->address ?? 'Station Road, Mumbai'),
                                     ]);
                                     if (empty(trim($qrData))) {
                                         $qrData = 'REF-2026-0891';
                                     }
                                     $qrW = max(20, (int)($layer['width'] ?? 60));
                                     $qrH = max(20, (int)($layer['height'] ?? 60));
                                     $qrSize = min($qrW, $qrH);
                                     try {
                                         $qrSvg = str_replace('<?xml version="1.0" encoding="UTF-8"?>', '', (string)\SimpleSoftwareIO\QrCode\Facades\QrCode::size($qrSize)->margin(1)->generate($qrData));
                                     } catch (\Throwable $e) {
                                         $qrSvg = '<svg viewBox="0 0 24 24" style="width: 100%; height: 100%;" fill="#0f172a"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><path d="M14 14h3v3h-3zM18 18h3v3h-3zM14 18h3v3h-3z"/></svg>';
                                     }
                                 @endphp
                                 <div style="width: 100%; height: 100%; background: white; padding: 3px; border-radius: 6px; display: flex; align-items: center; justify-content: center; box-sizing: border-box; overflow: hidden;">
                                     {!! $qrSvg !!}
                                 </div>

                            @elseif($type === 'barcode')
                                 @php
                                     $rawBarcodeValue = !empty($layer['value']) ? $layer['value'] : '{Ref No}';
                                     $barcodeData = strtr($rawBarcodeValue, [
                                         '{Student Photo}' => 'PHOTO',
                                         '{QR Code}' => 'QR',
                                         '{Barcode}' => 'BARCODE',
                                         '{First Name}' => 'Aaditya',
                                         '{Middle Name}' => 'Sunil',
                                         '{Last Name}' => 'Thakur',
                                         '{Roll No}' => '102',
                                         '{Ref No}' => 'REF-2026-0891',
                                         '{Campaign}' => 'iCard 2026-27',
                                         '{Standard}' => 'Grade V',
                                         '{Division}' => 'Div A',
                                         'Grade ({grade}) Div ({division})' => 'Grade V - A',
                                         '{Blood Group}' => 'B+',
                                         '{Gender}' => 'Male',
                                         '{DOB}' => '2017-05-12',
                                         '{Contact Number}' => '9876543210',
                                         '{Address}' => 'Samarth Nagar, Pune',
                                         '{Pincode}' => '411001',
                                         '{School Name}' => ($activeSchool->name ?? 'Sarvodya Vidyalay'),
                                         '{School Code}' => ($activeSchool->school_code ?? 'SV-2026'),
                                         '{Registration Code}' => ($activeSchool->school_code ?? 'SV-2026'),
                                         '{Principal Name}' => ($activeSchool->principal_name ?? 'Dr. R. K. Sharma'),
                                         '{School Contact}' => ($activeSchool->contact_number ?? '9820198201'),
                                         '{School Email}' => ($activeSchool->email ?? 'info@sarvodya.edu.in'),
                                         '{School Website}' => ($activeSchool->website ?? 'www.sarvodya.edu.in'),
                                         '{School Address}' => ($activeSchool->address ?? 'Station Road, Mumbai'),
                                     ]);
                                     if (empty(trim($barcodeData))) {
                                         $barcodeData = 'REF-2026-0891';
                                     }
                                     $showText = !isset($layer['show_text']) || $layer['show_text'];

                                     try {
                                         $generator = new \Picqer\Barcode\BarcodeGeneratorSVG();
                                         $rawSvg = $generator->getBarcode($barcodeData, $generator::TYPE_CODE_128);
                                         $barcodeSvg = preg_replace('/<\?xml.*?\?>/i', '', $rawSvg);
                                         $barcodeSvg = preg_replace('/<!DOCTYPE.*?>/i', '', $barcodeSvg);
                                         $barcodeSvg = str_replace('<svg ', '<svg preserveAspectRatio="none" style="width: 100%; height: 100%;" ', $barcodeSvg);
                                     } catch (\Throwable $e) {
                                         $barcodeSvg = '<div style="font-size: 10px; font-weight: bold; color: #ef4444;">[Barcode Error]</div>';
                                     }
                                 @endphp
                                 <div style="width: 100%; height: 100%; background: white; padding: 4px 6px; border-radius: 6px; display: flex; flex-direction: column; align-items: center; justify-content: center; box-sizing: border-box; overflow: hidden;">
                                     <div style="flex: 1; width: 100%; display: flex; align-items: center; justify-content: center; overflow: hidden;">
                                         {!! $barcodeSvg !!}
                                     </div>
                                     @if($showText)
                                         <div style="font-size: 9px; font-weight: 800; font-family: monospace; color: #0f172a; letter-spacing: 1px; margin-top: 1px; text-align: center; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 100%;">
                                             {{ $barcodeData }}
                                         </div>
                                     @endif
                                 </div>

                            @elseif($type === 'image')
                                 @php
                                     $imgBw = (float)($layer['border_width'] ?? 0);
                                     $imgBc = $layer['border_color'] ?? '#818cf8';
                                     $imgBr = (float)($layer['border_radius'] ?? 0);
                                     $imgFit = $layer['object_fit'] ?? 'contain';
                                     $imgOpacity = max(0, min(100, (float)($layer['opacity'] ?? 100))) / 100;
                                     $imgRadiusStyle = ($imgBr >= 999) ? '50%' : ($imgBr . 'px');
                                     $imgSrc = !empty($layer['image_path']) ? asset('storage/' . $layer['image_path']) : null;
                                 @endphp
                                 <div style="width: 100%; height: 100%; border-radius: {{ $imgRadiusStyle }}; border: {{ $imgBw }}px solid {{ $imgBc }}; opacity: {{ $imgOpacity }}; overflow: hidden; display: flex; align-items: center; justify-content: center; box-sizing: border-box; background: {{ $isSelected ? 'rgba(99, 102, 241, 0.15)' : 'transparent' }};">
                                     @if($imgSrc)
                                         <img src="{{ $imgSrc }}" alt="{{ $layer['label'] ?? 'Custom Image' }}" style="width: 100%; height: 100%; object-fit: {{ $imgFit }};" />
                                     @else
                                         <div style="font-size: 10px; font-weight: bold; color: #94a3b8;">[No Image]</div>
                                     @endif
                                 </div>

                            @elseif($type === 'shape')
                                @php
                                    $shapeOpacity = max(0, min(100, (float)($layer['opacity'] ?? 100))) / 100;
                                @endphp
                                <div style="width: 100%; height: 100%; opacity: {{ $shapeOpacity }};">
                                    @include('components.shape-svg', ['layer' => $layer])
                                </div>
                            @endif
                        </div>

                        <!-- Canva 8 Interactive Resize Handles (Rendered on Selection) -->
                        <div x-show="isLayerSelected({{ $idx }})">
                            <!-- 4 Corner Handles -->
                            <div @mousedown.stop.prevent="startResize({{ $idx }}, 'nw', $event)" @touchstart.stop.prevent="startResize({{ $idx }}, 'nw', $event)" title="Resize Top-Left" class="absolute w-3.5 h-3.5 bg-white border-2 border-indigo-600 rounded-full shadow-lg hover:scale-125 cursor-nwse-resize z-50 transition-transform" style="top: 0; left: 0; transform: translate(-50%, -50%);"></div>
                            <div @mousedown.stop.prevent="startResize({{ $idx }}, 'ne', $event)" @touchstart.stop.prevent="startResize({{ $idx }}, 'ne', $event)" title="Resize Top-Right" class="absolute w-3.5 h-3.5 bg-white border-2 border-indigo-600 rounded-full shadow-lg hover:scale-125 cursor-nesw-resize z-50 transition-transform" style="top: 0; left: 100%; transform: translate(-50%, -50%);"></div>
                            <div @mousedown.stop.prevent="startResize({{ $idx }}, 'sw', $event)" @touchstart.stop.prevent="startResize({{ $idx }}, 'sw', $event)" title="Resize Bottom-Left" class="absolute w-3.5 h-3.5 bg-white border-2 border-indigo-600 rounded-full shadow-lg hover:scale-125 cursor-nesw-resize z-50 transition-transform" style="top: 100%; left: 0; transform: translate(-50%, -50%);"></div>
                            <div @mousedown.stop.prevent="startResize({{ $idx }}, 'se', $event)" @touchstart.stop.prevent="startResize({{ $idx }}, 'se', $event)" title="Resize Bottom-Right" class="absolute w-3.5 h-3.5 bg-white border-2 border-indigo-600 rounded-full shadow-lg hover:scale-125 cursor-nwse-resize z-50 transition-transform" style="top: 100%; left: 100%; transform: translate(-50%, -50%);"></div>

                            <!-- 4 Side Handles -->
                            <div @mousedown.stop.prevent="startResize({{ $idx }}, 'n', $event)" @touchstart.stop.prevent="startResize({{ $idx }}, 'n', $event)" title="Stretch Top" class="absolute w-3 h-2.5 bg-indigo-500 border border-white rounded-sm shadow-md hover:scale-125 cursor-ns-resize z-50 transition-transform" style="top: 0; left: 50%; transform: translate(-50%, -50%);"></div>
                            <div @mousedown.stop.prevent="startResize({{ $idx }}, 's', $event)" @touchstart.stop.prevent="startResize({{ $idx }}, 's', $event)" title="Stretch Bottom" class="absolute w-3 h-2.5 bg-indigo-500 border border-white rounded-sm shadow-md hover:scale-125 cursor-ns-resize z-50 transition-transform" style="top: 100%; left: 50%; transform: translate(-50%, -50%);"></div>
                            <div @mousedown.stop.prevent="startResize({{ $idx }}, 'w', $event)" @touchstart.stop.prevent="startResize({{ $idx }}, 'w', $event)" title="Stretch Left" class="absolute w-2.5 h-3 bg-indigo-500 border border-white rounded-sm shadow-md hover:scale-125 cursor-ew-resize z-50 transition-transform" style="top: 50%; left: 0; transform: translate(-50%, -50%);"></div>
                            <div @mousedown.stop.prevent="startResize({{ $idx }}, 'e', $event)" @touchstart.stop.prevent="startResize({{ $idx }}, 'e', $event)" title="Stretch Right" class="absolute w-2.5 h-3 bg-indigo-500 border border-white rounded-sm shadow-md hover:scale-125 cursor-ew-resize z-50 transition-transform" style="top: 50%; left: 100%; transform: translate(-50%, -50%);"></div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        <!-- Canvas Bottom Toolbar: Zoom Controls & Presets Floating Bar -->
        <div class="w-full bg-slate-900/90 backdrop-blur-md border border-slate-800 rounded-2xl px-4 py-3 flex flex-wrap items-center justify-between gap-3 shadow-xl">
            <div class="flex items-center space-x-3">
                <!-- Mode Selector (Select vs Pan Tool) -->
                <div class="flex items-center bg-slate-950 border border-slate-800 p-1 rounded-xl">
                    <button 
                        type="button" 
                        @click="toggleTool('select')"
                        :class="activeTool === 'select' ? 'bg-indigo-600 text-white shadow-sm shadow-indigo-600/30' : 'text-slate-400 hover:text-white'"
                        class="px-3 py-1.5 rounded-lg text-xs font-bold transition flex items-center"
                        title="Select Tool (V)"
                    >
                        <svg class="w-3.5 h-3.5 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 15l-2 5L9 9l11 4-5 2zm0 0l5 5"/></svg>
                        Select
                    </button>
                    <button 
                        type="button" 
                        @click="toggleTool('pan')"
                        :class="(activeTool === 'pan' || isSpacePressed) ? 'bg-indigo-600 text-white shadow-sm shadow-indigo-600/30' : 'text-slate-400 hover:text-white'"
                        class="px-3 py-1.5 rounded-lg text-xs font-bold transition flex items-center"
                        title="Pan Tool (Hold Spacebar)"
                    >
                        <svg class="w-3.5 h-3.5 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 11.5V14m0 0v-2.5m0 2.5l3.5 3.5m0 0l3.5-3.5m-3.5 3.5V6a2 2 0 012-2h0a2 2 0 012 2v6.5"/></svg>
                        Pan
                    </button>
                    <button 
                        type="button" 
                        x-show="panOffsetX !== 0 || panOffsetY !== 0"
                        @click="resetPan()"
                        class="px-2.5 py-1.5 rounded-lg text-xs font-bold text-indigo-400 bg-indigo-500/10 border border-indigo-500/20 hover:bg-indigo-500/20 transition flex items-center"
                        title="Reset View Offset"
                    >
                        <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                        Center
                    </button>
                </div>

                <div class="h-4 w-[1px] bg-slate-800"></div>

                <div class="flex items-center space-x-3">
                    <span class="text-xs font-bold text-slate-300 flex items-center">
                        <svg class="w-4 h-4 mr-1.5 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0zM10 7v6m3-3H7"></path>
                        </svg>
                        Zoom:
                    </span>
                    <div class="flex items-center space-x-2">
                        <button type="button" @click="setZoom(Math.max(30, parseInt(zoomLevel) - 10))" title="Zoom Out" class="w-7 h-7 rounded-lg bg-slate-950 hover:bg-slate-800 border border-slate-800 text-slate-300 hover:text-white text-xs font-bold flex items-center justify-center transition">
                            &minus;
                        </button>
                        <input type="range" min="30" max="200" step="5" :value="zoomLevel" @input="setZoom($event.target.value)" class="w-24 sm:w-32 h-1.5 bg-slate-800 rounded-lg appearance-none cursor-pointer accent-indigo-500">
                        <button type="button" @click="setZoom(Math.min(200, parseInt(zoomLevel) + 10))" title="Zoom In" class="w-7 h-7 rounded-lg bg-slate-950 hover:bg-slate-800 border border-slate-800 text-slate-300 hover:text-white text-xs font-bold flex items-center justify-center transition">
                            &#43;
                        </button>
                    </div>
                    <span class="text-xs font-black text-indigo-400 bg-indigo-500/10 border border-indigo-500/20 px-2.5 py-1 rounded-md font-mono" x-text="zoomLevel + '%'">
                        100%
                    </span>
                </div>
            </div>

            <!-- Quick Zoom Preset Buttons & LocalStorage Badge -->
            <div class="flex items-center space-x-1.5">
                <button type="button" @click="setZoom(50)" class="px-2.5 py-1 rounded-lg text-[11px] font-bold transition" :class="zoomLevel == 50 ? 'bg-indigo-600 text-white shadow-sm' : 'bg-slate-950 text-slate-400 hover:text-white border border-slate-800'">50%</button>
                <button type="button" @click="setZoom(75)" class="px-2.5 py-1 rounded-lg text-[11px] font-bold transition" :class="zoomLevel == 75 ? 'bg-indigo-600 text-white shadow-sm' : 'bg-slate-950 text-slate-400 hover:text-white border border-slate-800'">75%</button>
                <button type="button" @click="setZoom(100)" class="px-2.5 py-1 rounded-lg text-[11px] font-bold transition" :class="zoomLevel == 100 ? 'bg-indigo-600 text-white shadow-sm' : 'bg-slate-950 text-slate-400 hover:text-white border border-slate-800'">100%</button>
                <button type="button" @click="setZoom(125)" class="px-2.5 py-1 rounded-lg text-[11px] font-bold transition" :class="zoomLevel == 125 ? 'bg-indigo-600 text-white shadow-sm' : 'bg-slate-950 text-slate-400 hover:text-white border border-slate-800'">125%</button>
                <button type="button" @click="setZoom(150)" class="px-2.5 py-1 rounded-lg text-[11px] font-bold transition" :class="zoomLevel == 150 ? 'bg-indigo-600 text-white shadow-sm' : 'bg-slate-950 text-slate-400 hover:text-white border border-slate-800'">150%</button>
                <span title="Preferences are saved automatically in your browser" class="text-[10px] text-emerald-400 font-extrabold bg-emerald-500/10 border border-emerald-500/20 px-2 py-0.5 rounded-md flex items-center ml-2">
                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 mr-1 animate-pulse"></span>
                    Saved ⚡
                </span>
            </div>
        </div>
    </div>
</div>
