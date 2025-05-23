<?php

namespace App\Http\Controllers;

use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index() {
        $user = request()->user();

        // Dashboard stats
        $startOfMonth = now()->startOfMonth()->toDateString();
        $endOfMonth = now()->endOfMonth()->toDateString();

        $totalExpenses = $user->expenses->sum('amount');
        $monthlyExpenses = $user->expenses->whereBetween('date', [$startOfMonth, $endOfMonth])->sum('amount');
        $categoriesUsed = $user->categories->count();

        // Category wise expenses
        $expensesPerCategory = $user->categories()->withSum('expenses', 'amount')->get();

        // Recent expenses
        $recentExpenses = $user->expenses()->with('category:id,name')->latest()->limit(5)->get();

        return $this->successResponse([
            'totalExpenses' => $totalExpenses,
            'monthlyExpenses' => $monthlyExpenses,
            'categoriesUsed' => $categoriesUsed,
            'expensesPerCategory' => $expensesPerCategory,
            'recentExpenses' => $recentExpenses,
        ], 'Records retrieved successfully.');
    }
}
