#!/usr/bin/php
<?php
// Preset PHP settings
error_reporting(E_ALL);
ini_set('display_errors', 0);
date_default_timezone_set('UTC');

// Define root directory
define('DS', DIRECTORY_SEPARATOR);
define('ROOT', __DIR__ . DS);

if (!isset($argv)) {
	die('ERROR: Please run this script in command line.');
}

if (!isset($argv[1])) {
	die('ERROR: Please provide the absolute path to input CSV file.');
}

if (!file_exists($argv[1])) {
	die('ERROR: The input CSV file is not found.');
}

if (!isset($argv[2])) {
	die('ERROR: Please provide the absolute path to output CSV file.');
}

if (!is_writable(dirname($argv[2]))) {
	die('ERROR: The output directory is not writable.');
}

$file = fopen($argv[1], 'r');

if (!$file) {
	die('ERROR: Failed to read the input CSV.');
}

@file_put_contents($argv[2], '');

while (!feof($file)) {
	$data = fgetcsv($file);

	// CIDR
	if (strpos($data[0], '/') !== false) {
		list($ip, $cidr) = explode('/', $data[0]);

		// IPv4
		if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
			$rows = cidrToRange($data[0]);
			unset($data[0]);

			@file_put_contents($argv[2], '"' . implode('","', array_merge($rows, $data)) . "\"\n", FILE_APPEND);

			continue;
		}

		// IPv6
		if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
		}

		continue;
	}

	// IPv4 Range
	if (filter_var($data[0], FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) && filter_var($data[1], FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
		$rows = rangeToCIDR($data[0], $data[1]);

		unset($data[0]);
		unset($data[1]);

		foreach ($rows as $row) {
			@file_put_contents($argv[2], '"' . implode('","', array_merge([$row], $data)) . "\"\n", FILE_APPEND);
		}

		continue;
	}

	// IPv6 Range
	if (filter_var($data[0], FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) && filter_var($data[1], FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
	}
}

fclose($file);

function cidrToRange($cidr)
{
	list($ip, $prefix) = explode('/', $cidr);

	$ipStart = long2ip((ip2long($ip)) & ((-1 << (32 - (int) $prefix))));

	$total = 1 << (32 - $prefix);

	$ipStartLong = sprintf('%u', ip2long($ipStart));
	$ipEndLong = $ipStartLong + $total - 1;

	if ($ipEndLong > 4294967295) {
		$ipEndLong = 4294967295;
	}

	$ipLast = long2ip($ipEndLong);

	return [$ipStart, $ipLast, $total];
}

function iMask($s)
{
	return base_convert((pow(2, 32) - pow(2, (32 - $s))), 10, 16);
}

function iMaxBlock($ibase, $tbit)
{
	while ($tbit > 0) {
		$im = hexdec(iMask($tbit - 1));
		$imand = $ibase & $im;
		if ($imand != $ibase) {
			break;
		}
		--$tbit;
	}

	return $tbit;
}

function rangeToCIDR($ipStart, $ipEnd)
{
	$s = explode('.', $ipStart);

	$start = '';
	$dot = '';

	while (list($key, $val) = each($s)) {
		$start = sprintf('%s%s%d', $start, $dot, $val);
		$dot = '.';
	}

	$end = '';
	$dot = '';

	$e = explode('.', $ipEnd);
	while (list($key, $val) = each($e)) {
		$end = sprintf('%s%s%d', $end, $dot, $val);
		$dot = '.';
	}

	$start = ip2long($start);
	$end = ip2long($end);
	$result = [];

	while ($end >= $start) {
		$maxSize = iMaxBlock($start, 32);
		$x = log($end - $start + 1) / log(2);
		$maxDiff = floor(32 - floor($x));

		$ip = long2ip($start);

		if ($maxSize < $maxDiff) {
			$maxSize = $maxDiff;
		}

		array_push($result, "$ip/$maxSize");
		$start += pow(2, (32 - $maxSize));
	}

	return $result;
}
