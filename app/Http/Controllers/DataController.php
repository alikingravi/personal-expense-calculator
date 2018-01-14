<?php

namespace App\Http\Controllers;

use App\Expense;
use App\Services\CsvDataExtractor;
use Illuminate\Http\Request;

class DataController extends Controller
{
    public function setMonthlyData()
    {
        $filePath = "../storage/app/2017/January.csv";

        $csv = new CsvDataExtractor();

        $data = $csv->readCSV($filePath);

        $monthlyMemoData = $csv->formatMonthlyMemoData($data);

        $monthlyCategoryData = $csv->formatMonthlyCategoryData($data);

        $savings = $csv->calculateSavings($monthlyCategoryData);

        $expenses = Expense::create([
            'year' => '2017',
            'month' => 'January',
            'monthly_data' => $monthlyMemoData,
            'categories_data' => $monthlyCategoryData,
            'money_in' => $savings['money_in'],
            'money_out' => $savings['money_out'],
            'savings' => $savings['savings']
        ]);

        return response()->json([
            'status' => 200,
            'message' => 'Data added successfully',
            'data' => $expenses
        ]);
    }
}
