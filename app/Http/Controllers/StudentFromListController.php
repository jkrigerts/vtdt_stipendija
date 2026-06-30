<?php

namespace App\Http\Controllers;

use App\Imports\StudentsFromListDataImport;
use App\Models\StudentFromList;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\DB;

class StudentFromListController extends Controller
{
    public function create() {
        $excludedStudents = DB::table('students')->where('excluded', true)->get();
        return view("scholarships.studentList", ['excludedStudents' => $excludedStudents]);
    }

    public function store(Request $request) {
        $request->validate([
            'students_file' => ['required', 'file', 'mimes:xlsx,xls'],
        ]);


        StudentFromList::query()->delete();

        Excel::import(new StudentsFromListDataImport, $request->file('students_file'));

        // 1. Mark students as excluded if they aren't in the student lists
        DB::statement('
            UPDATE students s
            LEFT JOIN student_from_lists l ON s.personal_id = l.personal_id
            SET s.excluded = TRUE
            WHERE l.personal_id IS NULL
        ');

        // 2. Mark current_group as false if their group name changed
        DB::statement('
            UPDATE students s
            LEFT JOIN student_from_lists l ON s.personal_id = l.personal_id
            SET s.current_group = FALSE
            WHERE s.group_name != l.group_name
        ');

        return redirect('/students/list/create')->with('status', 'Studentu fails veiksmīgi ielādēts.');
    }
}
