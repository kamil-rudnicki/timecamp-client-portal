<?php

include 'vendor/autoload.php';

use League\CommonMark\CommonMarkConverter;
use Khill\Duration\Duration;

// CONFIGS
$apiKey = "";
$csvUrlPeriods = "https://docs.google.com/spreadsheets/d/e/2PACX-1vSYafaTTOHn-7P7wa7J2B_ZidP8En7UnsN3sfIbeaqgKhbjwlt8aqUeZvNvKKtviZXDjdldvkidUAWX/pub?gid=0&single=true&output=csv";
$csvUrlClients = "https://docs.google.com/spreadsheets/d/e/2PACX-1vSYafaTTOHn-7P7wa7J2B_ZidP8En7UnsN3sfIbeaqgKhbjwlt8aqUeZvNvKKtviZXDjdldvkidUAWX/pub?gid=476107891&single=true&output=csv";

$converter = new CommonMarkConverter();

function format_minutes($value)
{
    $hours = intval($value / 60);
    $minutes = $value % 60;
    $str = '';

    if ($hours != 0) {
        $str = $hours . 'h';

        // Handle pluralisation.
        if (abs($hours) != 1) {
            $str .= '';
        }
    }

    // Always show minutes if there are no hours.
    if ($minutes != 0 || $hours == 0) {
        $str .= ' ' . $minutes . 'm';

        // Handle pluralisation.
        if (abs($minutes) != 1) {
            $str .= '';
        }
    }

    // There will be a leading space if hours is zero.
    return trim($str);
}

function parseCsvFromUrl($url) {
    $csv = array();
    $csvData = file_get_contents($url);
    $fp = tmpfile();
    $header = null;
    fwrite($fp, $csvData);
    rewind($fp);
    while (($row = fgetcsv($fp, 0)) !== FALSE) {
        //$csv[] = $row;
        if(!$header)
            $header = $row;
        else
            $csv[] = array_combine($header, $row);
    }
    return $csv;
}

function getTimeEntriesFromTimeCamp($from, $to, $task_id, $api_key) {

    $curl = curl_init();

    curl_setopt_array($curl, array(
        CURLOPT_URL => "https://app.timecamp.com/third_party/api/entries?opt_fields=tags&from=$from&to=$to",
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'GET',
        CURLOPT_HTTPHEADER => array(
            'Accept: application/json',
            "Authorization: $api_key",
        ),
    ));

    $response = curl_exec($curl);

    curl_close($curl);

    $data = json_decode($response, true);

    //filter data by task_id
    return array_filter($data, function($item) use ($task_id) {
        return $item['task_id'] == $task_id;
    });
}

function generateReport($from, $clientId, $api_key, $csvArray) {
    $data = array();

    foreach($csvArray as $row) {
        if($row['KlientId'] === $clientId && ($from === null || ($from !== null && $row['Od'] === $from))) {
            $data = $row;
        }
    }


    $data['liczby'] = [];
    if(!empty($data['Liczba1'])) {
        $data['liczby'][] = explode(':', $data['Liczba1']);
    }
    if(!empty($data['Liczba2'])) {
        $data['liczby'][] = explode(':', $data['Liczba2']);
    }
    if(!empty($data['Liczba3'])) {
        $data['liczby'][] = explode(':', $data['Liczba3']);
    }

    $data['timeEntries'] = getTimeEntriesFromTimeCamp($data['Od'], $data['Do'], $data['KlientId'], $api_key);
    $data['timeEntriesTotal'] = 0;
    foreach($data['timeEntries'] as $entry) {
        $data['timeEntriesTotal'] += $entry['duration'];
    }
    //print_r($data);
    return $data;
}

function getLogo($clientId, $csvUrl) {
    $csvArray = parseCsvFromUrl($csvUrl);
    foreach($csvArray as $row) {
        if($row['KlientId'] === $clientId) {
            return $row['Logo'];
        }
    }
    return '';
}

function getClientId($pass, $csvUrl) {
    $csvArray = parseCsvFromUrl($csvUrl);
    foreach($csvArray as $row) {
        if($row['Pass'] === $pass) {
            return $row['KlientId'];
        }
    }
    return '';
}

function getDateOptions($clientId, $csvUrl) {
    $csvArray = parseCsvFromUrl($csvUrl);
    $dates = array();
    foreach($csvArray as $row) {
        if($row['KlientId'] === $clientId) {
            $dates[] = [
                'od' => $row['Od'],
                'nazwa' => $row['Nazwa'],
            ];
        }
    }
    return $dates;
}

$pass = $_GET['p'] ?? null;
$start = $_GET['s'] ?? null;
$clientId = getClientId($pass, "https://docs.google.com/spreadsheets/d/e/2PACX-1vRv5QSBMZmQRkwgW9WAj--RLdF5J9iMbTm89xfI5HleyKcpFTfCGLS9OX2uYVyXih0qcW8hih7QHj-_/pub?gid=476107891&single=true&output=csv");
if(empty($clientId)) {
    echo 'Invalid 404';
    exit;
}

