<?php
/*

2025 Jari Perkiömäki OH6BG

Download the required suncalc.php from https://github.com/gregseth/suncalc-php and place it in the same directory as this script.

*/

require_once 'suncalc.php';

use AurorasLive\SunCalc;

function get_nearest_timezone($latitude, $longitude, $countryCode = '')
{
  $timezoneIdentifiers = $countryCode
    ? DateTimeZone::listIdentifiers(DateTimeZone::PER_COUNTRY, $countryCode)
    : DateTimeZone::listIdentifiers();

  if (empty($timezoneIdentifiers)) {
    return 'UTC';
  }

  $closestTimezone = '';
  $shortestDistance = INF;

  foreach ($timezoneIdentifiers as $timezoneId) {
    $timezone = new DateTimeZone($timezoneId);
    $location = $timezone->getLocation();

    if (!$location) {
      continue;
    }

    $tzLat = $location['latitude'];
    $tzLong = $location['longitude'];

    $theta = $longitude - $tzLong;
    $distance = sin(deg2rad($latitude)) * sin(deg2rad($tzLat)) +
      cos(deg2rad($latitude)) * cos(deg2rad($tzLat)) * cos(deg2rad($theta));
    $distance = acos(min(max($distance, -1), 1));
    $distance = rad2deg($distance);

    if ($distance < $shortestDistance) {
      $shortestDistance = $distance;
      $closestTimezone = $timezoneId;
    }
  }

  return $closestTimezone ?: 'UTC';
}

function formatTimeRounded(DateTime $dt): string
{
  $minutes = (int)$dt->format('i');
  $seconds = (int)$dt->format('s');
  $hours = (int)$dt->format('H');

  if ($seconds >= 30) {
    $minutes += 1;
    if ($minutes >= 60) {
      $minutes = 0;
      $hours = ($hours + 1) % 24;
    }
  }

  return sprintf('%02d:%02d', $hours, $minutes);
}

// Coordinates
$latitude = isset($_GET['lat']) ? floatval($_GET['lat']) : 0.0;
$longitude = isset($_GET['lon']) ? floatval($_GET['lon']) : 0.0;

// Timezone
$tzParam = strtolower($_GET['tz'] ?? 'utc');
$timezone = ($tzParam === 'local')
  ? new DateTimeZone(get_nearest_timezone($latitude, $longitude, 'FI'))
  : new DateTimeZone('UTC');

// Output formats
$format = strtolower($_GET['format'] ?? 'json');
$validFormats = ['json', 'csv'];
if (!in_array($format, $validFormats)) {
  http_response_code(400);
  echo "Invalid format parameter. Use 'json' or 'csv'.";
  exit;
}

// Column selection
$defaultColumns = ['nightend', 'nauticaldawn', 'dawn', 'sunrise', 'sunriseend', 'goldenhourend', 'solarnoon', 'goldenhour', 'sunsetstart', 'sunset', 'dusk', 'nauticaldusk', 'night', 'nadir'];
$columnsParam = $_GET['columns'] ?? '';
$selectedColumns = array_map('strtolower', array_filter(array_map('trim', explode(',', $columnsParam))));
$selectedColumns = $selectedColumns ?: $defaultColumns;

// Generate 5-day range
$dateParam = $_GET['date'] ?? '';
if ($dateParam) {
  $centerDate = DateTime::createFromFormat('Y-m-d', $dateParam, new DateTimeZone('UTC'));
  if ($centerDate && $centerDate->format('Y-m-d') === $dateParam) {
    $days = [];
    for ($i = -2; $i <= 2; $i++) {
      $date = clone $centerDate;
      $date->modify("$i day");
      $days[] = $date;
    }
  } else {
    http_response_code(400);
    echo "Invalid date parameter. Use YYYY-MM-DD format.";
    exit;
  }
} else {
  // No date provided, use today as center
  $days = [];
  for ($i = -2; $i <= 2; $i++) {
    $date = new DateTime("now", new DateTimeZone('UTC'));
    $date->modify("$i day");
    $days[] = $date;
  }
}

