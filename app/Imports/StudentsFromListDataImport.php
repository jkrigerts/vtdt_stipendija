<?php

namespace App\Imports;

use App\Models\StudentFromList;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithStartRow;

class StudentsFromListDataImport implements ToModel, WithStartRow
{
    public function startRow(): int
    {
            return 8;
    }

    public function model(array $row)
    {
        // 1. Skip the footer row at the bottom ("Sagatavoja:")
        if (str_contains($row[0] ?? '', 'Sagatavoja')) {
            return null;
        }

        // 2. Skip completely empty rows
        if (empty($row[0])) {
            return null; 
        }
        // 2. The Ultimate Safety Net: No personal code = Not a student row = Skip it.
        if (empty($row[2])) {
            return null; 
        }

        // 3. Skip completely empty rows based on the name column
        if (empty($row[3])) {
            return null; 
        }

        $cleanName = trim(preg_replace('/\s+/', ' ', $row[2]));
        $parts = explode(' ', $cleanName);

        $surname = array_shift($parts);
        $firstAndMiddleNames = implode(' ', $parts);
        var_dump($row);
        // 3. Return a new Model instance with the mapped data
        return new StudentFromList([
            // DB Column Name   => Excel Column Index (Based on your screenshot)
            'surname'        => $surname, // Column A
            'first_name'         => $firstAndMiddleNames, // Column C
            'personal_id'     => $row[3], // Column D
            'group_name'     => $row[0], // Column E
        ]);
    }
}
