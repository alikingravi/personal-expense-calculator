<?php

namespace App\Http\Controllers;

use App\Expense;
use App\Services\CsvDataExtractor;
use Illuminate\Http\Request;

class DataController extends Controller
{
    /**
     * Extracts data from the CSV file and stores it in the db
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function setMonthlyData()
    {
        $filePath = "../storage/app/2017/December.csv";

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

    /**
     * Gets the years and months available in the db
     *
     * @return \Illuminate\Http\JsonResponse
     */
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

    /**
     * Gets monthly data
     *
     * @param $year
     * @param $month
     * @return \Illuminate\Http\JsonResponse
     */
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

    /**
     * Gets yearly money-in, money-out and savings data
     *
     * @param $year
     * @return \Illuminate\Http\JsonResponse
     */
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

    /**
     * Gets data for yearly categories e.g bills, groceries etc
     *
     * @param $year
     * @return \Illuminate\Http\JsonResponse
     */
    public function getYearlyCategories($year)
    {
        $expenses = Expense::where('year', $year)->get();

        $info = [];
        foreach ($expenses as $expense) {
            $info[] = json_decode($expense->categories_data);
        }

        $bills = [];
        $groceries = [];
        $restaurants = [];
        foreach ($info as $value) {
            $bills[] = ltrim($value->Bills, '-');
            $groceries[] = ltrim($value->Groceries, '-');
            $restaurants[] = ltrim($value->Restaurants, '-');
        }
        $yearlyCategories['bills'] = $bills;
        $yearlyCategories['groceries'] = $groceries;
        $yearlyCategories['restaurants'] = $restaurants;

        return response()->json([
            'status' => '200',
            'message' => 'Data has been acquired successfully',
            'yearly_categories' => $yearlyCategories
        ]);
    }
}
