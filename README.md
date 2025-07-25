# hamPHP
Various PHP scripts for amateur radio purposes

# distancebearing.php

This file provides functions for calculating the great-circle distance (shortest path) and initial bearing between two points on Earth, specified by latitude and longitude.

## 1. haversineDistance($lat1, $lon1, $lat2, $lon2)

The Haversine formula calculates the shortest distance between two points on a sphere (like the Earth). The Haversine formula is generally accurate for most purposes, especially for longer distances. However, it assumes the Earth is a perfect sphere, which isn't quite true. The Earth is slightly flattened at the poles (an ellipsoid). For very short distances, or when high precision is needed, the error due to this simplification becomes more noticeable.

**Highlights:**

* **Relatively simple**: It's computationally less intensive than more complex methods like Vincenty's formulae.
* **Good enough accuracy**: For many applications, the accuracy is sufficient. The error introduced by assuming a spherical Earth is often negligible, especially for longer distances.

**Parameters:**

* `$lat1`: Latitude of point 1 (decimal degrees). South is negative.
* `$lon1`: Longitude of point 1 (decimal degrees). West is negative.
* `$lat2`: Latitude of point 2 (decimal degrees). South is negative.
* `$lon2`: Longitude of point 2 (decimal degrees). West is negative.


## 2. vincentyDistance($lat1, $lon1, $lat2, $lon2)

Just like the Haversine formula, Vincenty's formulae calculate the shortest path distance between two points on the Earth's surface. It accounts for the fact that the Earth is not a perfect sphere, but an ellipsoid (slightly flattened at the poles). Vincenty's formulae are highly accurate, even for short distances and near antipodal points (opposite sides of the Earth).

Unlike the Haversine formula, which is a direct calculation, Vincenty's uses an iterative approach. It starts with an initial guess for the distance and refines it repeatedly until the result converges to a very precise value. Think of it like zooming in on a map – each iteration gets you closer to the true distance.

The calculations within each iteration involve a series of trigonometric functions and other mathematical operations. It's more complex than the Haversine formula, but the added complexity is what gives it higher accuracy.

**Highlights:**

* **High accuracy**: It provides the most accurate distance calculations, accounting for the Earth's ellipsoidal shape.
* **Handles all cases**: It works reliably for all distances and locations on Earth, including near antipodal points where the Haversine formula can have issues.
* **Computational cost**: The iterative nature of Vincenty's formulae makes them more computationally expensive than the Haversine formula. While this is usually not a major concern for most applications, it might be a factor if you need to perform a massive number of distance calculations. If speed and simplicity are more important and the Earth's ellipsoidal shape isn't a major factor (e.g., for longer distances), the Haversine formula is often a good choice.

**Parameters:**

* `$lat1`: Latitude of point 1 (decimal degrees). South is negative.
* `$lon1`: Longitude of point 1 (decimal degrees). West is negative.
* `$lat2`: Latitude of point 2 (decimal degrees). South is negative.
* `$lon2`: Longitude of point 2 (decimal degrees). West is negative.


## 3. initialBearing($lat1, $lon1, $lat2, $lon2)

Calculates the initial bearing from point 1 (latitude/longitude $lat1, $lon1) to point 2 ($lat2, $lon2).

This function is highly useful in amateur radio for aiming directional antennas, such as yagis or beams.  It calculates the initial bearing, which is the direction you need to point your antenna to establish a direct, short-path communication link with a station at a different location.  This bearing is the angle, measured clockwise from true north (0°), to the target station.

Imagine you're at point 1 and want to communicate with a station at point 2.  This function tells you the direction to rotate your antenna for the optimal initial signal path.  It's important to distinguish between true north and magnetic north (what your compass indicates).  True north is the reference for this function.  You'll need to account for magnetic declination (the difference between true and magnetic north) at your location to accurately aim your antenna using a compass.

**Parameters:**

* `$lat1`: Latitude of point 1 (decimal degrees). South is negative.
* `$lon1`: Longitude of point 1 (decimal degrees). West is negative.
* `$lat2`: Latitude of point 2 (decimal degrees). South is negative.
* `$lon2`: Longitude of point 2 (decimal degrees). West is negative.

___

# maidenhead.php

This file provides two functions for working with Maidenhead grid locators: one to encode latitude and longitude coordinates into a grid locator (up to 12 characters), and another to decode a grid locator back into decimal degree latitude and longitude.

## 1. latlon2loc($position, $precision = 6)

This function converts geographic coordinates (latitude and longitude) into a Maidenhead grid locator.

