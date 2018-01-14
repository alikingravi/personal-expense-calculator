<?php
/**
 * Created by PhpStorm.
 * User: Kingravi
 * Date: 13/01/2018
 * Time: 17:54
 */

namespace App\Services;
use function MongoDB\BSON\toJSON;

/**
 * Class reads data from pre-cleaned .csv files and returns useful content
 *
 * @package App\Services
 */
class CsvDataExtractor
{

    /**
     * Reads a pre-cleaned .csv file and returns contents
     *
     * @param $filePath
     * @return array
     */
    public function readCSV($filePath)
    {
        $line_of_text = [];
        $file_handle = fopen($filePath, 'r');

        while (!feof($file_handle) ) {
            $line_of_text[] = fgetcsv($file_handle, 1024);
        }
        fclose($file_handle);

        return $line_of_text;
    }

    /**
     * Formats an array of data to return values of only unique memo keys
     *
     * @param $data
     * @return array
     */
    public function formatMonthlyMemoData($data)
    {
        // Remove the first element of array
        array_shift($data);

        // Find all unique keys
        $memos = $this->findAllMemos($data);
        sort($memos);

        // Add up all costs associated with each key
        $value = 0;
        $expenses = [];
        foreach ($memos as $memo) {
            foreach ($data as $item) {
                if ($item[5] === $memo) {
                    $expenses[$memo] = number_format($value += (float)$item[3], 2);
                }
            }
            $value = 0;
        }

        return json_encode($expenses);
    }

    /**
     * Filters through the data and returns a list of unique memo keys
     *
     * @param $data
     * @return array
     */
    public function findAllMemos($data)
    {
        $memos = [];
        foreach ($data as $item) {
            $memos[] = $item[5];
        }
        return array_unique(array_filter($memos));
    }

    /**
     * Formats an array of data to return values of only unique category keys
     *
     * @param $data
     * @return array
     */
    public function formatMonthlyCategoryData($data)
    {
        // Remove the first element of array
        array_shift($data);

        // Find all unique keys
        $categories = $this->findAllCategories($data);
        sort($categories);

        // Add up all the costs associated with each category
        $value = 0;
        $expenses = [];
        foreach ($categories as $category) {
            foreach ($data as $item) {
                if ($item[6] === $category) {
                    $expenses[$category] = number_format($value += (float)$item[3], 2);
                }
            }
            $value = 0;
        }

        $cleanExpenses = $this->cleanCommasInData($expenses);

        return json_encode($cleanExpenses);
    }

    /**
     * Cleans out commas in array values
     *
     * @param $data
     * @return array
     */
    public function cleanCommasInData($data)
    {
        $cleanedData = [];
        foreach ($data as $key => $item) {
            $cleanedData[$key] = str_replace(',', '', $item);
        }

        return $cleanedData;
    }

    /**
     * Filters through the data and returns a list of unique category keys
     *
     * @param $data
     * @return array
     */
    public function findAllCategories($data)
    {
        $categories = [];
        foreach ($data as $item) {
            $categories[] = $item[6];
        }

        return array_unique(array_filter($categories));
    }

    /**
     * Calculates money_in, money_out and savings data
     *
     * @param $monthlyCategoryData
     * @return array
     */
    public function calculateSavings($monthlyCategoryData)
    {
        $monthlyData = json_decode($monthlyCategoryData);
        $money_in = 0;
        $money_out = 0;
        $total = [];

        foreach ($monthlyData as $key => $value) {
            if ($key === "Income") {
                $money_in = $value;
            } else {
                $money_out += $value;
            }
        }
        $savings = number_format($money_in + $money_out, 2);

        $total['money_in'] = $money_in;
        $total['money_out'] = $money_out;
        $total['savings'] = $savings;

        $cleanTotal = $this->cleanCommasInData($total);

        return $cleanTotal;
    }
}
