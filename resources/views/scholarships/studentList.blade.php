<!doctype html>
<html lang="lv">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Stipendiju kalkulators </title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=DM+Mono:wght@400;500&family=Syne:wght@600;700;800&family=Inter:wght@400;500&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
        h1, h2, h3, .syne { font-family: 'Syne', sans-serif; }
        .mono { font-family: 'DM Mono', monospace; }

        .drop-zone { transition: border-color .15s, background .15s; }
        .drop-zone.drag-over { border-color: #d97706; background: #fffbeb; }

        .preset-btn.active { background: #1c1917; color: #fef3c7; border-color: #1c1917; }

        @keyframes spin-slow { to { transform: rotate(360deg); } }
        .spin-slow { animation: spin-slow 1.2s linear infinite; }

        .subject-row { transition: background .1s; }

        /* ── Step dependency lock ── */
        .step-locked { opacity: .45; pointer-events: none; }
        .step-locked-overlay { pointer-events: all; }

        /* ── Preset popover ── */
        #presetPopover {
            display: none;
            position: absolute;
            top: calc(100% + 6px);
            left: 0;
            z-index: 50;
            animation: popIn .15s ease;
        }
        #presetPopover.open { display: block; }
        @keyframes popIn {
            from { opacity: 0; transform: translateY(-4px) scale(.97); }
            to   { opacity: 1; transform: translateY(0) scale(1); }
        }

        /* ── Distribution bar preview ── */
        .tier-bar { transition: width .25s ease; }

        /* ── Students loaded badge animate ── */
        @keyframes fadeSlideIn {
            from { opacity: 0; transform: translateX(-6px); }
            to   { opacity: 1; transform: translateX(0); }
        }
        .badge-animate { animation: fadeSlideIn .25s ease both; }
    </style>
</head>
<body class="bg-stone-100 text-stone-900 min-h-screen">

{{-- ─── TOP BAR ─────────────────────────────────────────────────────── --}}
<div class="bg-stone-900 text-stone-100 border-b border-stone-800">
    <div class="max-w-6xl mx-auto px-5 py-3 flex items-center justify-between">
        <div class="flex items-center gap-3">
            <div class="w-7 h-7 bg-amber-400 rounded flex items-center justify-center">
                <svg class="w-4 h-4 text-stone-900" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <span class="syne font-700 text-sm tracking-wide">Stipendiju kalkulators</span>
        </div>
                    {{-- Actions --}}
            <div class="flex items-center gap-2">
                <a href="/results" class="px-3 py-1.5 rounded-lg border border-stone-600 text-stone-300 text-xs hover:bg-stone-800 transition-colors mono">
                    → Rezultāti
                </a>
                        <span class="mono text-xs text-stone-500">{{ date('Y') }}/{{ date('Y') + 1 }}
                         <form action="/logout" method="POST" onclick="event.preventDefault(); this.closest('form').submit();" style="cursor: pointer">
                            @csrf
                                {{ __('Logout') }}
                         </form>
        </span>
            </div>


    </div>
</div>

<div class="max-w-6xl mx-auto px-4 md:px-6 py-8 space-y-8">

    {{-- Page title --}}
    <div class="border-b border-stone-300 pb-5">
        <p class="mono text-xs text-stone-400 uppercase tracking-widest mb-1">Bez atskaitītajiem</p>
        <h1 class="syne text-4xl font-800 text-stone-900 tracking-tight">Skolēnu saraksts</h1>
    </div>

    {{-- ─── FLASH MESSAGES ──────────────────────────────────────────── --}}
    @if($errors->any())
        <div class="rounded-xl border border-red-300 bg-red-50 p-4">
            <p class="syne font-700 text-red-800 text-sm mb-1">Kļūda</p>
            @foreach($errors->all() as $error)
                <p class="text-sm text-red-700">{{ $error }}</p>
            @endforeach
        </div>
    @endif

    @if(session('status'))
        <div class="rounded-xl border border-emerald-300 bg-emerald-50 p-4 flex items-center gap-3">
            <svg class="w-5 h-5 text-emerald-600 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
            </svg>
            <span class="text-emerald-800 text-sm">{{ session('status') }}</span>
        </div>
    @endif


    {{-- ─── STEP 1: STUDENTS ───────────────────────────────────────── --}}
    <section>

        <div class="grid gap-4 lg:grid-cols-2">

            {{-- Upload card --}}
            <form method="POST" action="/students/list/store" enctype="multipart/form-data" id="studentsForm">
                @csrf
                <div class="bg-white rounded-2xl border border-stone-200 p-5 shadow-sm h-full flex flex-col gap-4">

                    <div id="studentsDrop"
                         class="drop-zone flex-1 border-2 border-dashed border-stone-300 rounded-xl p-6 text-center cursor-pointer flex flex-col items-center justify-center gap-3"
                         onclick="document.getElementById('studentsFileInput').click()">
                        <div id="studentsDropIcon" class="w-12 h-12 rounded-full bg-stone-100 flex items-center justify-center">
                            <svg class="w-6 h-6 text-stone-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                            </svg>
                        </div>
                        <div>
                            <p id="studentsDropText" class="font-medium text-stone-700 text-sm">Ievelciet failu šeit</p>
                            <p class="text-xs text-stone-400 mt-0.5">vai klikšķiniet, lai izvēlētos</p>
                        </div>
                        <input id="studentsFileInput" type="file" name="students_file" accept=".xlsx,.xls" class="hidden">
                    </div>
                    <button type="submit" id="studentsSubmitBtn" disabled
                            class="w-full rounded-xl bg-stone-900 text-white text-sm font-medium py-2.5 hover:bg-stone-700 transition-colors disabled:opacity-40 disabled:cursor-not-allowed">
                        Augšupielādēt skolēnu sarakstu
                    </button>
                </div>
            </form>

        </div>
    </section>

</div>

<script>
// ─── Drag & Drop ─────────────────────────────────────────────────────
function setupDrop(zoneId, inputId, textId, chosenId, nameId, clearId, submitId) {
    const zone   = document.getElementById(zoneId);
    const input  = document.getElementById(inputId);
    const text   = document.getElementById(textId);
    const chosen = document.getElementById(chosenId);
    const name   = document.getElementById(nameId);
    const clear  = document.getElementById(clearId);
    const submit = submitId ? document.getElementById(submitId) : null;
    if (!zone || !input) return;

    function setFile(file) {
        if (!file) return;
        const dt = new DataTransfer();
        dt.items.add(file);
        input.files = dt.files;
        if (text) text.textContent = file.name;
        if (name) name.textContent = file.name;
        if (chosen) chosen.classList.remove('hidden');
        if (submit) submit.disabled = false;
    }

    function clearFile() {
        input.value = '';
        if (text) text.textContent = zoneId === 'studentsDrop' ? 'Ievelciet failu šeit' : 'Ievelciet E-klases eksportu šeit';
        if (chosen) chosen.classList.add('hidden');
        if (submit) submit.disabled = true;
    }

    zone.addEventListener('dragover', e => { e.preventDefault(); zone.classList.add('drag-over'); });
    zone.addEventListener('dragleave', () => zone.classList.remove('drag-over'));
    zone.addEventListener('drop', e => {
        e.preventDefault();
        zone.classList.remove('drag-over');
        if (e.dataTransfer.files.length) setFile(e.dataTransfer.files[0]);
    });
    input.addEventListener('change', () => { if (input.files.length) setFile(input.files[0]); });
    if (clear) clear.addEventListener('click', clearFile);
}

setupDrop('studentsDrop', 'studentsFileInput', 'studentsDropText', 'studentsFileChosen', 'studentsFileName', 'studentsClearFile', 'studentsSubmitBtn');
</script>

</body>
</html>