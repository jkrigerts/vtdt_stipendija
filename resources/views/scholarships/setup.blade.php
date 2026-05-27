<!doctype html>
<html lang="lv">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Stipendiju kalkulators</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-100 text-slate-900">
<div class="max-w-6xl mx-auto p-6">
    <h1 class="text-3xl font-bold mb-6">Stipendiju kalkulators - iestatījumi</h1>

    @if(session('status'))
        <div class="mb-4 rounded bg-emerald-100 p-3 text-emerald-900">{{ session('status') }}</div>
    @endif

    <div class="grid gap-6 md:grid-cols-2">
        <form method="POST" action="/subjects/upload" enctype="multipart/form-data" class="rounded bg-white p-4 shadow">
            @csrf
            <h2 class="mb-3 text-xl font-semibold">Augšupielādēt `subjects.xlsx`</h2>
            <input type="file" name="subjects_file" class="mb-3 block w-full" required>
            <button class="rounded bg-indigo-600 px-4 py-2 text-white">Augšupielādēt priekšmetus</button>
        </form>

        <div class="rounded bg-white p-4 shadow">
            <h2 class="mb-3 text-xl font-semibold">Ielādētie priekšmeti</h2>
            <div class="max-h-72 overflow-auto">
                <table class="w-full text-sm">
                    <thead>
                    <tr>
                        <th class="text-left">Nosaukums</th>
                        <th class="text-left">Kategorija</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse($subjects as $subject)
                        <tr>
                            <td class="py-1">{{ $subject->name }}</td>
                            <td>{{ $subject->category }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="2" class="py-2 text-slate-500">Nav ielādētu priekšmetu.</td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <form method="POST" action="/calculate" enctype="multipart/form-data" class="mt-6 rounded bg-white p-4 shadow">
        @csrf
        <h2 class="mb-4 text-xl font-semibold">Aprēķina iestatījumi</h2>

        <div class="mb-4">
            <label for="period_preset" class="mb-1 block text-sm">Semestra izvēlne</label>
            <select id="period_preset" class="w-full rounded border px-3 py-2 md:w-80">
                <option value="custom">Pielāgots periods</option>
                <option value="semester_1">1. semestris</option>
                <option value="semester_2">2. semestris</option>
            </select>
        </div>

        <div class="grid gap-4 md:grid-cols-3">
            <div>
                <label class="mb-1 block text-sm">Perioda sākums</label>
                <input id="period_start" type="date" name="period_start" value="{{ old('period_start', optional($session?->period_start)->format('Y-m-d')) }}" class="w-full rounded border px-3 py-2" required>
            </div>
            <div>
                <label class="mb-1 block text-sm">Perioda beigas</label>
                <input id="period_end" type="date" name="period_end" value="{{ old('period_end', optional($session?->period_end)->format('Y-m-d')) }}" class="w-full rounded border px-3 py-2" required>
            </div>
            <div>
                <label class="mb-1 block text-sm">Mēneša budžets (EUR)</label>
                <input type="number" step="0.01" min="0" name="monthly_budget" value="{{ old('monthly_budget', $session?->monthly_budget) }}" class="w-full rounded border px-3 py-2" required>
            </div>
        </div>

        <p class="mt-3 text-sm text-slate-600">Piemērs: 1. semestris: 2025-09-01 līdz 2025-12-19, 2. semestris: 2026-01-05 līdz 2026-06-30</p>

        <h3 class="mb-2 mt-5 text-lg font-semibold">Vidējās atzīmes un stipendijas summa</h3>
        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
            @foreach($gradeTable as $i => $row)
                <label class="block rounded-xl border border-slate-200 bg-slate-50 p-4 text-sm">
                    <span class="block font-semibold text-slate-800">
                        {{ number_format($row['min'], 2) }} - {{ number_format($row['max'], 2) }}
                    </span>
                    <span class="mt-3 block">
                        Summa (EUR)
                        <input
                            type="number"
                            name="amounts[]"
                            step="0.01"
                            min="0"
                            value="{{ old('amounts.'.$i, $row['amount']) }}"
                            class="mt-1 w-full rounded border px-3 py-2"
                            required
                        >
                    </span>
                </label>
            @endforeach
        </div>

        <div class="mt-5">
            <label class="mb-1 block text-sm">Augšupielādēt E-klases eksportu (.xlsx)</label>
            <input type="file" name="grades_file" class="block w-full" required>
        </div>

        <button class="mt-5 rounded bg-emerald-600 px-4 py-2 text-white">Aprēķināt stipendijas</button>
    </form>
</div>

<script>
    const periodPreset = document.getElementById('period_preset');
    const periodStart = document.getElementById('period_start');
    const periodEnd = document.getElementById('period_end');

    const presets = {
        semester_1: { start: '2025-09-01', end: '2025-12-19' },
        semester_2: { start: '2026-01-05', end: '2026-06-30' },
    };

    const syncPresetFromDates = () => {
        const matchedPreset = Object.entries(presets).find(([, dates]) => {
            return dates.start === periodStart.value && dates.end === periodEnd.value;
        });

        periodPreset.value = matchedPreset ? matchedPreset[0] : 'custom';
    };

    periodPreset.addEventListener('change', () => {
        const selectedPreset = presets[periodPreset.value];

        if (!selectedPreset) {
            return;
        }

        periodStart.value = selectedPreset.start;
        periodEnd.value = selectedPreset.end;
    });

    periodStart.addEventListener('change', syncPresetFromDates);
    periodEnd.addEventListener('change', syncPresetFromDates);
    syncPresetFromDates();
</script>
</body>
</html>
