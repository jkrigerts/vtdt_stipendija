<!doctype html>
<html lang="lv">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Stipendiju kalkulators</title>
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

        /* ── Subjects loaded badge animate ── */
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
        <span class="mono text-xs text-stone-500">{{ date('Y') }}/{{ date('Y') + 1 }}
                         <form action="/logout" method="POST" onclick="event.preventDefault(); this.closest('form').submit();" style="cursor: pointer">
                            @csrf
                                {{ __('Logout') }}
                         </form>
</span>
    </div>
</div>

<div class="max-w-6xl mx-auto px-4 md:px-6 py-8 space-y-8">

    {{-- Page title --}}
    <div class="border-b border-stone-300 pb-5">
        <p class="mono text-xs text-stone-400 uppercase tracking-widest mb-1">Iestatījumi</p>
        <h1 class="syne text-4xl font-800 text-stone-900 tracking-tight">Stipendijas</h1>
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

    {{-- ─── PROGRESS INDICATOR ─────────────────────────────────────── --}}
    {{-- Shows the user the two-step dependency clearly --}}
    <div class="flex items-center gap-3">
        <div id="step1Indicator" class="flex items-center gap-2">
            <div id="step1Dot" class="w-6 h-6 rounded-full flex items-center justify-center text-xs font-bold
                {{ $subjects->count() > 0 ? 'bg-emerald-500 text-white' : 'bg-amber-400 text-stone-900' }}">
                {{ $subjects->count() > 0 ? '✓' : '1' }}
            </div>
            <span class="text-sm font-medium {{ $subjects->count() > 0 ? 'text-emerald-700' : 'text-stone-700' }}">Priekšmeti</span>
        </div>
        <div class="flex-1 h-px {{ $subjects->count() > 0 ? 'bg-emerald-300' : 'bg-stone-200' }} max-w-[60px]"></div>
        <div class="flex items-center gap-2">
            <div class="w-6 h-6 rounded-full flex items-center justify-center text-xs font-bold
                {{ $subjects->count() > 0 ? 'bg-stone-900 text-white' : 'bg-stone-200 text-stone-400' }}">2</div>
            <span class="text-sm font-medium {{ $subjects->count() > 0 ? 'text-stone-700' : 'text-stone-400' }}">Aprēķins</span>
        </div>
    </div>

    {{-- ─── STEP 1: SUBJECTS ───────────────────────────────────────── --}}
    <section>
        <div class="flex items-center gap-3 mb-4">
            <span class="mono text-xs text-stone-400">01</span>
            <h2 class="syne text-xl font-700">Mācību priekšmeti</h2>
            @if($subjects->count() > 0)
                <span class="badge-animate mono text-xs bg-emerald-100 text-emerald-700 px-2.5 py-1 rounded-full font-medium flex items-center gap-1">
                    <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                    {{ $subjects->count() }} ielādēti
                </span>
            @else
                <span class="mono text-xs bg-amber-100 text-amber-700 px-2.5 py-1 rounded-full font-medium">Nepieciešams sākt</span>
            @endif
        </div>

        <div class="grid gap-4 lg:grid-cols-2">

            {{-- Upload card --}}
            <form method="POST" action="/subjects/upload" enctype="multipart/form-data" id="subjectsForm">
                @csrf
                <div class="bg-white rounded-2xl border border-stone-200 p-5 shadow-sm h-full flex flex-col gap-4">
                    <p class="text-sm text-stone-500">Augšupielādēt <code class="bg-stone-100 text-stone-700 px-1.5 py-0.5 rounded mono text-xs">subjects.xlsx</code> ar priekšmetu VIMP/PROF iedalījumu.</p>

                    <div id="subjectsDrop"
                         class="drop-zone flex-1 border-2 border-dashed border-stone-300 rounded-xl p-6 text-center cursor-pointer flex flex-col items-center justify-center gap-3"
                         onclick="document.getElementById('subjectsFileInput').click()">
                        <div id="subjectsDropIcon" class="w-12 h-12 rounded-full bg-stone-100 flex items-center justify-center">
                            <svg class="w-6 h-6 text-stone-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                            </svg>
                        </div>
                        <div>
                            <p id="subjectsDropText" class="font-medium text-stone-700 text-sm">Ievelciet failu šeit</p>
                            <p class="text-xs text-stone-400 mt-0.5">vai klikšķiniet, lai izvēlētos</p>
                        </div>
                        <input id="subjectsFileInput" type="file" name="subjects_file" accept=".xlsx,.xls" class="hidden">
                    </div>

                    {{-- File selected state --}}
                    <div id="subjectsFileChosen" class="hidden rounded-xl bg-stone-50 border border-stone-200 px-4 py-3 flex items-center gap-3">
                        <svg class="w-5 h-5 text-emerald-500 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                        <span id="subjectsFileName" class="text-sm text-stone-700 flex-1 truncate"></span>
                        <button type="button" id="subjectsClearFile" class="text-stone-400 hover:text-stone-600 text-xs">✕</button>
                    </div>

                    <button type="submit" id="subjectsSubmitBtn" disabled
                            class="w-full rounded-xl bg-stone-900 text-white text-sm font-medium py-2.5 hover:bg-stone-700 transition-colors disabled:opacity-40 disabled:cursor-not-allowed">
                        Augšupielādēt priekšmetus
                    </button>
                </div>
            </form>

            {{-- Subjects panel --}}
            <div class="bg-white rounded-2xl border border-stone-200 p-5 shadow-sm flex flex-col gap-3">
                <div class="flex items-center justify-between">
                    <h3 class="syne font-700 text-base">Ielādētie priekšmeti</h3>
                    @if($subjects->count() > 0)
                        <span class="mono text-xs bg-amber-100 text-amber-700 px-2.5 py-1 rounded-full font-medium">{{ $subjects->count() }} priekšmeti</span>
                    @endif
                </div>

                @if($subjects->count() > 0)
                    <div class="relative">
                        <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-stone-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                        <input id="subjectSearch" type="text" placeholder="Meklēt priekšmetu…"
                               class="w-full pl-9 pr-3 py-2 text-sm rounded-lg border border-stone-200 focus:outline-none focus:ring-2 focus:ring-amber-300">
                    </div>
                @endif

                <div class="flex-1 max-h-60 overflow-auto space-y-0.5" id="subjectsList">
                    @forelse($subjects as $subject)
                        <div class="subject-row flex items-center justify-between px-2.5 py-1.5 rounded-lg hover:bg-stone-50"
                             data-name="{{ strtolower($subject->name) }}">
                            <span class="text-sm text-stone-700">{{ $subject->name }}</span>
                            <span class="mono text-xs font-medium px-2 py-0.5 rounded
                                {{ $subject->category === 'PROF' ? 'bg-amber-100 text-amber-700' : 'bg-sky-100 text-sky-700' }}">
                                {{ $subject->category }}
                            </span>
                        </div>
                    @empty
                        <div class="flex flex-col items-center justify-center py-10 gap-2">
                            <svg class="w-8 h-8 text-stone-200" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 13h6m-3-3v6m-9 1V7a2 2 0 012-2h6l2 2h6a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2z"/>
                            </svg>
                            <p class="text-sm text-stone-400">Nav ielādētu priekšmetu.</p>
                            <p class="text-xs text-stone-300">Augšupielādē subjects.xlsx, lai sāktu.</p>
                        </div>
                    @endforelse
                </div>
                <p id="searchEmpty" class="hidden text-sm text-stone-400 py-3 text-center">Nav atrasts neviens priekšmets.</p>
            </div>
        </div>
    </section>

    {{-- ─── STEP 2: CALCULATION SETTINGS ─────────────────────────── --}}
    {{-- Locked overlay when no subjects loaded --}}
    <div class="relative">
        @if($subjects->count() === 0)
            <div class="step-locked-overlay absolute inset-0 z-10 rounded-2xl flex flex-col items-center justify-center gap-3 bg-stone-100/70 backdrop-blur-[1px]">
                <div class="bg-white border border-stone-200 rounded-2xl px-6 py-5 text-center shadow-lg max-w-xs">
                    <div class="w-10 h-10 rounded-full bg-amber-100 flex items-center justify-center mx-auto mb-3">
                        <svg class="w-5 h-5 text-amber-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                        </svg>
                    </div>
                    <p class="syne font-700 text-stone-800 text-sm">Vispirms ielādē priekšmetus</p>
                    <p class="text-xs text-stone-500 mt-1">Augšupielādē subjects.xlsx 1. solī, lai atbloķētu aprēķinu.</p>
                </div>
            </div>
        @endif

        <form method="POST" action="/calculate" enctype="multipart/form-data" id="calculateForm"
              class="{{ $subjects->count() === 0 ? 'step-locked' : '' }}">
            @csrf

            <div class="flex items-center gap-3 mb-4">
                <span class="mono text-xs text-stone-400">02</span>
                <h2 class="syne text-xl font-700">Aprēķina iestatījumi</h2>
            </div>

            <div class="space-y-4">

                {{-- Period card --}}
                <div class="bg-white rounded-2xl border border-stone-200 p-5 shadow-sm">
                    <h3 class="syne font-700 text-base mb-4">Periods</h3>

                    {{-- Preset buttons + popover --}}
                    <div class="flex flex-wrap items-center gap-2 mb-4">
                        <button type="button" id="presetBtn1" class="preset-btn mono text-xs px-3 py-1.5 rounded-lg border border-stone-300 hover:border-stone-500 transition-colors" data-preset="semester_1">
                            1. semestris
                        </button>
                        <button type="button" id="presetBtn2" class="preset-btn mono text-xs px-3 py-1.5 rounded-lg border border-stone-300 hover:border-stone-500 transition-colors" data-preset="semester_2">
                            2. semestris
                        </button>

                        {{-- Popover trigger --}}
                        <div class="relative" id="presetPopoverAnchor">
                            <button type="button" id="editPresetsBtn"
                                    class="mono text-xs px-3 py-1.5 rounded-lg border border-dashed border-stone-300 text-stone-400 hover:border-stone-500 hover:text-stone-600 transition-colors">
                                ✎ Rediģēt datumus
                            </button>
                            {{-- Popover panel --}}
                            <div id="presetPopover" class="bg-white border border-stone-200 rounded-2xl shadow-xl p-5 w-72">
                                <p class="text-xs text-stone-500 font-medium mb-3">Semestru datumi <span class="text-stone-300">(saglabājas pārlūkā)</span></p>
                                <div class="grid gap-3">
                                    <div class="grid grid-cols-2 gap-2">
                                        <label class="block">
                                            <span class="text-xs text-stone-400 block mb-1">1. sem. sākums</span>
                                            <input type="date" id="ps1s" class="w-full rounded-lg border border-stone-200 px-2 py-1.5 text-xs focus:outline-none focus:ring-2 focus:ring-amber-300">
                                        </label>
                                        <label class="block">
                                            <span class="text-xs text-stone-400 block mb-1">1. sem. beigas</span>
                                            <input type="date" id="ps1e" class="w-full rounded-lg border border-stone-200 px-2 py-1.5 text-xs focus:outline-none focus:ring-2 focus:ring-amber-300">
                                        </label>
                                        <label class="block">
                                            <span class="text-xs text-stone-400 block mb-1">2. sem. sākums</span>
                                            <input type="date" id="ps2s" class="w-full rounded-lg border border-stone-200 px-2 py-1.5 text-xs focus:outline-none focus:ring-2 focus:ring-amber-300">
                                        </label>
                                        <label class="block">
                                            <span class="text-xs text-stone-400 block mb-1">2. sem. beigas</span>
                                            <input type="date" id="ps2e" class="w-full rounded-lg border border-stone-200 px-2 py-1.5 text-xs focus:outline-none focus:ring-2 focus:ring-amber-300">
                                        </label>
                                    </div>
                                    <button type="button" id="savePresetsBtn"
                                            class="w-full py-2 rounded-xl bg-stone-800 text-white text-xs hover:bg-stone-700 transition-colors">
                                        Saglabāt datumus
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="grid gap-4 sm:grid-cols-3">
                        <div>
                            <label class="text-xs text-stone-500 block mb-1.5">Perioda sākums</label>
                            <input id="period_start" type="date" name="period_start"
                                   value="{{ old('period_start', optional($session?->period_start)->format('Y-m-d')) }}"
                                   class="w-full rounded-xl border border-stone-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-amber-300" required>
                        </div>
                        <div>
                            <label class="text-xs text-stone-500 block mb-1.5">Perioda beigas</label>
                            <input id="period_end" type="date" name="period_end"
                                   value="{{ old('period_end', optional($session?->period_end)->format('Y-m-d')) }}"
                                   class="w-full rounded-xl border border-stone-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-amber-300" required>
                        </div>
                        <div>
                            <label class="text-xs text-stone-500 block mb-1.5">Mēneša budžets (EUR)</label>
                            <input type="number" step="0.01" min="0" name="monthly_budget"
                                   value="{{ old('monthly_budget', $session?->monthly_budget) }}"
                                   class="w-full rounded-xl border border-stone-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-amber-300" required>
                        </div>
                    </div>
                </div>

                {{-- Grade table card with bar preview --}}
                <div class="bg-white rounded-2xl border border-stone-200 p-5 shadow-sm">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="syne font-700 text-base">Stipendija pēc vidējās atzīmes</h3>
                        <span class="text-xs text-stone-400">Grafiks atjauninās automātiski</span>
                    </div>

                    {{-- Visual distribution preview --}}
                    <div class="mb-5 p-4 rounded-xl bg-stone-50 border border-stone-100">
                        <p class="mono text-[10px] uppercase tracking-widest text-stone-400 mb-3">Stipendiju sadalījums</p>
                        <div class="space-y-2" id="tierPreview">
                            @php
                                $barColors = ['bg-red-400','bg-orange-400','bg-amber-400','bg-lime-400','bg-emerald-400','bg-teal-500'];
                                $maxAmount = collect($gradeTable)->max('amount') ?: 1;
                            @endphp
                            @foreach($gradeTable as $i => $row)
                                <div class="flex items-center gap-3">
                                    <span class="mono text-[10px] text-stone-400 w-16 flex-shrink-0">{{ number_format($row['min'], 1) }}–{{ number_format($row['max'], 2) }}</span>
                                    <div class="flex-1 bg-stone-200 rounded-full h-2 overflow-hidden">
                                        <div class="tier-bar h-full rounded-full {{ $barColors[$i] ?? 'bg-stone-300' }}"
                                             id="tierBar{{ $i }}"
                                             style="width: {{ $maxAmount > 0 ? ($row['amount'] / $maxAmount * 100) : 0 }}%"></div>
                                    </div>
                                    <span class="mono text-xs text-stone-600 w-16 text-right flex-shrink-0" id="tierLabel{{ $i }}">{{ number_format($row['amount'], 2) }} €</span>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
                        @foreach($gradeTable as $i => $row)
                            <div class="flex items-stretch gap-3 rounded-xl border border-stone-100 bg-stone-50 overflow-hidden">
                                <div class="w-1 flex-shrink-0 {{ $barColors[$i] ?? 'bg-stone-300' }}"></div>
                                <div class="flex-1 py-3 pr-3 flex items-center justify-between gap-3">
                                    <div>
                                        <p class="mono text-xs text-stone-500">Vidējā atzīme</p>
                                        <p class="mono text-sm font-medium text-stone-800 mt-0.5">
                                            {{ number_format($row['min'], 1) }}–{{ number_format($row['max'], 2) }}
                                        </p>
                                    </div>
                                    <div class="w-24">
                                        <p class="text-xs text-stone-400 mb-1">EUR/mēn.</p>
                                        <input type="number" name="amounts[]" step="0.01" min="0"
                                               value="{{ old('amounts.'.$i, $row['amount']) }}"
                                               data-tier="{{ $i }}"
                                               class="tier-input w-full rounded-lg border border-stone-200 px-2 py-1.5 text-sm mono focus:outline-none focus:ring-2 focus:ring-amber-300" required>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                {{-- Grades file upload card --}}
                <div class="bg-white rounded-2xl border border-stone-200 p-5 shadow-sm">
                    <h3 class="syne font-700 text-base mb-1">E-klases eksports</h3>
                    <p class="text-sm text-stone-500 mb-4">Augšupielādēt vērtējumu eksportu no E-klases (.xlsx).</p>

                    <div id="gradesDrop"
                         class="drop-zone border-2 border-dashed border-stone-300 rounded-xl p-8 text-center cursor-pointer flex flex-col items-center justify-center gap-3"
                         onclick="document.getElementById('gradesFileInput').click()">
                        <div class="w-14 h-14 rounded-full bg-stone-100 flex items-center justify-center">
                            <svg class="w-7 h-7 text-stone-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                        </div>
                        <div>
                            <p id="gradesDropText" class="font-medium text-stone-700 text-sm">Ievelciet E-klases eksportu šeit</p>
                            <p class="text-xs text-stone-400 mt-0.5">vai klikšķiniet, lai izvēlētos failu</p>
                        </div>
                        <input id="gradesFileInput" type="file" name="grades_file" accept=".xlsx,.xls" class="hidden" required>
                    </div>

                    {{-- File chosen indicator --}}
                    <div id="gradesFileChosen" class="hidden mt-3 rounded-xl bg-stone-50 border border-stone-200 px-4 py-3 flex items-center gap-3">
                        <svg class="w-5 h-5 text-emerald-500 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                        <span id="gradesFileName" class="text-sm text-stone-700 flex-1 truncate"></span>
                        <button type="button" id="gradesClearFile" class="text-stone-400 hover:text-stone-600 text-xs">✕</button>
                    </div>
                </div>

                {{-- Submit --}}
                <button id="calculateBtn" type="submit"
                        class="w-full rounded-2xl bg-amber-400 hover:bg-amber-300 text-stone-900 syne font-700 text-lg py-4 transition-colors disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-3">
                    <span id="calculateBtnText">Aprēķināt stipendijas</span>
                    <svg id="calculateSpinner" class="hidden w-5 h-5 spin-slow" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                    </svg>
                </button>

            </div>
        </form>
    </div>
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
        if (text) text.textContent = zoneId === 'subjectsDrop' ? 'Ievelciet failu šeit' : 'Ievelciet E-klases eksportu šeit';
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

