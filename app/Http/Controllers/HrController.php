<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Department;
use App\Models\Employee;
use App\Models\JobPosition;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class HrController extends Controller
{
    public function index(): View
    {
        $employees = Employee::with(['department', 'jobPosition'])->latest()->paginate(15);
        $departments = Department::with('jobPositions')->orderBy('name')->get();
        if ($departments->isEmpty()) {
            $eng = Department::create(['name' => 'Engineering', 'description' => 'Software & Hardware R&D']);
            JobPosition::create(['department_id' => $eng->id, 'title' => 'Software Engineer']);
            JobPosition::create(['department_id' => $eng->id, 'title' => 'Engineering Lead']);

            $ops = Department::create(['name' => 'Operations', 'description' => 'Logistics & Warehouse']);
            JobPosition::create(['department_id' => $ops->id, 'title' => 'Warehouse Manager']);
            $departments = Department::with('jobPositions')->orderBy('name')->get();
        }

        $positions = JobPosition::orderBy('title')->get();
        return view('hr.index', compact('employees', 'departments', 'positions'));
    }

    public function storeEmployee(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'first_name' => 'required|string|max:100',
            'last_name' => 'required|string|max:100',
            'email' => 'required|email|unique:employees,email',
            'phone' => 'nullable|string|max:50',
            'department_id' => 'required|exists:departments,id',
            'job_position_id' => 'required|exists:job_positions,id',
            'hire_date' => 'required|date',
            'salary' => 'required|numeric|min:0',
        ]);

        DB::transaction(function () use ($validated, $request) {
            $salaryCents = (int) round($validated['salary'] * 100);
            $emp = Employee::create([
                'first_name' => $validated['first_name'],
                'last_name' => $validated['last_name'],
                'email' => $validated['email'],
                'phone' => $validated['phone'] ?? null,
                'department_id' => $validated['department_id'],
                'job_position_id' => $validated['job_position_id'],
                'hire_date' => $validated['hire_date'],
                'salary_cents' => $salaryCents,
                'status' => 'active',
            ]);

            AuditLog::create([
                'user_id' => $request->user()->id,
                'action' => 'ADD_EMPLOYEE',
                'module' => 'HR',
                'description' => "Added employee {$emp->full_name} ({$emp->email})",
            ]);
        });

        return redirect()->route('hr.index')->with('success', 'Employee profile created successfully.');
    }
}
