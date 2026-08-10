{{-- TAB 4: Uploads Media Library --}}
<div x-show="activeInspectorTab === 'uploads'" x-transition:enter="transition ease-out duration-150 transform opacity-0 scale-95" class="space-y-4 flex-1 flex flex-col min-h-0">
    <div class="flex items-center justify-between border-b border-slate-100 pb-3">
        <div>
            <h3 class="text-sm font-black text-slate-900">Uploads Media Library</h3>
            <p class="text-[11px] text-slate-500 font-medium font-bold text-slate-600">Dump JPG, PNG, WEBP & SVG images to use on your cards</p>
        </div>
        <label class="px-3 py-1.5 bg-amber-600 hover:bg-amber-700 text-white rounded-xl text-xs font-bold transition flex items-center shadow-md shadow-amber-600/20 cursor-pointer space-x-1">
            <span>+ Upload Image</span>
            <input type="file" wire:model.live="uploadedImage" accept="image/png,image/jpeg,image/jpg,image/webp,image/svg+xml" class="hidden">
        </label>
    </div>

    <!-- Drag & Drop Upload Zone -->
    <div class="relative border-2 border-dashed border-slate-200 hover:border-amber-500 rounded-2xl p-4 bg-slate-50/70 hover:bg-amber-50/30 transition text-center group cursor-pointer">
        <input type="file" wire:model.live="uploadedImage" accept="image/png,image/jpeg,image/jpg,image/webp,image/svg+xml" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-10">
        <div class="space-y-1.5">
            <div class="w-10 h-10 mx-auto rounded-2xl bg-amber-100/80 text-amber-700 flex items-center justify-center text-lg group-hover:scale-110 transition shadow-sm">
                ☁️
            </div>
            <p class="text-xs font-bold text-slate-800">Click or drop images here to upload</p>
            <p class="text-[10px] text-slate-400 font-medium">Supports JPG, PNG, WEBP & SVG (Max 10MB)</p>
            <div wire:loading wire:target="uploadedImage" class="text-xs text-amber-600 font-bold">Uploading file to library...</div>
        </div>
    </div>

    <!-- Uploaded Files Grid -->
    <div class="flex-1 flex flex-col space-y-2.5 min-h-0">
        <div class="flex items-center justify-between px-1">
            <span class="text-xs font-black text-slate-700 uppercase tracking-wider">Uploaded Images</span>
            <span class="px-2 py-0.5 bg-amber-100 text-amber-800 rounded-full text-[10px] font-bold">
                {{ count($this->uploadedAssets) }} images
            </span>
        </div>

        @if(count($this->uploadedAssets) > 0)
            <div class="grid grid-cols-3 sm:grid-cols-4 gap-2.5 overflow-y-auto pr-1 flex-1 max-h-[420px]">
                @foreach($this->uploadedAssets as $assetPath)
                    @php
                        $assetUrl = asset('storage/' . $assetPath);
                        $assetName = basename($assetPath);
                    @endphp
                    <div wire:key="tab4-asset-thumb-{{ md5($assetPath) }}" class="relative group aspect-square rounded-2xl bg-white border border-slate-200/80 shadow-sm overflow-hidden hover:border-amber-500 hover:shadow-lg transition">
                        <img src="{{ $assetUrl }}" 
                            wire:click="addAssetToCanvas('{{ $assetPath }}')" 
                            title="Click to insert on ID Card" 
                            class="w-full h-full object-contain p-1.5 cursor-pointer transition transform group-hover:scale-105" 
                        />
                        <div class="absolute inset-x-0 bottom-0 bg-gradient-to-t from-slate-900/80 to-transparent p-1.5 opacity-0 group-hover:opacity-100 transition flex items-center justify-between pointer-events-none">
                            <span class="text-[9px] font-bold text-white truncate max-w-[80%]">{{ $assetName }}</span>
                        </div>
                        <button type="button" 
                            wire:click="deleteUploadedAsset('{{ $assetPath }}')" 
                            wire:confirm="Delete this image from uploads library?" 
                            title="Delete image asset" 
                            class="absolute top-1.5 right-1.5 bg-red-600/90 text-white p-1 rounded-lg text-[9px] opacity-0 group-hover:opacity-100 transition shadow-md hover:bg-red-700 z-10"
                        >
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                        </button>
                    </div>
                @endforeach
            </div>
        @else
            <div class="flex-1 flex flex-col items-center justify-center border-2 border-dashed border-slate-200 rounded-2xl bg-slate-50/50 p-6 text-center">
                <span class="text-3xl mb-2">📁</span>
                <p class="text-xs font-bold text-slate-600">Your uploads folder is empty</p>
                <p class="text-[11px] text-slate-400 mt-1 max-w-[220px]">Upload logos, icons, stamps or photos to easily place them on any ID card template.</p>
            </div>
        @endif
    </div>
</div>
