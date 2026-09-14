<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\Punishment;
use App\Models\Reward;
use Illuminate\Http\Request;

class RecognitionController extends Controller
{
    public function index(Request $request)
    {
        $employeeId = $request->user()->isAdmin() ? $request->integer('employee_id') : $request->user()->employee?->id;
        $employees = $request->user()->isAdmin() ? Employee::with('user')->where('is_active', true)->orderBy('employee_code')->get() : collect();

        return view('recognition.index', [
            'rewards' => Reward::with('employee.user')->when($employeeId, fn ($query) => $query->where('employee_id', $employeeId))->latest('awarded_at')->paginate(10, ['*'], 'rewards_page'),
            'punishments' => Punishment::with('employee.user')->when($employeeId, fn ($query) => $query->where('employee_id', $employeeId))->latest('issued_at')->paginate(10, ['*'], 'punishments_page'),
            'employees' => $employees,
            'selectedEmployee' => $employeeId,
        ]);
    }

    public function storeReward(Request $request)
    {
        $data = $request->validate([
            'employee_id' => ['required', 'exists:employees,id'],
            'type' => ['required', 'in:bonus,points'],
            'amount' => ['nullable', 'numeric', 'min:0'],
            'points' => ['nullable', 'integer', 'min:0'],
            'title' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:2000'],
            'awarded_at' => ['required', 'date'],
        ]);
        // Kolom amount/points NOT NULL: nol-kan field yang tidak relevan dengan tipe.
        if ($data['type'] === 'points') {
            $data['amount'] = 0;
            $data['points'] ??= 0;
        } else {
            $data['points'] = 0;
            $data['amount'] ??= 0;
        }
        Reward::create($data);

        return back()->with('success', 'Reward berhasil ditambahkan.');
    }

    public function storePunishment(Request $request)
    {
        $data = $request->validate([
            'employee_id' => ['required', 'exists:employees,id'],
            'type' => ['required', 'in:warning,points_deduction,salary_deduction,administrative'],
            'amount' => ['nullable', 'numeric', 'min:0'],
            'title' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:2000'],
            'issued_at' => ['required', 'date'],
        ]);
        // Kolom amount NOT NULL: punishment tanpa nominal (mis. peringatan) disimpan 0.
        $data['amount'] ??= 0;
        Punishment::create($data);

        return back()->with('success', 'Punishment berhasil ditambahkan.');
    }

    public function destroyReward(Request $request, Reward $reward)
    {
        abort_unless($request->user()->isAdmin(), 403);
        $reward->delete();

        return back()->with('success', 'Reward berhasil dihapus.');
    }

    public function destroyPunishment(Request $request, Punishment $punishment)
    {
        abort_unless($request->user()->isAdmin(), 403);
        $punishment->delete();

        return back()->with('success', 'Punishment berhasil dihapus.');
    }
}
