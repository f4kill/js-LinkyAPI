<?php

require_once './Linky.php';
require_once './EnedisCredentials.php';
require_once './LinkyException.php';

use Linky\Linky;
use Linky\EnedisCredentials;
use Linky\LinkyException;

$enedisCredentials = new EnedisCredentials('mylogin', 'mypass');

try {
    $linky = new Linky($enedisCredentials);

    //$data = $linky->getAll();
    //$data = $linky->getHourlyData(new DateTime('2020-01-05'));
    $data = $linky->getDailyData(new DateTime('2020-01-01'), new DateTime('2020-01-11'));

    var_dump($data);
} catch (LinkyException $e) {
    echo $e->getMessage().PHP_EOL;

    exit;
}

//
$storage = './linkyLog.json';

if (file_exists($storage)) {
    $previousData = json_decode(file_get_contents($storage), true);
} else {
    $previousData = $data;
}

//_____Update current month:
$timezone = 'Europe/Paris';
$today = new \DateTime('NOW', new DateTimeZone($timezone));
$thisMonth = $today->format('M Y');

$updateTime             = $today->format('d/m/Y H:i:s');
$previousData['Update'] = $updateTime;

if ($data['months'][$thisMonth] != null) {
    $previousData['months'][$thisMonth] = $data['months'][$thisMonth];
}

//if first day of month, update previous month:
if ($today->format('d') == '01') {
    $prevMonth = clone $today;
    $prevMonth->sub(new DateInterval('P1M'));
    $prevMonth = $prevMonth->format('M Y');
    $previousData['months'][$prevMonth] = $data['months'][$prevMonth];
}

//_____Update current year:
$thisYear = new \DateTime();
$thisYear = $thisYear->format('Y');

if ($data['years'][$thisYear] != null) {
    $previousData['years'][$thisYear] = $data['years'][$thisYear];
}

//if first day of year, update previous year:
if ($today->format('d/m') == '01/01') {
    $prevYear = clone $today;
    $prevYear->sub(new \DateInterval('P1Y'));
    $prevYear                         = $prevYear->format('Y');
    $previousData['years'][$prevYear] = $data['years'][$prevYear];
}

//_____Does yesterday hours exists ?
$yesterday = clone $today;
$yesterday->sub(new \DateInterval('P1D'));
$yesterday = $yesterday->format('d/m/Y');
//avoid empty data:
if (!isset($previousData['hours'][$yesterday])) {
    $h = $data['hours'][$yesterday]["00:00"];
    if ($h != 'kW' and $h != '-2kW') {
        $previousData['hours'][$yesterday] = $data['hours'][$yesterday];
    }
}

//_____Add yesterday day:
if (!isset($previousData['days'][$yesterday])) {
    $previousData['days'][$yesterday] = $data['days'][$yesterday];
}


file_put_contents($storage, json_encode($previousData, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
