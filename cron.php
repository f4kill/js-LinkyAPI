<?php

require_once './Linky.php';
require_once './EnedisCredentials.php';
require_once './LinkyException.php';
require_once './DataHelper.php';

use Linky\EnedisCredentials;
use Linky\Linky;
use Linky\LinkyException;
use Linky\DataHelper;

/*
 * Configuration
 */

$storage = './linky-data.json';

$enedisCredentials = new EnedisCredentials('mylogin', 'mypass');

/*
 * Main
 */

$data = array(
    Linky::DATASET_HOURLY  => null,
    Linky::DATASET_DAILY   => null,
    Linky::DATASET_MONTHLY => null,
    Linky::DATASET_YEARLY  => null,
);

if (file_exists($storage)) {
    $data = json_decode(file_get_contents($storage), true);
}

try {
    $linky = new Linky($enedisCredentials);

    $today = new \DateTime('NOW', new \DateTimeZone(Linky::TIMEZONE));
    $yesterday = clone $today;
    $yesterday->sub(new \DateInterval('P1D')); // Enedis' last data are for yesterday

    // Hourly
    $hourlyData = $linky->getHourlyData($yesterday);

    DataHelper::merge($data, Linky::DATASET_HOURLY, $hourlyData);

    // Daily
    $daysAgo = clone $yesterday;
    $daysAgo->sub(new \DateInterval('P3D'));
    $dailyData = $linky->getDailyData($daysAgo, $yesterday);

    DataHelper::merge($data, Linky::DATASET_DAILY, $dailyData);

    // Monthly
    $monthsAgo = clone $yesterday;
    $monthsAgo->sub(new \DateInterval('P3M'));
    $monthsAgo->setDate($monthsAgo->format('Y'), $monthsAgo->format('m'), '01');
    $monthlyData = $linky->getMonthlyData($monthsAgo, $yesterday);

    DataHelper::merge($data, Linky::DATASET_MONTHLY, $monthlyData);

    // Yearly
    $yearlyData = $linky->getYearlyData();

    DataHelper::merge($data, Linky::DATASET_YEARLY, $yearlyData);
} catch (LinkyException $e) {
    echo $e->getMessage().PHP_EOL;

    exit;
}

file_put_contents($storage, json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
