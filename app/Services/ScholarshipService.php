<?php

namespace App\Services;

use App\Models\CalculationSession;
use App\Models\GradeRecord;
use App\Models\Student;
use App\Models\Subject;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

class ScholarshipService
{
    private const IGNORE_SHEETS = ['Apv. žurn. - žurn. pārb.', 'Ind. d. žurn. - žurn. pārb.'];

    public function importSubjects(UploadedFile $file): array
    {
        $rows = Excel::toArray([], $file)[0] ?? [];

        Subject::query()->delete();

        $insert = [];
        foreach (array_slice($rows, 1) as $row) {
            $name = trim((string) ($row[0] ?? ''));
            $category = strtoupper(trim((string) ($row[1] ?? '')));
            if ($name === '' || !in_array($category, ['VIMP', 'PROF'], true)) {
                continue;
            }
            $insert[] = ['name' => $name, 'category' => $category, 'created_at' => now(), 'updated_at' => now()];
        }

        if ($insert !== []) {
            Subject::insert($insert);
        }

        return Subject::orderBy('name')->get()->toArray();
    }

    public function calculate(array $data, UploadedFile $file): CalculationSession
    {
        return DB::transaction(function () use ($data, $file) {

            GradeRecord::query()->delete();
            Student::query()->delete();
            CalculationSession::query()->delete();

            $session = CalculationSession::create([
                'period_start' => $data['period_start'],
                'period_end' => $data['period_end'],
                'monthly_budget' => $data['monthly_budget'],
                'grade_table' => $data['grade_table'],
            ]);

            $periodStart = Carbon::parse($data['period_start']);
            $periodEnd = Carbon::parse($data['period_end']);

            $subjects = Subject::query()->pluck('category', 'name');
            $sheets = Excel::toArray([], $file);

            $studentsToInsert = [];
            $studentKeyMap = [];

            foreach ($sheets as $sheetIndex => $sheet) {
                $groupName = trim((string) ($sheet[0][0] ?? ''));
                if ($groupName === '' || in_array($groupName, self::IGNORE_SHEETS, true)) {
                    continue;
                }

                $surnames = $sheet[1] ?? [];
                $firstNames = $sheet[2] ?? [];
                $ids = $sheet[3] ?? [];

                foreach ($this->detectStudentColumns($ids) as $col) {
                    $surname = trim((string) ($surnames[$col] ?? ''));
                    $firstName = trim((string) ($firstNames[$col] ?? ''));
                    $personalId = trim((string) ($ids[$col] ?? ''));

                    if ($personalId === '' || ($surname === '' && $firstName === '')) {
                        continue;
                    }

                    $uniqueKey = $sheetIndex . '|' . $col;

                    $studentsToInsert[] = [
                        'session_id' => $session->id,
                        'surname' => $surname,
                        'first_name' => $firstName,
                        'personal_id' => $personalId,
                        'group_name' => $groupName,
                    ];

                    $studentKeyMap[$uniqueKey] = [
                        'personal_id' => $personalId,
                        'group_name' => $groupName,
                    ];
                }
            }


            Student::insert($studentsToInsert);

    
            $students = Student::where('session_id', $session->id)
                ->get()
                ->keyBy(fn($s) => $s->personal_id . '|' . $s->group_name);

            $gradeRecordsToInsert = [];

            foreach ($sheets as $sheetIndex => $sheet) {
                $groupName = trim((string) ($sheet[0][0] ?? ''));
                if ($groupName === '' || in_array($groupName, self::IGNORE_SHEETS, true)) {
                    continue;
                }

                $ids = $sheet[3] ?? [];
                $studentColumns = $this->detectStudentColumns($ids);

                $studentsByColumn = [];
                foreach ($studentColumns as $col) {
                    $key = $sheetIndex . '|' . $col;

                    if (!isset($studentKeyMap[$key])) {
                        continue;
                    }

                    $map = $studentKeyMap[$key];
                    $lookupKey = $map['personal_id'] . '|' . $map['group_name'];

                    if (isset($students[$lookupKey])) {
                        $studentsByColumn[$col] = $students[$lookupKey];
                    }
                }

                $subjectValues = [];

                foreach (array_slice($sheet, 4) as $row) {
                    if (trim((string) ($row[0] ?? '')) !== 'Žurnāls') {
                        continue;
                    }

                    $subject = trim((string) ($row[1] ?? ''));
                    $gradeType = trim((string) ($row[4] ?? ''));

                    if (!in_array($gradeType, [
                        'I semestra vērtējums',
                        'II semestra vērtējums',
                        'Galīgais vērtējums priekšmetā'
                    ], true)) {
                        continue;
                    }

                    $dateRaw = trim((string) ($row[3] ?? ''));
                    if ($dateRaw === '') {
                        continue;
                    }

                    $date = Carbon::createFromFormat('d.m.Y', $dateRaw);

                    if ($date->lt($periodStart) || $date->gt($periodEnd)) {
                        continue;
                    }

                    $category = $subjects[$subject] ?? 'VIMP';
                    $priority = $gradeType === 'Galīgais vērtējums priekšmetā' ? 2 : 1;

                    foreach ($studentsByColumn as $col => $student) {
                        $raw = $row[$col] ?? null;
                        if ($raw === null || $raw === '') {
                            continue;
                        }

                        $gradeValue = strtolower(trim((string) $raw));

                        if ($gradeValue === 'n' || str_contains($gradeValue, '%')) {
                            continue;
                        }

                        $key = $student->id . '|' . $subject;

                        if (!isset($subjectValues[$key]) || $priority > $subjectValues[$key]['priority']) {
                            $subjectValues[$key] = [
                                'student_id' => $student->id,
                                'subject_name' => $subject,
                                'grade_type' => $gradeType,
                                'grade_value' => $gradeValue,
                                'grade_date' => $date->toDateString(),
                                'priority' => $priority,
                            ];
                        }
                    }
                }

                foreach ($subjectValues as $value) {
                    $gradeRecordsToInsert[] = [
                        'student_id' => $value['student_id'],
                        'subject_name' => $value['subject_name'],
                        'grade_type' => $value['grade_type'],
                        'grade_value' => $value['grade_value'],
                        'grade_date' => $value['grade_date'],
                    ];
                }
            }


            foreach (array_chunk($gradeRecordsToInsert, 1000) as $chunk) {
                GradeRecord::insert($chunk);
            }

            return $session;
        });
    }
    public function buildResults(CalculationSession $session, $group = ""): array
    {
        $students = Student::query()
            ->where('session_id', $session->id)
            ->when($group, function ($query, $group) {
                return $query->where('group_name', $group);
            })
            ->with('grades')
            ->orderBy('group_name')
            ->orderBy('surname')
            ->get();

        $items = $students->map(function (Student $student) use ($session) {
            $summary = $this->summarizeStudent($student, $session->grade_table);
            return [
                'student' => $student,
                'average' => $summary['average'],
                'scholarship' => $summary['scholarship'],
                'insufficient' => $summary['insufficient'],
            ];
        });

        return [
            'items' => $items,
            'total' => $items->sum('scholarship'),
        ];
    }

