<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class EmployeeController extends Controller
{
    public function index(Request $request)
    {
        $dir = strtolower((string) $request->query('direction', 'asc')) === 'desc' ? 'desc' : 'asc';
        $employees = Employee::query()
            ->orderBy('login', $dir)
            ->get();

        return view('admin.employees.index', [
            'employees' => $employees,
            'sortDirection' => $dir,
        ]);
    }

    public function bulkUpdate(Request $request)
    {
        $validated = $request->validate([
            'employees' => ['required', 'array'],
            'employees.*.grades' => ['nullable', 'string'],
            'employees.*.competencies' => ['nullable', 'string'],
            'employees.*.experience_achievements' => ['nullable', 'string'],
            'employees.*.ignored' => ['nullable', 'boolean'],
        ]);

        $rows = $validated['employees'];
        $ids = array_map('intval', array_keys($rows));
        $existingIds = Employee::query()->whereIn('id', $ids)->pluck('id')->all();
        $existingIds = array_flip($existingIds);

        DB::transaction(function () use ($rows, $existingIds) {
            foreach ($rows as $id => $row) {
                $id = (int) $id;
                if (! isset($existingIds[$id])) {
                    continue;
                }
                Employee::query()->whereKey($id)->update([
                    'grades' => $row['grades'] ?? null,
                    'competencies' => $row['competencies'] ?? null,
                    'experience_achievements' => $row['experience_achievements'] ?? null,
                    'ignored' => filter_var($row['ignored'] ?? false, FILTER_VALIDATE_BOOLEAN),
                ]);
            }
        });

        return redirect()->route('employees.index', array_filter([
            'direction' => $request->query('direction'),
        ]))->with('status', 'Сохранено.');
    }
}
