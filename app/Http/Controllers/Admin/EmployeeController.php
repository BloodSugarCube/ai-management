<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use Illuminate\Http\Request;

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

    public function update(Request $request, Employee $employee)
    {
        $data = $request->validate([
            'grades' => ['nullable', 'string'],
            'competencies' => ['nullable', 'string'],
            'experience_achievements' => ['nullable', 'string'],
        ]);

        $employee->update($data);

        return redirect()->route('employees.index', array_filter([
            'direction' => $request->query('direction'),
        ]))->with('status', 'Сохранено.');
    }
}
