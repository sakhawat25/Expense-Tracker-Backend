<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportsController extends Controller
{
    public function index()
    {
        $user = request()->user();
        $year = now()->year;

        $startDate = Carbon::create($year, 1)->startOfMonth();
        $endDate = Carbon::create($year, 12)->endOfMonth();

        // Monthly expenses (including zero months)
        $monthlyTotals = DB::table('expenses')
            ->selectRaw('MONTH(date) as month, SUM(amount) as total')
            ->where('user_id', $user->id)
            ->whereYear('date', $year)
            ->groupBy(DB::raw('MONTH(date)'))
            ->pluck('total', 'month');

        $expensesPerMonth = collect(range(1, 12))->map(function ($month) use ($monthlyTotals, $year) {
            $label = Carbon::create($year, $month, 1)->format('M Y');
            return [$label => number_format($monthlyTotals->get($month, 0), 2)];
        })->values();


        // Category-wise expenses
        $expensesPerCategory = $user->categories()
            ->withSum(['expenses' => fn($query) => $query->whereYear('date', $year)], 'amount')
            ->get();

        // All expenses for the year
        $wholeYearExpenses = $user->expenses()
            ->with('category:id,name')
            ->whereBetween('date', [$startDate->toDateString(), $endDate->toDateString()])
            ->orderBy('date', 'DESC')
            ->get();

        return $this->successResponse([
            'expensesPerMonth' => $expensesPerMonth,
            'expensesPerCategory' => $expensesPerCategory,
            'wholeYearExpenses' => $wholeYearExpenses,
        ], 'Records retrieved successfully.');
    }


    public function filter(Request $request)
    {
        $request->validate([
            'from' => 'required|date|date_format:Y-m-d|before_or_equal:to',
            'to' => 'required|date|date_format:Y-m-d|after_or_equal:from',
        ]);

        $user = $request->user();
        $startDate = Carbon::createFromFormat('Y-m-d', $request->from);
        $endDate = Carbon::createFromFormat('Y-m-d', $request->to);


        // Monthly totals
        $monthlyTotals = DB::table('expenses')
            ->selectRaw("YEAR(date) as year, MONTH(date) as month, SUM(amount) as total")
            ->where('user_id', $user->id)
            ->whereBetween('date', [$startDate, $endDate])
            ->groupByRaw("YEAR(date), MONTH(date)")
            ->orderByRaw("YEAR(date), MONTH(date)")
            ->get()
            ->mapWithKeys(function ($row) {
                $key = Carbon::create($row->year, $row->month)->format('Y-m');
                return [$key => number_format($row->total, 2)];
            });

        // Fill missing months
        $expensesPerMonth = [];
        $current = $startDate->copy();
        while ($current <= $endDate) {
            $key = $current->format('Y-m');
            $label = $current->format('M Y');
            $expensesPerMonth[] = [$label => $monthlyTotals->get($key, "0.00")];
            $current->addMonth();
        }

        // Category-wise expenses
        $expensesPerCategory = $user->categories()
            ->withSum(['expenses' => fn($query) => $query->whereBetween('date', [$startDate, $endDate])], 'amount')
            ->get();

        // Expenses in selected range
        $expensesListInRange = $user->expenses()
            ->with('category:id,name')
            ->whereBetween('date', [$startDate, $endDate])
            ->orderBy('date', 'DESC')
            ->get();

        return $this->successResponse([
            'expensesPerMonth' => $expensesPerMonth,
            'expensesPerCategory' => $expensesPerCategory,
            'wholeYearExpenses' => $expensesListInRange,
        ], 'Records retrieved successfully.');
    }
}
