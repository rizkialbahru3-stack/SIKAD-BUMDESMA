<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\Position;
use App\Models\User;
use App\Models\WorkSchedule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class EmployeeController extends Controller
{
    public function index(Request $request)
    {
        $employees = Employee::withTrashed()->with('user', 'position', 'workSchedule')
            ->when($request->input('search'), fn ($query, $search) => $query->where(function ($query) use ($search) {
                $query->where('employee_code', 'like', '%'.$search.'%')->orWhere('name', 'like', '%'.$search.'%')->orWhereHas('user', fn ($userQuery) => $userQuery->where('name', 'like', '%'.$search.'%'));
            }))
            ->when($request->filled('position_id'), fn ($query) => $query->where('position_id', $request->integer('position_id')))
            ->when($request->filled('status'), fn ($query) => $query->where('is_active', $request->boolean('status')))
            ->latest()
            ->paginate(15);

        return view('employees.index', ['employees' => $employees, 'positions' => Position::orderBy('name')->get(), 'filters' => $request->only('search', 'status', 'position_id')]);
    }

    public function create()
    {
        return view('employees.form', ['employee' => new Employee, 'positions' => Position::orderBy('name')->get(), 'schedules' => WorkSchedule::where('is_active', true)->orderBy('name')->get()]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'employee_code' => ['nullable', 'string', 'max:30', Rule::unique('employees', 'employee_code')],
            'position_id' => ['required', 'exists:positions,id'],
            'work_schedule_id' => ['nullable', 'exists:work_schedules,id'],
            'phone' => ['nullable', 'string', 'max:30'],
            'joined_at' => ['required', 'date'],
            'basic_salary' => ['nullable', 'numeric', 'min:0'],
            'attendance_allowance' => ['nullable', 'numeric', 'min:0'],
            'photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'is_active' => ['sometimes', 'boolean'],
            'create_account' => ['nullable', 'boolean'],
            'remove_photo' => ['nullable', 'boolean'],
            'email' => [Rule::requiredIf($request->boolean('create_account')), 'nullable', 'email', 'max:150', Rule::unique('users', 'email')],
            'password' => [Rule::requiredIf($request->boolean('create_account')), 'nullable', 'string', 'min:8', 'confirmed'],
        ]);
        DB::transaction(function () use ($data, $request) {
            $user = null;
            if ($request->boolean('create_account')) {
                $user = User::create(['name' => $data['name'], 'email' => $data['email'], 'role' => 'employee', 'password' => Hash::make($data['password'])]);
            }
            $employee = Employee::create($this->employeeData($data) + ['user_id' => $user?->id, 'name' => $data['name'], 'email' => $user?->email]);
            $this->storePhoto($request, $employee);
        });

        return redirect()->route('employees.index')->with('success', 'Data karyawan berhasil ditambahkan.');
    }

    public function edit(Employee $employee)
    {
        return view('employees.form', ['employee' => $employee->load('user'), 'positions' => Position::orderBy('name')->get(), 'schedules' => WorkSchedule::where('is_active', true)->orderBy('name')->get()]);
    }

    public function show(Employee $employee)
    {
        return view('employees.show', ['employee' => $employee->load('user', 'position', 'workSchedule')]);
    }

    public function myProfile(Request $request)
    {
        $employee = $request->user()->employee;
        abort_unless($employee, 404, 'Data karyawan belum terhubung ke akun ini.');

        return view('employees.show', ['employee' => $employee->load('user', 'position', 'workSchedule')]);
    }

    public function update(Request $request, Employee $employee)
    {
        $data = $this->validated($request, $employee);
        $data['is_active'] = $request->boolean('is_active');
        if (empty($data['employee_code'])) {
            $data['employee_code'] = $employee->employee_code;
        }
        $accountData = null;
        if (! $employee->user && $request->boolean('create_account')) {
            $accountData = $request->validate([
                'email' => ['required', 'email', 'max:150', Rule::unique('users', 'email')],
                'password' => ['required', 'string', 'min:8', 'confirmed'],
            ]);
            $data['password'] = null;
        }
        DB::transaction(function () use ($data, $employee, $accountData) {
            if ($accountData) {
                $user = User::create(['name' => $data['name'], 'email' => $accountData['email'], 'role' => 'employee', 'password' => Hash::make($accountData['password'])]);
                $employee->update(['user_id' => $user->id]);
                $data['email'] = $accountData['email'];
            }
            $employee->update($this->employeeData($data) + ['name' => $data['name'], 'email' => $data['email'] ?? null]);
            if ($employee->user) {
                $employee->user->update(['name' => $data['name']]);
                if (! empty($data['email'])) {
                    $employee->user->update(['email' => $data['email']]);
                }
                if (! empty($data['password'])) {
                    $employee->user->update(['password' => Hash::make($data['password'])]);
                }
            }
        });
        if ($request->boolean('remove_photo') && ! $request->hasFile('photo') && $employee->photo) {
            Storage::disk('public')->delete($employee->photo);
            $employee->update(['photo' => null]);
        }
        $this->storePhoto($request, $employee);

        return redirect()->route('employees.index')->with('success', 'Data karyawan berhasil diperbarui.');
    }

    public function storeAccount(Request $request, Employee $employee)
    {
        abort_if($employee->user_id, 422, 'Karyawan sudah memiliki akun login.');

        $data = $request->validate([
            'email' => ['required', 'email', 'max:150', Rule::unique('users', 'email')],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        DB::transaction(function () use ($data, $employee) {
            $user = User::create([
                'name' => $employee->name ?: $employee->display_name,
                'email' => $data['email'],
                'role' => 'employee',
                'password' => Hash::make($data['password']),
            ]);
            $employee->update(['user_id' => $user->id, 'email' => $data['email']]);
        });

        return back()->with('success', 'Akun login berhasil dibuat dan terhubung ke '.$employee->display_name.'.');
    }

    public function destroy(Employee $employee)
    {
        $employee->update(['is_active' => false]);
        $employee->delete();

        return back()->with('success', 'Karyawan berhasil dinonaktifkan.');
    }

    private function validated(Request $request, ?Employee $employee = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'email' => ['nullable', 'email', 'max:150', Rule::unique('users', 'email')->ignore($employee?->user_id)],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
            'create_account' => ['nullable', 'boolean'],
            'remove_photo' => ['nullable', 'boolean'],
            'employee_code' => [$employee ? 'sometimes' : 'nullable', 'string', 'max:30', Rule::unique('employees', 'employee_code')->ignore($employee?->id)],
            'position_id' => ['required', 'exists:positions,id'],
            'work_schedule_id' => ['nullable', 'exists:work_schedules,id'],
            'phone' => ['nullable', 'string', 'max:30'],
            'joined_at' => ['required', 'date'],
            'basic_salary' => ['nullable', 'numeric', 'min:0'],
            'attendance_allowance' => ['nullable', 'numeric', 'min:0'],
            'photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'is_active' => ['sometimes', 'boolean'],
        ]);
    }

    private function employeeData(array $data): array
    {
        $employeeCode = $data['employee_code'] ?? null;
        if (! $employeeCode) {
            $nextId = (Employee::withTrashed()->max('id') ?? 0) + 1;
            do {
                $employeeCode = 'KRY-'.str_pad((string) $nextId++, 3, '0', STR_PAD_LEFT);
            } while (Employee::withTrashed()->where('employee_code', $employeeCode)->exists());
        }

        return collect($data)->only(['position_id', 'work_schedule_id', 'phone', 'joined_at', 'basic_salary', 'attendance_allowance', 'is_active'])->toArray() + ['employee_code' => $employeeCode, 'is_active' => true];
    }

    private function storePhoto(Request $request, Employee $employee): void
    {
        if (! $request->hasFile('photo')) {
            return;
        }
        if ($employee->photo) {
            Storage::disk('public')->delete($employee->photo);
        }
        $employee->update(['photo' => $request->file('photo')->store('employee-photos', 'public')]);
    }
}
