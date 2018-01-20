<?php

namespace App\Http\Controllers;

use App\Expense;
use App\Services\CsvDataExtractor;
use Illuminate\Http\Request;

class DataController extends Controller
{
    public function setMonthlyData()
    {
        $filePath = "../storage/app/2017/February.csv";

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

    public function getYearsAndMonths()
    {
        $expenses = Expense::all();

        if (count($expenses) == 0) {
            return response()->json([
                'status' => '401',
                'message' => 'The data could not be found'
            ]);
        }

        $info = [];
        $allYears = [];
        $yearsMonths = [];

        // Find all available years
        foreach ($expenses as $expense) {
            $allYears[] = $expense->year;
        }
        $years = array_unique($allYears);

        foreach ($years as $year) {
            foreach ($expenses as $expense) {
                if ($expense->year === $year) {
                    $info[] = $expense->month;
                }
            }
            $yearsMonths[$year] = $info;
            $info = [];
        }

        return response()->json([
            'status' => '200',
            'message' => 'Years and months have been acquired',
            'years_months' => $yearsMonths
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

        return response()->json([
            'status' => '200',
            'message' => 'Data has been acquired successfully',
            'all_data' => $data
        ]);
    }

    public function getYearlyData($year)
    {
        $expenses = Expense::where('year', $year)->get();

        if (count($expenses) == 0) {
            return response()->json([
                'status' => '401',
                'message' => 'The data could not be found'
            ]);
        }

        // Get all months
        $allMonths = [];
        foreach ($expenses as $expense) {
            $allMonths[] = $expense->month;
        }

        $info = [];
        $yearlyInfo = [];
        $savings = [];
        foreach ($allMonths as $month) {
            foreach ($expenses as $expense) {
                if ($expense->month === $month) {
                    $info['year'] = $expense->year;
                    $info['month'] = $expense->month;
                    $info['money_in'] = $expense->money_in;
                    $info['money_out'] = $expense->money_out;
                    $info['savings'] = $expense->savings;

                    $savings[$month] = $expense->savings;
                }
            }
            $yearlyInfo[$month] = $info;
            $info = [];
        }

        // Total Savings for the year
        $total = '0';
        foreach ($savings as $saving) {
            $total = bcadd($total, $saving, 2);
        }
        $savings['total'] = $total;

        return response()->json([
            'status' => '200',
            'message' => 'Data has been acquired successfully',
            'yearly_info' => $yearlyInfo,
            'savings' => $savings
        ]);
    }
}