$csvArray = parseCsvFromUrl($csvUrlPeriods);
$report = generateReport($start, $clientId, $apiKey, $csvArray);
$logoUrl = getLogo($clientId, $csvUrlClients);
$dateOptions = getDateOptions($clientId, $csvUrlPeriods);
$selectedDate = $start;
$currentUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]/?p=$pass";

?><!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Raport Klienta</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css"
          integrity="sha384-1BmE4kWBq78iYhFldvKuhfTAU6auU8tT94WrHftjDbrCEXSU1oBoqyl2QvZ6jIW3" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.5.0/font/bootstrap-icons.css">
    <style>
    </style>
</head>
<body style="background: rgb(245, 245, 245);">

<div class="col-lg-8 mx-auto p-3 py-md-5">
    <header class="d-flex align-items-center pb-3 mb-5 border-bottom">
        <span class="d-flex align-items-center text-dark text-decoration-none">
            <img src="<?= $logoUrl ?>" alt="logo" class="mr-3" style="max-width: 100px;">
        </span>
        <nav class="d-inline-flex mt-2 mt-md-0 ms-md-auto">
            <a href="https://itrix.pl"><img
                        src="https://cdn.bitrix24.pl/b20501713/landing/35f/35fc29a1d7b6c0e7d82127e23c242f21/Dodaj_nag_wek222_2x.png"
                        style="width: 80px;" /></a>
            <a class="py-2" href="#">Oceń nas</a>
        </nav>
    </header>

    <main>
        <div class="row g-3">
            <div class="col-sm-3">
                <select class="form-select col-md-4" onchange="this.options[this.selectedIndex].value && (window.location = this.options[this.selectedIndex].value);">
                    <?php foreach($dateOptions as $i => $dateOption): ?>
                        <option <?php if((!empty($selectedDate) && $dateOption['od'] === $selectedDate) || (empty($selectedDate) && $i === (count($dateOptions) - 1))) echo 'selected'; ?>
                                value="<?php echo $currentUrl."&s=".$dateOption['od'] ?>"><?php echo $dateOption['nazwa'] ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <br />

        <h1 class="mb-4">Raport za okres <?php echo date('d.m', strtotime($report['Od'])); ?>-<?php echo date('d.m.Y', strtotime($report['Do'])); ?></h1>
        <div class="row">
            <div class="col-md-6">
                <div class="card mb-3 shadow">
                    <div class="card-body">
                        <h4 class="card-title">Podsumowanie</h4>
                        <p class="card-text">
                            <?php echo $converter->convertToHtml($report["Podsumowanie"]); ?>
                        </p>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card mb-3 shadow">
                    <div class="card-body">
                        <h4 class="card-title">Plan na kolejny okres</h4>
                        <p class="card-text">
                            <?php echo $converter->convertToHtml($report["Plan"]); ?>
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <?php if(count($report["liczby"])) {?>
            <h2 class="mb-4 mt-4">Podsumowanie w liczbach</h2>
            <div class="row">
                <?php foreach ($report["liczby"] as $key => $value) { ?>
                    <div class="card text-bg-secondary mb-3 text-center me-3" style="max-width: 15rem;">
                        <div class="card-body">
                            <h5 class="card-title" style="color: #888;"><?php echo $value[0]; ?></h5>
                            <p class="card-text">
                            <h1 class="mb-0">
                                <?php echo $value[1]; ?>
                            </h1>
                            </p>
                        </div>
                    </div>
                <?php } ?>
            </div>
        <?php } ?>

        <h1 class="mt-5">Raport szczegółowy</h1>
        <h4 style="color: #888;" class="mt-0 mb-4">Suma godzin: <?php echo format_minutes($report['timeEntriesTotal']/60); ?></h4>
        <div class="card shadow">
            <table class="table table-sm">
                <thead>
                <tr>
                    <th scope="col">Dzień</th>
                    <th scope="col">Od-do</th>
                    <th scope="col">Czas</th>
                    <th scope="col">Notatka</th>
                    <th scope="col">Tagi</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($report["timeEntries"] as $key => $row) { ?>
                    <tr>
                        <th scope="row"><?php echo $row['date']; ?></th>
                        <td><?php echo substr($row['start_time'], 0, 5); ?>-<?php echo substr($row['end_time'], 0, 5); ?></td>
                        <td><?php

                            $duration = new Duration($row['duration']);
                            //echo $duration->humanize();
                            echo format_minutes($row['duration']/60);
                            //echo round($row['duration']/3600, 2); ?>
                        </td>
                        <td class="text-truncate" style="max-width: 140px;"><?php echo $row['description']; ?></td>
                        <td class="text-truncate" style="max-width: 140px;"><?php
                            if(isset($row['tags'])) {
                                foreach($row['tags'] as $tag) {
                                    echo '<span class="badge bg-secondary">'.$tag['name'].'</span> ';
                                }
                            }
                            ?></td>
                    </tr>
                <?php } ?>
                </tbody>
            </table>
        </div>

        <footer class="pt-5 my-5 text-muted border-top">
            Stworzone przy udziale <a href="https://www.timecamp.com">TimeCamp</a> · © 2022 &nbsp; &nbsp; &nbsp; <a
                    href="mailto:k.rudnicki@timecamp.com">Wyślij feedback</a>
        </footer>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>