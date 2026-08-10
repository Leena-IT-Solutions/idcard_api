{{-- TAB 3: Background Graphic --}}
<div x-show="activeInspectorTab === 'background'" x-transition:enter="transition ease-out duration-150 transform opacity-0 scale-95" class="space-y-4 flex-1 flex flex-col min-h-0">
    <div class="flex items-center justify-between border-b border-slate-100 pb-3">
        <div>
            <h3 class="text-sm font-black text-slate-900">Background Graphic</h3>
            <p class="text-[11px] text-slate-500 font-medium">Upload or update the master card background graphic</p>
        </div>
        @if($template && $template->background_image)
            <button type="button" wire:click="deleteBackgroundImage" wire:confirm="Are you sure you want to delete the current background image?" class="px-2.5 py-1 bg-red-50 hover:bg-red-100 text-red-700 border border-red-200 rounded-xl text-xs font-bold transition flex items-center space-x-1">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                <span>Delete</span>
            </button>
        @endif
    </div>

    @if($template && $template->background_image)
        <div class="relative group rounded-2xl overflow-hidden border border-slate-200/80 bg-slate-100/70 p-3 space-y-2">
            @php
                $bgImgUrl = str_starts_with($template->background_image, 'http') 
                    ? $template->background_image 
                    : asset('storage/' . $template->background_image);
            @endphp
            <div class="w-full rounded-xl overflow-hidden bg-slate-900/90 border border-slate-300/80 p-2 flex items-center justify-center shadow-inner">
                <img src="{{ $bgImgUrl }}" class="w-full h-auto max-h-[380px] object-contain rounded-lg shadow-md" alt="Full Master Background Graphic" />
            </div>
            <div class="flex items-center justify-between text-[11px] font-bold text-slate-600 px-1 pt-1">
                <span class="text-emerald-700 font-bold flex items-center">
                    <span class="w-2 h-2 rounded-full bg-emerald-500 inline-block mr-1.5 animate-pulse"></span>
                    Active Background Graphic (Full View)
                </span>
                <a href="{{ $bgImgUrl }}" target="_blank" class="text-indigo-600 hover:text-indigo-800 font-extrabold flex items-center space-x-1">
                    <span>🔍 Open Original</span>
                </a>
            </div>
        </div>
    @endif

    <div>
        <label class="block text-[11px] font-bold text-slate-600 mb-1">
            {{ ($template && $template->background_image) ? 'Update / Replace Background Graphic' : 'Upload New Background Graphic' }}
        </label>
        <input type="file" wire:model="bgUpload" accept="image/*" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2.5 text-xs font-bold text-slate-700 focus:outline-none focus:border-indigo-600 transition">
        @if($bgUpload)
            <span class="text-[11px] font-bold text-indigo-700 mt-1 block">New background file selected. Click 'Save Design' to apply update!</span>
        @else
            <span class="text-[10px] text-slate-500 mt-1 block">Upload custom background graphic (CR-80 85.6mm x 54mm equivalent ratio)</span>
        @endif
    </div>
</div>
