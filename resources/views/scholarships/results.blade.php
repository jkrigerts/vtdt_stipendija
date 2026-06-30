<!doctype html>
<html lang="lv">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Stipendiju rezultāti</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=DM+Mono:wght@400;500&family=Syne:wght@600;700;800&family=Inter:wght@400;500&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
        h1, h2, h3, .syne { font-family: 'Syne', sans-serif; }
        .mono { font-family: 'DM Mono', monospace; }

        .group-anchor-btn.active { background: #1c1917; color: #fef3c7; border-color: #1c1917; }

        /* ── Excluded grade ── */
        .grade-value.excluded { text-decoration: line-through; color: #a8a29e; }

        /* ── Toggle switch ── */
        .toggle-track {
            width: 36px; height: 20px;
            border-radius: 10px;
            background: #e7e5e4;
            position: relative;
            cursor: pointer;
            transition: background .2s;
            flex-shrink: 0;
        }
        .toggle-track.on { background: #f59e0b; }
        .toggle-thumb {
            position: absolute;
            top: 3px; left: 3px;
            width: 14px; height: 14px;
            border-radius: 50%;
            background: white;
            box-shadow: 0 1px 3px rgba(0,0,0,.25);
            transition: transform .2s;
        }
        .toggle-track.on .toggle-thumb { transform: translateX(16px); }

        /* ── Undo toast ── */
        #undoToast {
            position: fixed;
            bottom: 1.5rem;
            left: 50%;
            transform: translateX(-50%) translateY(calc(100% + 3rem));
            transition: transform .25s ease-in;
            z-index: 100;
            pointer-events: none;
        }
        #undoToast.visible {
            transform: translateX(-50%) translateY(0);
            transition: transform .3s cubic-bezier(.34,1.4,.64,1);
            pointer-events: all;
        }

        /* ── Highlighted summary row ── */
        tr.highlight-student td { background: #fef9c3 !important; }
        tr { transition: background .3s; }

        /* ── Skeleton pulse for async update ── */
        @keyframes pulse { 0%,100% { opacity:1; } 50% { opacity:.4; } }
        .updating { animation: pulse .7s ease infinite; }
    </style>
</head>
<body class="bg-stone-100 text-stone-900 min-h-screen">

{{-- ─── UNDO TOAST ──────────────────────────────────────────────────── --}}
<div id="undoToast" role="status" aria-live="polite">
    <div class="bg-stone-900 text-stone-100 rounded-2xl px-5 py-3 flex items-center gap-4 shadow-2xl">
        <svg class="w-4 h-4 text-amber-400 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
        <span id="undoToastMsg" class="text-sm mono"></span>
        <button id="undoBtn" class="ml-2 px-3 py-1 rounded-lg bg-amber-400 text-stone-900 text-xs font-bold hover:bg-amber-300 transition-colors">
            Atcelt
        </button>
        <button id="undoCloseBtn" class="text-stone-500 hover:text-stone-300 text-xs ml-1">✕</button>
    </div>
</div>

{{-- ─── STICKY TOP BAR ──────────────────────────────────────────────── --}}
<div class="sticky top-0 z-30 bg-stone-900 text-stone-100 border-b border-stone-800">
    <div class="max-w-7xl mx-auto px-4 md:px-5 py-3">
        <div class="flex items-center justify-between gap-4">

            {{-- Logo --}}
            <div class="flex items-center gap-3 flex-shrink-0">
                <div class="w-7 h-7 bg-amber-400 rounded flex items-center justify-center">
                    <svg class="w-4 h-4 text-stone-900" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <span class="syne font-700 text-sm tracking-wide hidden sm:block">Stipendijas</span>
            </div>

            {{-- Inline stats (desktop) --}}
            @php $diff = (float) $session->monthly_budget - (float) $results['total']; @endphp
            <div class="hidden md:flex items-center gap-5 text-xs">
                <div>
                    <p class="text-stone-500 mono uppercase tracking-widest text-[10px]">Kopā</p>
                    <p class="font-semibold mono" id="totalPayoutBar">{{ number_format($results['total'], 2) }} EUR</p>
                </div>
                <div class="w-px h-6 bg-stone-700"></div>
                <div>
                    <p class="text-stone-500 mono uppercase tracking-widest text-[10px]">Budžets</p>
                    <p class="font-semibold mono">{{ number_format($session->monthly_budget, 2) }} EUR</p>
                </div>
                <div class="w-px h-6 bg-stone-700"></div>
                <div>
                    <p class="text-stone-500 mono uppercase tracking-widest text-[10px]">Starpība</p>
                    <p id="budgetDiffBar" class="font-semibold mono {{ $diff > 0 ? 'text-emerald-400' : ($diff < 0 ? 'text-red-400' : 'text-stone-400') }}">
                        {{ number_format($diff, 2) }} EUR
                    </p>
                </div>
            </div>

            {{-- Actions --}}
            <div class="flex items-center gap-2">
                <a href="/dashboard" class="px-3 py-1.5 rounded-lg border border-stone-600 text-stone-300 text-xs hover:bg-stone-800 transition-colors mono">
                    ← Pārrēķināt
                </a>
                <a href="/results/export?session={{ $session->id }}"
                   class="px-3 py-1.5 rounded-lg bg-amber-400 text-stone-900 text-xs font-medium hover:bg-amber-300 transition-colors mono">
                    ⬇ Excel
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
</div>

<div class="max-w-7xl mx-auto px-4 md:px-6 py-6 space-y-6">

    {{-- ─── STATS CARDS ────────────────────────────────────────────── --}}
    <div class="grid grid-cols-3 gap-3">
        <div class="bg-white rounded-2xl border border-stone-200 shadow-sm p-4 md:p-5">
            <p class="mono text-[10px] uppercase tracking-widest text-stone-400">Kopējā summa</p>
            <p id="totalPayout" class="syne font-800 text-xl md:text-3xl mt-1 tracking-tight">
                {{ number_format($results['total'], 2) }}
                <span class="text-sm md:text-base font-400 text-stone-400">EUR</span>
            </p>
        </div>
        <div class="bg-white rounded-2xl border border-stone-200 shadow-sm p-4 md:p-5">
            <p class="mono text-[10px] uppercase tracking-widest text-stone-400">Budžets</p>
            <p class="syne font-800 text-xl md:text-3xl mt-1 tracking-tight">
                {{ number_format($session->monthly_budget, 2) }}
                <span class="text-sm md:text-base font-400 text-stone-400">EUR</span>
            </p>
        </div>
        @php
            $diffCardBg    = $diff > 0 ? 'bg-emerald-50 border-emerald-200' : ($diff < 0 ? 'bg-red-50 border-red-200' : 'bg-white border-stone-200');
            $diffTextColor = $diff > 0 ? 'text-emerald-600' : ($diff < 0 ? 'text-red-600' : 'text-stone-500');
        @endphp
        <div id="diffCard" class="rounded-2xl border shadow-sm p-4 md:p-5 {{ $diffCardBg }}">
            <p class="mono text-[10px] uppercase tracking-widest text-stone-400">Starpība</p>
            <p id="budgetDifference" class="syne font-800 text-xl md:text-3xl mt-1 tracking-tight {{ $diffTextColor }}">
                {{ number_format($diff, 2) }}
                <span class="text-sm md:text-base font-400">EUR</span>
            </p>
        </div>
    </div>

    <div class="flex flex-wrap items-center gap-2">
        <span class="mono text-xs text-stone-400 mr-1">Noņemt skolēnus, kas ir atskaitīti:</span>
            <a href="/students/list/create"
                class="group-anchor-btn mono text-xs px-3 py-1.5 rounded-lg border border-stone-300 bg-white hover:border-stone-500 transition-colors scroll-smooth">
                Augšupielādēt sarakstu
            </a>
    </div>

        {{-- ─── GROUP ANCHOR NAVIGATION ────────────────────────────────── --}}
    @if(count($groups) > 1)
        <div class="flex flex-wrap items-center gap-2">
            <span class="mono text-xs text-stone-400 mr-1">Pāriet uz:</span>
            @foreach($groups as $groupName => $group)
                <a href="/results/{{ $groupName }}"
                   class="group-anchor-btn mono text-xs px-3 py-1.5 rounded-lg border border-stone-300 bg-white hover:border-stone-500 transition-colors scroll-smooth">
                    {{ $groupName }}
                    <span class="text-stone-400 ml-1">{{ count($group['students']) }}</span>
                </a>
            @endforeach
        </div>
    @endif

    {{-- ─── SUMMARY TABLE ──────────────────────────────────────────── --}}
    <div class="bg-white rounded-2xl border border-stone-200 shadow-sm overflow-hidden">
        <div class="px-5 py-4 border-b border-stone-100 flex items-center justify-between">
            <h2 class="syne font-700">Kopsavilkums pa skolēniem</h2>
            <p class="text-xs text-stone-400 hidden sm:block">Klikšķini uz rindiņas, lai ritinātu uz grupu</p>
        </div>
        <div class="overflow-auto">
            <table class="w-full text-sm" id="studentsTable">
                <thead>
                <tr class="bg-stone-50 border-b border-stone-100">
                    <th class="text-left py-3 px-4 mono text-[11px] uppercase tracking-widest text-stone-400 font-medium">Uzvārds</th>
                    <th class="text-left py-3 px-4 mono text-[11px] uppercase tracking-widest text-stone-400 font-medium">Vārds</th>
                    <th class="text-left py-3 px-4 mono text-[11px] uppercase tracking-widest text-stone-400 font-medium hidden md:table-cell">Personas kods</th>
                    <th class="text-left py-3 px-4 mono text-[11px] uppercase tracking-widest text-stone-400 font-medium">Grupa</th>
                    <th class="text-left py-3 px-4 mono text-[11px] uppercase tracking-widest text-stone-400 font-medium">Vidējais</th>
                    <th class="text-left py-3 px-4 mono text-[11px] uppercase tracking-widest text-stone-400 font-medium">Nesekmīgi</th>
                    <th class="text-left py-3 px-4 mono text-[11px] uppercase tracking-widest text-stone-400 font-medium">NV</th>
                    <th class="text-left py-3 px-4 mono text-[11px] uppercase tracking-widest text-stone-400 font-medium">Tukšs</th>
                    <th class="text-left py-3 px-4 mono text-[11px] uppercase tracking-widest text-stone-400 font-medium">Stipendija</th>
                </tr>
                </thead>
                <tbody>
                @foreach($results['items'] as $row)
                    @if(!$row['student']->excluded)
                        @if($row['student']->current_group)
                            <tr class="border-t border-stone-100 hover:bg-amber-50 cursor-pointer transition-colors"
                                data-student-id="{{ $row['student']->id }}"
                                data-group="{{ Str::slug($row['student']->group_name) }}"
                                onclick="scrollToStudent({{ $row['student']->id }}, '{{ Str::slug($row['student']->group_name) }}')">
                                <td class="py-3 px-4 font-medium">{{ $row['student']->surname }}</td>
                                <td class="py-3 px-4">{{ $row['student']->first_name }}</td>
                                <td class="py-3 px-4 mono text-xs text-stone-400 hidden md:table-cell">{{ $row['student']->personal_id }}</td>
                                <td class="py-3 px-4">
                                    <span class="mono text-xs bg-stone-100 text-stone-600 px-2 py-0.5 rounded-full">{{ $row['student']->group_name }}</span>
                                </td>
                                <td class="py-3 px-4 mono avg">{{ $row['average'] ?? '—' }}</td>
                                <td class="py-3 px-4 mono">{{ $row["insufficient"]}}</td>
                                <td class="py-3 px-4 mono">{{ $row["nv"] }}</td>
                                <td class="py-3 px-4 mono">{{ $row["noGrade"] }}</td>
                                <td class="py-3 px-4">
                                    @php $s = $row['scholarship']; @endphp
                                    <span class="scholarship mono font-medium {{ $s > 0 ? 'text-emerald-600' : 'text-stone-400' }}">{{ number_format($s, 2) }}</span>
                                    <span class="text-stone-400 text-xs">EUR</span>
                                </td>
                            </tr>
                        @endif
                    @endif
                @endforeach
                </tbody>
            </table>
        </div>
    </div>



</div>

<script>
    const budget = {{ (float) $session->monthly_budget }};

    // ─── Scroll-to-student from summary table ──────────────────────
    function scrollToStudent(studentId, groupSlug) {
        const section = document.getElementById(`group-${groupSlug}`);
        const row     = document.getElementById(`student-row-${studentId}`);
        if (section) section.scrollIntoView({ behavior: 'smooth', block: 'start' });
        if (row) {
            setTimeout(() => {
                row.classList.add('highlight-student');
                setTimeout(() => row.classList.remove('highlight-student'), 1800);
            }, 350);
        }
    }

    // ─── Stat update helpers ───────────────────────────────────────
    function diffClass(diff, light = false) {
        if (diff > 0) return light ? 'text-emerald-400' : 'text-emerald-600';
        if (diff < 0) return light ? 'text-red-400'     : 'text-red-600';
        return light ? 'text-stone-400' : 'text-stone-500';
    }

    function updateStats(totalStr, diffStr, diffNum) {
        const tp = document.getElementById('totalPayout');
        if (tp) tp.innerHTML = `${totalStr} <span class="text-sm md:text-base font-400 text-stone-400">EUR</span>`;

        const bd = document.getElementById('budgetDifference');
        if (bd) {
            bd.innerHTML = `${diffStr} <span class="text-sm md:text-base font-400">EUR</span>`;
            bd.className = `syne font-800 text-xl md:text-3xl mt-1 tracking-tight ${diffClass(diffNum)}`;
        }

        // Diff card bg
        const card = document.getElementById('diffCard');
        if (card) {
            card.className = 'rounded-2xl border shadow-sm p-4 md:p-5 ';
            if (diffNum > 0)      card.className += 'bg-emerald-50 border-emerald-200';
            else if (diffNum < 0) card.className += 'bg-red-50 border-red-200';
            else                  card.className += 'bg-white border-stone-200';
        }

        // Top bar
        const tpb = document.getElementById('totalPayoutBar');
        if (tpb) tpb.textContent = `${totalStr} EUR`;
        const bdb = document.getElementById('budgetDiffBar');
        if (bdb) {
            bdb.textContent = `${diffStr} EUR`;
            bdb.className   = `font-semibold mono ${diffClass(diffNum, true)}`;
        }
    }

    function updateStudent(studentId, data) {
        const sch      = parseFloat(data.scholarship);
        const schClass = `scholarship mono font-medium ${sch > 0 ? 'text-emerald-600' : 'text-stone-400'}`;

        // Summary table
        const summaryRow = document.querySelector(`tr[data-student-id='${studentId}']`);
        if (summaryRow) {
            summaryRow.querySelector('.avg').textContent = data.average ?? '—';
            const schEl = summaryRow.querySelector('.scholarship');
            if (schEl) { schEl.textContent = data.scholarship; schEl.className = schClass; }
        }

        // Group table
        document.querySelectorAll(`.group-avg[data-student-id='${studentId}']`).forEach(el => {
            el.textContent = data.average ?? '—';
            el.classList.remove('updating');
        });
        document.querySelectorAll(`.group-scholarship[data-student-id='${studentId}']`).forEach(el => {
            el.textContent = data.scholarship;
            el.className   = `group-scholarship mono font-medium ${sch > 0 ? 'text-emerald-600' : 'text-stone-400'}`;
            el.classList.remove('updating');
        });
    }

    // ─── Undo toast ────────────────────────────────────────────────
    let undoTimeout  = null;
    let undoCallback = null;

    function showUndo(msg, onUndo) {
        clearTimeout(undoTimeout);
        undoCallback = onUndo;

        const toast = document.getElementById('undoToast');
        document.getElementById('undoToastMsg').textContent = msg;
        toast.classList.add('visible');

        undoTimeout = setTimeout(() => dismissUndo(), 5000);
    }

    function dismissUndo() {
        clearTimeout(undoTimeout);
        document.getElementById('undoToast').classList.remove('visible');
        undoCallback = null;
    }

    document.getElementById('undoBtn').addEventListener('click', async () => {
        if (undoCallback) await undoCallback();
        dismissUndo();
    });
    document.getElementById('undoCloseBtn').addEventListener('click', dismissUndo);

    // ─── Toggle switch logic ───────────────────────────────────────
    async function setExclude(gradeId, studentId, excluded) {
        // Optimistic UI
        const gradeEl = document.querySelector(`.grade-value[data-grade-id='${gradeId}']`);
        if (gradeEl) gradeEl.classList.toggle('excluded', excluded);

        const track = document.querySelector(`.toggle-track[data-grade-id='${gradeId}']`);
        if (track) {
            track.classList.toggle('on', excluded);
            track.setAttribute('aria-checked', excluded ? 'true' : 'false');
        }

        // Show updating pulse
        document.querySelectorAll(`.group-avg[data-student-id='${studentId}'],
                                   .group-scholarship[data-student-id='${studentId}']`)
                .forEach(el => el.classList.add('updating'));

        const fd = new FormData();
        fd.append('_token', '{{ csrf_token() }}');
        fd.append('grade_record_id', gradeId);
        fd.append('excluded', excluded ? '1' : '0');

        const res = await fetch('/results/exclude-grade', { method: 'POST', body: fd });
        if (!res.ok) {
            // Revert
            if (gradeEl) gradeEl.classList.toggle('excluded', !excluded);
            if (track)   { track.classList.toggle('on', !excluded); track.setAttribute('aria-checked', (!excluded).toString()); }
            document.querySelectorAll(`.group-avg[data-student-id='${studentId}'],
                                       .group-scholarship[data-student-id='${studentId}']`)
                    .forEach(el => el.classList.remove('updating'));
            return;
        }

        const data = await res.json();
        updateStudent(studentId, data);

        const totalNum = parseFloat(data.total);
        const diffNum  = budget - totalNum;
        updateStats(data.total, data.difference, diffNum);
    }

    document.querySelectorAll('.toggle-track').forEach(track => {
        function handleToggle() {
            const gradeId   = track.dataset.gradeId;
            const studentId = track.dataset.studentId;
            const willExclude = !track.classList.contains('on');

            setExclude(gradeId, studentId, willExclude);

            const gradeName = document.querySelector(`.grade-value[data-grade-id='${gradeId}']`)?.textContent || '';
            const action    = willExclude ? 'izslēgta' : 'ieslēgta';
            showUndo(`Atzīme ${gradeName} ${action}`, async () => {
                await setExclude(gradeId, studentId, !willExclude);
            });
        }

        track.addEventListener('click', handleToggle);
        track.addEventListener('keydown', e => {
            if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); handleToggle(); }
        });
    });
</script>
</body>
</html>