$allPhases = [];

foreach ($days as $date) {
  try {
    $suncalc = new SunCalc($date, $latitude, $longitude);
    $sunTimes = $suncalc->getSunTimes();
    $solarNoon = $sunTimes['solarNoon'] ?? null;

    $altitude = null;
    if ($solarNoon instanceof DateTime) {
      $position = $suncalc->getSunPosition();
      $altitude = $position->altitude ?? null;
    }

    $condition = (is_null($sunTimes['sunrise']) && is_null($sunTimes['sunset']))
      ? (($altitude > 0) ? "Polar Day" : (($altitude < 0) ? "Polar Night" : "Undet"))
      : "Normal";

    $phases = ['date' => $date->format('Y-m-d'), 'condition' => $condition];
    foreach ($sunTimes as $phase => $time) {
      $key = strtolower($phase);
      if (in_array($key, $selectedColumns)) {
        if ($time instanceof DateTime) {
          $time->setTimezone($timezone);
          $phases[$key] = formatTimeRounded($time);
        } else {
          $phases[$key] = 'N/A';
        }
      }
    }

    $allPhases[] = $phases;
  } catch (Exception $e) {
    error_log("Error processing date {$date->format('Y-m-d')}: " . $e->getMessage());
    continue;
  }
}

// Determine headers
$headers = array_unique(array_merge(['date', 'condition'], $selectedColumns));
$headers[] = 'latitude';
$headers[] = 'longitude';

// Output
switch ($format) {
  case 'csv':
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="sun_times.csv"');
    $fp = fopen('php://output', 'w');
    fputcsv($fp, $headers, ',', '"', '\\');
    foreach ($allPhases as $row) {
      $line = [];
      foreach ($headers as $header) {
        if ($header === 'latitude') {
          $line[] = $latitude;
        } elseif ($header === 'longitude') {
          $line[] = $longitude;
        } else {
          $line[] = $row[$header] ?? '';
        }
      }
      fputcsv($fp, $line, ',', '"', '\\');
    }
    fclose($fp);
    break;

  case 'json':
  default:
    header('Content-Type: application/json');
    $jsonPhases = [];
    foreach ($allPhases as $row) {
      $enhancedRow = $row;
      $enhancedRow['latitude'] = $latitude;
      $enhancedRow['longitude'] = $longitude;
      foreach ($headers as $header) {
        if (
          isset($row[$header]) &&
          $row[$header] !== 'N/A' &&
          $header !== 'date' &&
          $header !== 'condition'
        ) {
          $dateObj = null;
          foreach ($days as $date) {
            if ($date->format('Y-m-d') === $row['date']) {
              $suncalc = new SunCalc($date, $latitude, $longitude);
              $sunTimes = $suncalc->getSunTimes();
              foreach ($sunTimes as $phaseKey => $phaseTime) {
                if (strtolower($phaseKey) === $header && $phaseTime instanceof DateTime) {
                  $dateObj = clone $phaseTime;
                  if ($dateObj instanceof DateTime) {
                    $dateObj->setTimezone($timezone);
                  }
                  break;
                }
              }
              break;
            }
          }
          if ($dateObj instanceof DateTime) {
            $suncalcForPhase = new SunCalc($dateObj, $latitude, $longitude);
            $position = $suncalcForPhase->getSunPosition($dateObj);
            $azimuthDeg = isset($position->azimuth)
              ? round(fmod(rad2deg($position->azimuth) + 180, 360), 2)
              : null;
            $altitudeDeg = isset($position->altitude)
              ? round(rad2deg($position->altitude), 2)
              : null;
            $enhancedRow[$header . '_azimuth'] = $azimuthDeg;
            $enhancedRow[$header . '_altitude'] = $altitudeDeg;
          } else {
            $enhancedRow[$header . '_azimuth'] = null;
            $enhancedRow[$header . '_altitude'] = null;
          }
        }
      }
      $jsonPhases[] = $enhancedRow;
    }
    echo json_encode($jsonPhases, JSON_PRETTY_PRINT);
    break;
}
