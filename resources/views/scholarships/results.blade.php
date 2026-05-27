<!doctype html>
<html lang="lv">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Stipendiju rezultāti</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-100 text-slate-900">
<div class="max-w-7xl mx-auto p-4 md:p-6">
    <div class="sticky top-0 z-20 -mx-4 md:-mx-6 px-4 md:px-6 py-4 bg-slate-100/95 backdrop-blur border-b border-slate-200 mb-6">
        <div class="flex flex-col gap-4">
            <div class="flex flex-col gap-3 md:flex-row md:items-end md:justify-between">
                <div>
                    <p class="text-sm uppercase tracking-[0.2em] text-slate-500">Rezultāti</p>
                    <h1 class="text-3xl font-bold">Stipendiju rezultāti</h1>
                </div>
                <div class="flex flex-wrap gap-2">
                    <a href="/" class="px-4 py-2 rounded-lg bg-slate-700 text-white hover:bg-slate-800 transition-colors">Pārrēķināt</a>
                    <a href="/results/export?session={{ $session->id }}" class="px-4 py-2 rounded-lg bg-indigo-600 text-white hover:bg-indigo-700 transition-colors">Eksportēt uz Excel</a>
                </div>
            </div>

            @php $diff = (float) $session->monthly_budget - (float) $results['total']; @endphp
            <div class="grid gap-3 md:grid-cols-3">
                <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-4">
                    <div class="text-sm text-slate-500">Kopējā izmaksu summa</div>
                    <div id="totalPayout" class="text-2xl font-bold mt-1">{{ number_format($results['total'], 2) }} EUR</div>
                </div>
                <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-4">
                    <div class="text-sm text-slate-500">Budžets</div>
                    <div class="text-2xl font-bold mt-1">{{ number_format($session->monthly_budget, 2) }} EUR</div>
                </div>
                <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-4">
                    <div class="text-sm text-slate-500">Starpība</div>
                    <div id="budgetDifference" class="text-2xl font-bold mt-1 {{ $diff >= 0 ? 'text-emerald-600' : 'text-red-600' }}">{{ number_format($diff, 2) }} EUR</div>
                </div>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-4 mb-6 overflow-auto">
        <h2 class="text-lg font-semibold mb-3">Kopsavilkums pa skolēniem</h2>
        <table class="w-full text-sm" id="studentsTable">
            <thead>
            <tr class="border-b border-slate-200">
                <th class="text-left py-2 pr-4">Uzvārds</th>
                <th class="text-left py-2 pr-4">Vārds</th>
                <th class="text-left py-2 pr-4">Personas kods</th>
                <th class="text-left py-2 pr-4">Grupa</th>
                <th class="text-left py-2 pr-4">Vidējais vērtējums</th>
                <th class="text-left py-2">Stipendija (EUR)</th>
            </tr>
            </thead>
            <tbody>
            @foreach($results['items'] as $row)
                <tr class="border-b border-slate-100" data-student-id="{{ $row['student']->id }}">
                    <td class="py-2 pr-4">{{ $row['student']->surname }}</td>
                    <td class="py-2 pr-4">{{ $row['student']->first_name }}</td>
                    <td class="py-2 pr-4">{{ $row['student']->personal_id }}</td>
                    <td class="py-2 pr-4">{{ $row['student']->group_name }}</td>
                    <td class="py-2 pr-4 avg">{{ $row['average'] ?? '-' }}</td>
                    <td class="py-2 scholarship">{{ number_format($row['scholarship'], 2) }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>

    <div class="space-y-6">
        @foreach($groups as $groupName => $group)
            <section class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
                <div class="px-4 py-4 border-b border-slate-200 flex flex-col gap-1 md:flex-row md:items-center md:justify-between">
                    <h2 class="text-xl font-semibold">Grupa {{ $groupName }}</h2>
                    <p class="text-sm text-slate-500">Atzīmē “Izslēgt”, lai pārrēķins notiek uzreiz</p>
                </div>

                <div class="overflow-auto">
                    <table class="min-w-full text-sm">
                        <thead class="bg-slate-50">
                        <tr>
                            <th class="sticky left-0 z-10 bg-slate-50 border-b border-r border-slate-200 p-3 text-left min-w-[220px]">Skolēns</th>
                            <th class="border-b border-r border-slate-200 p-3 text-left min-w-[130px]">Vidējais</th>
                            <th class="border-b border-r border-slate-200 p-3 text-left min-w-[140px]">Stipendija</th>
                            @foreach($group['subjects'] as $subject)
                                <th class="border-b border-r border-slate-200 p-3 text-left min-w-[180px]">{{ $subject }}</th>
                            @endforeach
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($group['students'] as $student)
                            @php
                                $summaryRow = collect($results['items'])->firstWhere('student.id', $student->id);
                            @endphp
                            <tr class="odd:bg-white even:bg-slate-50/50">
                                <td class="sticky left-0 z-10 border-b border-r border-slate-200 bg-inherit p-3 font-medium">
                                    {{ $student->surname }} {{ $student->first_name }}
                                </td>
                                <td class="border-b border-r border-slate-200 p-3">
                                    <span class="group-avg" data-student-id="{{ $student->id }}">{{ $summaryRow['average'] ?? '-' }}</span>
                                </td>
                                <td class="border-b border-r border-slate-200 p-3">
                                    <span class="group-scholarship font-semibold" data-student-id="{{ $student->id }}">{{ number_format($summaryRow['scholarship'] ?? 0, 2) }}</span>
                                    <span class="text-slate-500">EUR</span>
                                </td>
                                @foreach($group['subjects'] as $subject)
                                    @php $grade = $student->grades->firstWhere('subject_name', $subject); @endphp
                                    <td class="border-b border-r border-slate-200 p-3 align-top">
                                        @if($grade)
                                            <div class="font-semibold">{{ $grade->grade_value }}</div>
                                            <label class="mt-2 inline-flex items-center gap-2 text-xs text-slate-600">
                                                <input type="checkbox" class="exclude-grade rounded border-slate-300" data-grade-id="{{ $grade->id }}" data-student-id="{{ $student->id }}" {{ $grade->excluded ? 'checked' : '' }}>
                                                <span>Izslēgt</span>
                                            </label>
                                        @else
                                            <span class="text-slate-400">-</span>
                                        @endif
                                    </td>
                                @endforeach
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </section>
        @endforeach
    </div>
</div>

<script>
    const formatValue = (value) => value ?? '-';

    const updateStudentWidgets = (studentId, data) => {
        const summaryRow = document.querySelector(`tr[data-student-id='${studentId}']`);
        if (summaryRow) {
            summaryRow.querySelector('.avg').textContent = formatValue(data.average);
            summaryRow.querySelector('.scholarship').textContent = data.scholarship;
        }

        document.querySelectorAll(`.group-avg[data-student-id='${studentId}']`).forEach((el) => {
            el.textContent = formatValue(data.average);
        });

        document.querySelectorAll(`.group-scholarship[data-student-id='${studentId}']`).forEach((el) => {
            el.textContent = data.scholarship;
        });
    };

    document.querySelectorAll('.exclude-grade').forEach((el) => {
        el.addEventListener('change', async () => {
            const formData = new FormData();
            formData.append('_token', '{{ csrf_token() }}');
            formData.append('grade_record_id', el.dataset.gradeId);
            formData.append('excluded', el.checked ? '1' : '0');

            const res = await fetch('/results/exclude-grade', { method: 'POST', body: formData });
            if (!res.ok) return;

            const data = await res.json();
            updateStudentWidgets(el.dataset.studentId, data);

            const totalEl = document.getElementById('totalPayout');
            const diffEl = document.getElementById('budgetDifference');

            if (totalEl) totalEl.textContent = `${data.total} EUR`;
            if (diffEl) {
                diffEl.textContent = `${data.difference} EUR`;
                diffEl.classList.toggle('text-emerald-600', !!data.difference_positive);
                diffEl.classList.toggle('text-red-600', !data.difference_positive);
            }
        });
    });
</script>
</body>
</html>
