<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class ResultsExport implements FromCollection, WithHeadings
{
    public function __construct(private readonly Collection $rows)
    {
    }

    public function collection(): Collection
    {
        return $this->rows->map(fn ($row) => [
            $row['student']->surname,
            $row['student']->first_name,
            $row['student']->personal_id,
            $row['student']->group_name,
            $row['average'] ?? '-',
            number_format($row['scholarship'], 2, '.', ''),
        ]);
    }

    public function headings(): array
    {
        return ['Uzvārds', 'Vārds', 'Personas kods', 'Grupa', 'Vidējais vērtējums', 'Stipendija (EUR)'];
    }
}
