{{-- Clickable Variable Inserter Toolbar Pills --}}
<div class="w-full mt-5 space-y-3 bg-white/90 backdrop-blur-xl border border-slate-200/80 rounded-2xl p-4 shadow-md">
    <div>
        <span class="text-[11px] font-extrabold text-indigo-700 uppercase tracking-wider block mb-2">🏫 School Variable Tags (Click to Insert):</span>
        <div class="flex flex-wrap gap-1.5">
            @php
                $schoolVars = [
                    '{School Logo}', '{School Name}', '{Registration Code}', '{Principal Name}',
                    '{School Contact}', '{School Email}', '{School Website}', '{School Address}'
                ];
            @endphp
            @foreach($schoolVars as $v)
                <button type="button" wire:click="appendVariableToSelected('{{ $v }}')" class="px-2.5 py-1 {{ $v === '{School Logo}' ? 'bg-indigo-600 text-white hover:bg-indigo-700 shadow-sm shadow-indigo-600/25' : 'bg-indigo-50 hover:bg-indigo-600 text-indigo-700 hover:text-white border border-indigo-200/80' }} rounded-lg text-xs font-bold transition">
                    + {{ $v }}
                </button>
            @endforeach
        </div>
    </div>

    <div>
        <span class="text-[11px] font-extrabold text-amber-700 uppercase tracking-wider block mb-2">🎓 Student Variable Tags (Click to Insert):</span>
        <div class="flex flex-wrap gap-1.5">
            @php
                $studentVars = [
                    '{Student Photo}', '{QR Code}', '{Barcode}', '{First Name}', '{Middle Name}', '{Last Name}',
                    '{Roll No}', '{Ref No}', '{Campaign}', '{Standard}', '{Division}',
                    'Grade ({grade}) Div ({division})', '{Blood Group}', '{Gender}',
                    '{DOB}', '{Contact Number}', '{Address}', '{Pincode}'
                ];
            @endphp
            @foreach($studentVars as $v)
                @php
                    $btnStyle = 'bg-indigo-50 hover:bg-indigo-600 text-indigo-700 hover:text-white border border-indigo-200/80';
                    if ($v === '{Student Photo}') {
                        $btnStyle = 'bg-amber-600 text-white hover:bg-amber-700 shadow-sm shadow-amber-600/25';
                    } elseif ($v === '{QR Code}') {
                        $btnStyle = 'bg-violet-600 text-white hover:bg-violet-700 shadow-sm shadow-violet-600/25';
                    } elseif ($v === '{Barcode}') {
                        $btnStyle = 'bg-cyan-600 text-white hover:bg-cyan-700 shadow-sm shadow-cyan-600/25';
                    }
                @endphp
                <button type="button" wire:click="appendVariableToSelected('{{ $v }}')" class="px-2.5 py-1 {{ $btnStyle }} rounded-lg text-xs font-bold transition">
                    + {{ $v }}
                </button>
            @endforeach
        </div>
    </div>
</div>