    public function summarizeStudent(Student $student, array $gradeTable): array
    {
        $subjects = Subject::query()->pluck('category', 'name');
        $insufficient = 0;
        $sum = 0;
        $count = 0;

        foreach ($student->grades()->where('excluded', false)->get() as $grade) {
            $category = $subjects[$grade->subject_name] ?? 'VIMP';
            $min = $category === 'PROF' ? 5 : 4;
            $value = strtolower($grade->grade_value);

            if ($value === 'nv') {
                $insufficient++;
                continue;
            }

            if (!is_numeric($value)) {
                continue;
            }

            $num = (float) $value;
            if ($num < $min) {
                $insufficient++;
            }
            $sum += $num;
            $count++;
        }

        $average = $count > 0 ? round($sum / $count, 2) : null;
        $scholarship = 0.0;

        if ($insufficient === 1) {
            $scholarship = 15.0;
        } elseif ($insufficient === 0 && $average !== null) {
            foreach ($gradeTable as $row) {
                if ($average >= (float) $row['min'] && $average <= (float) $row['max']) {
                    $scholarship = (float) $row['amount'];
                    break;
                }
            }
        }

        return ['average' => $average, 'scholarship' => $scholarship, 'insufficient' => $insufficient];
    }

    public function groupMatrix(CalculationSession $session, $group = ""): Collection
    {
        $students = Student::query()->where('session_id', $session->id)->when($group, function ($query, $group) {
                return $query->where('group_name', $group);
            })->with('grades')->get()->groupBy('group_name');

        return $students->map(function (Collection $groupStudents) {
            $subjects = $groupStudents->flatMap(fn (Student $s) => $s->grades->pluck('subject_name'))->unique()->sort()->values();

            return [
                'students' => $groupStudents->sortBy('surname')->values(),
                'subjects' => $subjects,
            ];
        });
    }

    private function detectStudentColumns(array $idRow): array
    {
        $columns = [];

        foreach ($idRow as $index => $value) {
            $cell = trim((string) $value);
            if (preg_match('/^\d{6}-\d{5}$/', $cell) === 1) {
                $columns[] = (int) $index;
            }
        }

        return $columns;
    }
}
