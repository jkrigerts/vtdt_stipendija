<?php

namespace App\Http\Controllers;

use App\Exports\ResultsExport;
use App\Models\CalculationSession;
use App\Models\GradeRecord;
use App\Models\Student;
use App\Models\Subject;
use App\Services\ScholarshipService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ScholarshipController extends Controller
{
    public function __construct(private readonly ScholarshipService $service)
    {
    }

    public function index()
    {
        $session = CalculationSession::latest()->first();

        return view('scholarships.setup', [
            'subjects' => Subject::orderBy('name')->get(),
            'session' => $session,
            'gradeTable' => $session?->grade_table ?? [
                ['min' => 4.0, 'max' => 4.99, 'amount' => 20],
                ['min' => 5.0, 'max' => 5.99, 'amount' => 25],
                ['min' => 6.0, 'max' => 6.99, 'amount' => 35],
                ['min' => 7.0, 'max' => 7.99, 'amount' => 50],
                ['min' => 8.0, 'max' => 8.99, 'amount' => 70],
                ['min' => 9.0, 'max' => 10.0, 'amount' => 90],
            ],
        ]);
    }

    public function uploadSubjects(Request $request): RedirectResponse
    {
        $request->validate([
            'subjects_file' => ['required', 'file', 'mimes:xlsx,xls'],
        ]);

        $this->service->importSubjects($request->file('subjects_file'));

        return redirect('/dashboard')->with('status', 'Priekšmetu fails veiksmīgi ielādēts.');
    }

    public function calculate(Request $request): RedirectResponse
    {
    
        $data = $request->validate([
            'period_start' => ['required', 'date'],
            'period_end' => ['required', 'date', 'after_or_equal:period_start'],
            'monthly_budget' => ['required', 'numeric', 'min:0'],
            'grades_file' => ['required', 'file', 'mimes:xlsx,xls'],
            'amounts' => ['required', 'array', 'size:6'],
            'amounts.*' => ['required', 'numeric', 'min:0'],
        ]);

        $gradeTable = [
            ['min' => 4.0, 'max' => 4.99, 'amount' => (float) $data['amounts'][0]],
            ['min' => 5.0, 'max' => 5.99, 'amount' => (float) $data['amounts'][1]],
            ['min' => 6.0, 'max' => 6.99, 'amount' => (float) $data['amounts'][2]],
            ['min' => 7.0, 'max' => 7.99, 'amount' => (float) $data['amounts'][3]],
            ['min' => 8.0, 'max' => 8.99, 'amount' => (float) $data['amounts'][4]],
            ['min' => 9.0, 'max' => 10.0, 'amount' => (float) $data['amounts'][5]],
        ];

        $session = $this->service->calculate([
            'period_start' => $data['period_start'],
            'period_end' => $data['period_end'],
            'monthly_budget' => $data['monthly_budget'],
            'grade_table' => $gradeTable,
        ], $request->file('grades_file'));

        return redirect('/results?session='.$session->id);
    }

    public function results(Request $request)
    {
        $sessionId = $request->integer('session') ?: CalculationSession::max('id');
        if (!$sessionId) {
            return Redirect::to('/dashboard')->with('status', 'Vispirms augšupielādē failus un palaid aprēķinu.');
        }

        $session = CalculationSession::find($sessionId);
        if (!$session) {
            return Redirect::to('/dashboard')->with('status', 'Aprēķina sesija netika atrasta. Lūdzu, aprēķini vēlreiz.');
        }

        $results = $this->service->buildResults($session);

        return view('scholarships.results', [
            'session' => $session,
            'results' => $results,
            'groups' => $this->service->groupMatrix($session),
        ]);
    }

    public function group(Request $request)
    {
        $sessionId = $request->integer('session') ?: CalculationSession::max('id');
        $group_name = $request->group_name ?? "";
        if (!$sessionId) {
            return Redirect::to('/dashboard')->with('status', 'Vispirms augšupielādē failus un palaid aprēķinu.');
        }

        $session = CalculationSession::find($sessionId);
        if (!$session) {
            return Redirect::to('/dashboard')->with('status', 'Aprēķina sesija netika atrasta. Lūdzu, aprēķini vēlreiz.');
        }


        $results = $this->service->buildResults($session, $group_name);

        return view('scholarships.group', [
            'session' => $session,
            'results' => $results,
            'groups' => $this->service->groupMatrix($session, $group_name),
        ]);
    }

    public function export(Request $request): BinaryFileResponse|RedirectResponse
    {
        $sessionId = $request->integer('session') ?: CalculationSession::max('id');
        if (!$sessionId) {
            return Redirect::to('/dashboard')->with('status', 'Nav ko eksportēt, jo vēl nav rezultātu.');
        }

        $session = CalculationSession::find($sessionId);
        if (!$session) {
            return Redirect::to('/dashboard')->with('status', 'Aprēķina sesija netika atrasta. Lūdzu, aprēķini vēlreiz.');
        }

        $results = $this->service->buildResults($session);

        return Excel::download(new ResultsExport($results['items']), 'scholarship_results.xlsx');
    }

    public function excludeGrade(Request $request)
    {
        $data = $request->validate([
            'grade_record_id' => ['required', 'exists:grade_records,id'],
            'excluded' => ['required', 'boolean'],
        ]);

        $grade = GradeRecord::findOrFail($data['grade_record_id']);
        $grade->update(['excluded' => (bool) $data['excluded']]);

        $student = Student::with('grades')->findOrFail($grade->student_id);
        $session = $student->session;
        $summary = $this->service->summarizeStudent($student, $student->session->grade_table);
        $groupName = $student->group_name;
        $results = $this->service->buildResults($session, $groupName);
        $total = (float) $results['total'];
        $budget = (float) $session->monthly_budget;

        return response()->json([
            'average' => $summary['average'],
            'scholarship' => number_format($summary['scholarship'], 2, '.', ''),
            'total' => number_format($total, 2, '.', ''),
            'difference' => number_format($budget - $total, 2, '.', ''),
            'difference_positive' => $budget - $total >= 0,
            'saved_message' => 'Izmaiņas saglabātas.',
        ]);
    }
}
