<?php

namespace App\Http\Controllers;

use App\Models\Expense;
use App\Http\Requests\StoreExpenseRequest;
use App\Http\Requests\UpdateExpenseRequest;
use App\Models\Category;

class ExpenseController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $expenses = $this->getExpenseRecords();

        return $this->successResponse([
            'expenses' => $expenses
        ], 'Records retrieved successfully.');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreExpenseRequest $request)
    {
        // Find or add category if provided in request
        $category = null;

        if ($request->category) {
            $category = Category::firstOrCreate([
                'name' => $request->category,
                'user_id' => $request->user()->id,
            ]);
        }

        if ($category) {
            // Add expense with category
            Expense::create([
                'user_id' => $request->user()->id,
                'category_id' => $category->id,
                'amount' => $request->amount,
                'date' => $request->date,
                'note' => $request->note,
            ]);
        }

        else {
            // Add expense without category
            Expense::create([
                'user_id' => $request->user()->id,
                'amount' => $request->amount,
                'date' => $request->date,
                'note' => $request->note,
            ]);
        }

        // return successful response
        $expenses = $this->getExpenseRecords();

        return $this->successResponse([
            'expenses' => $expenses
        ], 'Record added successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Expense $expense)
    {
        return $this->successResponse([
          'expense' => $expense->load('category:id,name')
        ], 'Record retrieved successfully.');
    }


    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateExpenseRequest $request, Expense $expense)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Expense $expense)
    {
        //
    }

    private function getExpenseRecords() 
    {
        $expenses = Expense::with('category:id,name')->where('user_id', request()->user()->id)->latest()->paginate(10);
        return $expenses;
    }
}