The Maidenhead grid locator system is used in amateur radio to concisely represent a location on Earth. It's like a more precise version of saying "I'm in New York City" – it pinpoints a smaller area within the city.

The function takes an array (`$position`, e.g. `[40.645246, -73.785112]` for New York City) containing the decimal degree latitude and longitude (south and west being negative) and a precision level (from 2 to 12 characters) as input.

It returns the corresponding Maidenhead grid locator string. Higher precision means a smaller, more specific area is represented.

**Precision**

`$precision` is an integer that defines the precision of the Maidenhead locator:

* `$precision = 1`, 2-char Maidenhead
* `$precision = 2`, 4-char Maidenhead
* `$precision = 3`, 6-char Maidenhead
* `$precision = 4`, 8-char Maidenhead
* `$precision = 5`, 10-char Maidenhead
* `$precision = 6`, 12-char Maidenhead

## 2. loc2latlon($grid)

This function decodes a Maidenhead grid locator string (up to 12 characters) into its corresponding decimal degree latitude and longitude coordinates. The returned array contains the latitude (negative for south) and longitude (negative for west).

For example, the Maidenhead grid locator of FN30CP54SU86 would be converted to an array of `[40.645251736111, -73.785121527778]` for New York City.

___

# sun.php

This script calculates various sun times for a given location and date range, outputting the results in either CSV or JSON format.  It uses the `suncalc.php` library, available at https://github.com/gregseth/suncalc-php, so make sure that file is in the same directory.

Here's how to use it, including the different URL parameters you can combine:

**Base URL:**  `sun.php`

**Required URL Parameters:**

* **`lat`:** Latitude of the location (decimal degrees). South is negative.  *Example:* `lat=40.7128`
* **`lon`:** Longitude of the location (decimal degrees). West is negative. *Example:* `lon=-74.0060`

If omitted, the default latitude and longitude are 0.0, 0.0.

**Optional URL Parameters:**

* **`date` (YYYY-MM-DD):**  Center date for the 5-day range. If omitted, the script uses the current date as the center. *Example:* `date=2024-03-20`
* **`tz`:** Timezone for the output.  Options:
    * **`utc` (default):** Output times in Universal Coordinated Time (UTC).
    * **`local`:** Output times in the timezone closest to the specified coordinates (with a preference for Finnish timezones if available; **change this to your preference in the code**). Note: Consider this highly experimental! *Example:* `tz=local`
* **`format`:** Output format. Options:
    * **`json` (default):** Output as JSON.
    * **`csv`:** Output as CSV. *Example:* `format=csv`
* **`columns`:** Comma-separated list of sun times to include in the output.  Available columns are in a chronological order as follows: `nightend`, `nauticaldawn`, `dawn`, `sunrise`, `sunriseend`, `goldenhourend`, `solarnoon`, `goldenhour`, `sunsetstart`, `sunset`, `dusk`, `nauticaldusk`, `night`, `nadir`.  If omitted, all columns are included. *Example:* `columns=sunrise,sunset,solarnoon`


**Example Usage Scenarios:**

1. **Sunrise and sunset times for New York City in local time for a 5-day range centered on today, JSON format:**

   ```
   sun.php?lat=40.7128&lon=-74.0060&tz=local&columns=sunrise,sunset
   ```

2. **All sun times for Helsinki, Finland, for a 5-day range centered on a specific date in UTC, CSV format:**

   ```
   sun.php?lat=60.1708&lon=24.9375&date=2024-12-25&format=csv
   ```

3. **All sun times for a 5-day range centered on today, for a location in Japan, in UTC, JSON format:**

   ```
   sun.php?lat=35.6895&lon=139.6917
   ```

**JSON Output Details:**

The JSON output includes the following for each date:

* `date`: Date in YYYY-MM-DD format.
* `condition`:  "Normal", "Polar Day", "Polar Night", or "Undet" (undetermined).
* Requested sun times (e.g., `sunrise`, `sunset`).
* `latitude`:  The provided latitude.
* `longitude`: The provided longitude.
* For each requested sun time, the azimuth and altitude of the sun (in degrees) are also included (e.g., `sunrise_azimuth`, `sunrise_altitude`).


**CSV Output Details:**

The CSV output includes a header row with the requested columns, `date`, `condition`, `latitude`, and `longitude`.  Each subsequent row represents a day in the 5-day range. The azimuth and altitude of the sun are not included in the CSV output.


This script provides a flexible way to obtain various sun time data for different locations and dates, making it suitable for a range of applications. Remember to consult the `suncalc.php` documentation for more details on the specific sun time calculations.
