<?php

require './parsecsv-for-php/parsecsv.lib.php';
$csv_file = $_FILES;
foreach($csv_file as $csv_data){
    // print("<pre>".print_r($csv_data,true)."</pre>");
    $csvFileName = $csv_data["name"];
    $file = fopen($csvFileName, 'r');
    $file_data = fgetcsv($file);
    $fp = file($csvFileName);

    $csv = new \ParseCsv\Csv();
    $csv->auto($csvFileName);
    $csv_file_data = $csv->titles;
    echo json_encode($csv_file_data);
}