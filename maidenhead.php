<?php
function latlon2loc($position, $precision = 6)
{
  /*

  Convert latitude and longitude to Maidenhead grid locator.

  $position is an array with decimal latitude and longitude;
  South is negative latitude, West is negative longitude.
  Example: [40.645246, -73.785112] for New York City

  $precision is an integer that defines the precision of the Maidenhead locator:
  $precision = 1, 2-char Maidenhead
  $precision = 2, 4-char Maidenhead
  $precision = 3, 6-char Maidenhead
  $precision = 4, 8-char Maidenhead
  $precision = 5, 10-char Maidenhead
  $precision = 6, 12-char Maidenhead

  */

  if (count($position) != 2) {
    throw new InvalidArgumentException("array of latitude and longitude required");
  }
  $lat = floatval($position[0]);
  $lon = floatval($position[1]);

  $A = ord("A");
  $a = fmod($lon + 180, 20);
  $b = fmod($lat + 90, 10);
  $a0 = intval(($lon + 180) / 20);
  $b0 = intval(($lat + 90) / 10);
  $grid = chr($A + $a0) . chr($A + $b0);
  $lon = $a / 2.0;
  $lat = $b;
  $i = 1;

  while ($i < $precision) {
    $i += 1;
    $a = fmod($lon, 1);
    $b = fmod($lat, 1);
    $a0 = intval($lon);
    $b0 = intval($lat);

    if ($i % 2 == 0) {
      $grid .= strval($a0) . strval($b0);
      $lon = 24 * $a;
      $lat = 24 * $b;
    } else {
      $grid .= chr($A + $a0) . chr($A + $b0);
      $lon = 10 * $a;
      $lat = 10 * $b;
    }
  }

  return $grid;
}

function loc2latlon($grid)
{
  /*

  Convert Maidenhead grid locator to decimal latitude and longitude.

  South is negative latitude, West is negative longitude.
  Example: FN30CP54SU86 -> [40.645251736111, -73.785121527778] for New York City

  */

  if (!is_string($grid)) {
    throw new InvalidArgumentException("Maidenhead grid locator is a string");
  }
  $grid = strtoupper(trim($grid));
  $N = strlen($grid);

  if ($N < 2 || $N > 12 || $N % 2 !== 0) {
    throw new InvalidArgumentException("Maidenhead grid locator requires 2-12 characters, and an even number of characters");
  }

  $A = ord("A");
  $lon = -180.0;
  $lat = -90.0;

  $lon += (ord($grid[0]) - $A) * 20;
  $lat += (ord($grid[1]) - $A) * 10;

  if ($N >= 4) {
    $lon += intval($grid[2]) * 2;
    $lat += intval($grid[3]) * 1;
  }

  if ($N >= 6) {
    $lon += (ord($grid[4]) - $A) * 5.0 / 60;
    $lat += (ord($grid[5]) - $A) * 2.5 / 60;
  }

  if ($N >= 8) {
    $lon += intval($grid[6]) * 5.0 / 600;
    $lat += intval($grid[7]) * 2.5 / 600;
  }

  if ($N >= 10) {
    $lon += (ord($grid[8]) - $A) * 5.0 / 14400;
    $lat += (ord($grid[9]) - $A) * 2.5 / 14400;
  }

  if ($N >= 12) {
    $lon += intval($grid[10]) * 5.0 / 144000;
    $lat += intval($grid[11]) * 2.5 / 144000;
  }

  if ($N == 2) {
    $lon += 20 / 2;
    $lat += 10 / 2;
  } elseif ($N == 4) {
    $lon += 2 / 2;
    $lat += 1.0 / 2;
  } elseif ($N == 6) {
    $lon += (5.0 / 60) / 2;
    $lat += (2.5 / 60) / 2;
  } elseif ($N == 8) {
    $lon += (5.0 / 600) / 2;
    $lat += (2.5 / 600) / 2;
  } elseif ($N == 10) {
    $lon += (5.0 / 14400) / 2;
    $lat += (2.5 / 14400) / 2;
  } elseif ($N >= 12) {
    $lon += (5.0 / 144000) / 2;
    $lat += (2.5 / 144000) / 2;
  }

  return [$lat, $lon];
}

// Example:

$latlon = [40.645246, -73.785112];
$grid = latlon2loc($latlon);
echo "$latlon[0], $latlon[1] -> $grid\n";

list($lat, $lon) = loc2latlon($grid);
echo "$grid -> $lat, $lon\n";