setupDrop('subjectsDrop', 'subjectsFileInput', 'subjectsDropText', 'subjectsFileChosen', 'subjectsFileName', 'subjectsClearFile', 'subjectsSubmitBtn');
setupDrop('gradesDrop',   'gradesFileInput',   'gradesDropText',   'gradesFileChosen',   'gradesFileName',   'gradesClearFile',   null);

// ─── Subject search ───────────────────────────────────────────────────
const searchInput = document.getElementById('subjectSearch');
if (searchInput) {
    searchInput.addEventListener('input', () => {
        const q = searchInput.value.toLowerCase();
        const rows = document.querySelectorAll('.subject-row');
        let found = 0;
        rows.forEach(row => {
            const match = row.dataset.name.includes(q);
            row.classList.toggle('hidden', !match);
            if (match) found++;
        });
        document.getElementById('searchEmpty').classList.toggle('hidden', found > 0 || q === '');
    });
}

// ─── Preset popover ───────────────────────────────────────────────────
const DEFAULT_PRESETS = {
    semester_1: { start: '2025-09-01', end: '2025-12-19' },
    semester_2: { start: '2026-01-05', end: '2026-06-30' },
};

function loadPresets() {
    try { return JSON.parse(localStorage.getItem('scholarship_presets')) || DEFAULT_PRESETS; }
    catch { return DEFAULT_PRESETS; }
}

