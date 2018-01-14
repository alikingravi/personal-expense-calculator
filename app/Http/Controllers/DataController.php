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

        // Get year and month from file path
        $explode = explode('/', $filePath);
        $year = $explode[3];
        $monthExplode = explode('.', $explode[4]);
        $month = $monthExplode[0];

        $csv = new CsvDataExtractor();

        $data = $csv->readCSV($filePath);

        $monthlyMemoData = $csv->formatMonthlyMemoData($data);

        $monthlyCategoryData = $csv->formatMonthlyCategoryData($data);

        $savings = $csv->calculateSavings($monthlyCategoryData);

        $expenses = Expense::create([
            'year' => $year,
            'month' => $month,
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

    public function getMonthlyData($year, $month)
    {
        $data = Expense::where('year', $year)
            ->where('month', $month)
            ->get();

        if (count($data) == 0) {
            return response()->json([
                'status' => '401',
                'message' => 'The data could not be found'
            ]);
        }

        return response()->json($data);
    }
}
