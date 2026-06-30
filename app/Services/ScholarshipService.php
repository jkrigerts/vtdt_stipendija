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
        
            ini_set('memory_limit', '2048M'); 
            set_time_limit(300);
            
            $sheets = Excel::toArray([], $file);
    
            return DB::transaction(function () use ($data, $sheets) {
                GradeRecord::query()->delete();
                Student::query()->delete();
                CalculationSession::query()->delete();

                $session = CalculationSession::create([
                    'period_start' => $data['period_start'],
                    'period_end' => $data['period_end'],
                    'monthly_budget' => $data['monthly_budget'],
                    'grade_table' => $data['grade_table'],
                ]);

            $subjects = Subject::query()->pluck('category', 'name');

            foreach ($sheets as $sheet) {
                $groupName = trim((string) ($sheet[0][0] ?? ''));
                if ($groupName === '' || in_array($groupName, self::IGNORE_SHEETS, true)) {
                    continue;
                }

                $surnames = $sheet[1] ?? [];
                $firstNames = $sheet[2] ?? [];
                $ids = $sheet[3] ?? [];

                $students = [];
                foreach ($this->detectStudentColumns($ids) as $col) {
                    $surname = trim((string) ($surnames[$col] ?? ''));
                    $firstName = trim((string) ($firstNames[$col] ?? ''));
                    $personalId = trim((string) ($ids[$col] ?? ''));

                    if ($personalId === '' || ($surname === '' && $firstName === '')) {
                        continue;
                    }

                    $student = Student::create([
                        'session_id' => $session->id,
                        'surname' => $surname,
                        'first_name' => $firstName,
                        'personal_id' => $personalId,
                        'group_name' => $groupName,
                    ]);

                    $students[$col] = $student;
                }

                $subjectValues = [];
                foreach (array_slice($sheet, 4) as $row) {
                    if (trim((string) ($row[0] ?? '')) !== 'Žurnāls') {
                        continue;
                    }

                    $subject = trim((string) ($row[1] ?? ''));
                    $gradeType = trim((string) ($row[4] ?? ''));
                    if (!in_array($gradeType, ['I semestra vērtējums', 'II semestra vērtējums', 'Galīgais vērtējums priekšmetā'], true)) {
                        continue;
                    }

                    $dateRaw = trim((string) ($row[3] ?? ''));
                    if ($dateRaw === '') {
                        continue;
                    }

                    $date = Carbon::createFromFormat('d.m.Y', $dateRaw);
                    if ($date->lt(Carbon::parse($data['period_start'])) || $date->gt(Carbon::parse($data['period_end']))) {
                        continue;
                    }

                    foreach ($students as $col => $student) {
                        $gradeValue = strtolower(trim((string) ($row[$col] ?? '')));
                        if ($gradeValue === '' || $gradeValue === 'n' || str_contains($gradeValue, '%')) {
                            continue;
                        }

                        $priority = $gradeType === 'Galīgais vērtējums priekšmetā' ? 2 : 1;
                        $key = $student->id . '|' . $subject;

                        if (!isset($subjectValues[$key]) || $priority > $subjectValues[$key]['priority']) {
                            $subjectValues[$key] = [
                                'student_id' => $student->id,
                                'subject_name' => $subject,
                                'grade_type' => $gradeType,
                                'grade_value' => $gradeValue,
                                'grade_date' => $date->toDateString(),
                                'priority' => $priority,
                                'category' => $subjects[$subject] ?? 'VIMP',
                            ];
                        }
                    }
                }

                foreach ($subjectValues as $value) {
                    GradeRecord::create([
                        'student_id' => $value['student_id'],
                        'subject_name' => $value['subject_name'],
                        'grade_type' => $value['grade_type'],
                        'grade_value' => $value['grade_value'],
                        'grade_date' => $value['grade_date'],
                    ]);
                }
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
                'nv' => $summary['nv'],
                'noGrade' => $summary['noGrade'],
            ];
        });

        return [
            'items' => $items,
            'total' => $items->reject(function ($item) {
                                         return $item['student']->excluded || !$item['student']->current_group;
                                      })
            ->sum('scholarship'),
        ];
    }

    public function summarizeStudent(Student $student, array $gradeTable): array
    {
        $subjects = Subject::query()->pluck('category', 'name');
        $insufficient = 0;
        $nv = 0;
        $noGrade = 0;
        $sum = 0;
        $count = 0;

        foreach ($student->grades()->where('excluded', false)->get() as $grade) {
            $category = $subjects[$grade->subject_name] ?? 'VIMP';
            $min = $category === 'PROF' ? 5 : 4;
            $value = strtolower($grade->grade_value);

            if ($value === 'nv' || $value === 'na') {
                $nv++;
                continue;
            }

            if (preg_replace('/^\s+|\s+$/u', '', $value) == '') {
                $noGrade++;
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

        if ($insufficient + $nv === 1) {
            $scholarship = 15.0;
        } elseif ($insufficient + $nv === 0 && $average !== null) {
            foreach ($gradeTable as $row) {
                if ($average >= (float) $row['min'] && $average <= (float) $row['max']) {
                    $scholarship = (float) $row['amount'];
                    break;
                }
            }
        }

        return ['average' => $average, 'scholarship' => $scholarship, 'insufficient' => $insufficient, 'nv' => $nv, 'noGrade' => $noGrade];
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