let presets = loadPresets();

function fillEditor() {
    document.getElementById('ps1s').value = presets.semester_1.start;
    document.getElementById('ps1e').value = presets.semester_1.end;
    document.getElementById('ps2s').value = presets.semester_2.start;
    document.getElementById('ps2e').value = presets.semester_2.end;
}
fillEditor();

const popover = document.getElementById('presetPopover');
const editBtn = document.getElementById('editPresetsBtn');

editBtn.addEventListener('click', (e) => {
    e.stopPropagation();
    popover.classList.toggle('open');
});

document.addEventListener('click', (e) => {
    if (!document.getElementById('presetPopoverAnchor').contains(e.target)) {
        popover.classList.remove('open');
    }
});

document.getElementById('savePresetsBtn').addEventListener('click', () => {
    presets = {
        semester_1: { start: document.getElementById('ps1s').value, end: document.getElementById('ps1e').value },
        semester_2: { start: document.getElementById('ps2s').value, end: document.getElementById('ps2e').value },
    };
    localStorage.setItem('scholarship_presets', JSON.stringify(presets));
    popover.classList.remove('open');
    syncPresetBtns();
});

const periodStart = document.getElementById('period_start');
const periodEnd   = document.getElementById('period_end');

function syncPresetBtns() {
    document.querySelectorAll('.preset-btn').forEach(btn => {
        const p = presets[btn.dataset.preset];
        const active = p && periodStart.value === p.start && periodEnd.value === p.end;
        btn.classList.toggle('active', active);
    });
}

