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

	echo 'Getting all years...'.PHP_EOL;

	$yearlyData = $linky->getYearlyData();

	DataHelper::merge($data, Linky::DATASET_YEARLY, $yearlyData);

	foreach ($yearlyData as $year => $_) {
		if ($_ === null) {
			continue;
		}

		echo 'Getting monthly for year '.$year.'...'.PHP_EOL;
		try {
			$monthlyData = $linky->getMonthlyData(new \DateTime($year.'-01-01', new \DateTimeZone(Linky::TIMEZONE)), new \DateTime($year.'-12-31', new \DateTimeZone(Linky::TIMEZONE)));

			DataHelper::merge($data, Linky::DATASET_MONTHLY, $monthlyData);

			foreach ($monthlyData as $month => $_) {
				if ($_ === null) {
					continue;
				}

				echo 'Getting daily for '.$month.'...'.PHP_EOL;
				try {
					$dailyData = $linky->getDailyData(new \DateTime($month.'-01', new \DateTimeZone(Linky::TIMEZONE)), new \DateTime($month.'-31', new \DateTimeZone(Linky::TIMEZONE)));

					DataHelper::merge($data, Linky::DATASET_DAILY, $dailyData);

					foreach ($dailyData as $day => $_) {
						if ($_ === null) {
							continue;
						}

						echo 'Getting hourly for day '.$day.'...'.PHP_EOL;
						try {
							$hourlyData = $linky->getHourlyData(new \DateTime($day, new \DateTimeZone(Linky::TIMEZONE)));

							DataHelper::merge($data, Linky::DATASET_HOURLY, $hourlyData);
						} catch (LinkyException $e) {
							echo $e->getMessage().PHP_EOL;
						}
					}
				} catch (LinkyException $e) {
					echo $e->getMessage().PHP_EOL;
				}
			}
		} catch (LinkyException $e) {
			echo $e->getMessage().PHP_EOL;
		}
	}
} catch (LinkyException $e) {
	echo $e->getMessage().PHP_EOL;

	exit;
}

file_put_contents($storage, json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