document.querySelectorAll('.preset-btn').forEach(btn => {
    btn.addEventListener('click', () => {
        const p = presets[btn.dataset.preset];
        if (p) { periodStart.value = p.start; periodEnd.value = p.end; }
        syncPresetBtns();
    });
});
periodStart.addEventListener('change', syncPresetBtns);
periodEnd.addEventListener('change',   syncPresetBtns);
syncPresetBtns();

// ─── Grade tier bar preview ───────────────────────────────────────────
const tierInputs = document.querySelectorAll('.tier-input');

function updateBars() {
    const values = Array.from(tierInputs).map(i => parseFloat(i.value) || 0);
    const max    = Math.max(...values, 1);
    values.forEach((val, i) => {
        const bar   = document.getElementById(`tierBar${i}`);
        const label = document.getElementById(`tierLabel${i}`);
        if (bar)   bar.style.width   = `${(val / max * 100).toFixed(1)}%`;
        if (label) label.textContent = `${val.toFixed(2)} €`;
    });
}

tierInputs.forEach(input => input.addEventListener('input', updateBars));

// ─── Loading state ────────────────────────────────────────────────────
document.getElementById('calculateForm').addEventListener('submit', () => {
    const btn     = document.getElementById('calculateBtn');
    const txt     = document.getElementById('calculateBtnText');
    const spinner = document.getElementById('calculateSpinner');
    btn.disabled      = true;
    txt.textContent   = 'Aprēķina…';
    spinner.classList.remove('hidden');
    document.getElementById('subjectsForm').querySelectorAll('input, button').forEach(el => el.disabled = true);
});
</script>
</body>
</html>