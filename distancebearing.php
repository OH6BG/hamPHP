<?php
function haversineDistance($lat1, $lon1, $lat2, $lon2)
{
    // Radius of the Earth in kilometers
    $R = 6371.0;

    // Convert degrees to radians
    $lat1 = deg2rad($lat1);
    $lon1 = deg2rad($lon1);
    $lat2 = deg2rad($lat2);
    $lon2 = deg2rad($lon2);

    $dlat = $lat2 - $lat1;
    $dlon = $lon2 - $lon1;

    $a = sin($dlat / 2) * sin($dlat / 2) +
        cos($lat1) * cos($lat2) *
        sin($dlon / 2) * sin($dlon / 2);
    $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

    $distance = $R * $c;
    return $distance;
}

function vincentyDistance($lat1, $lon1, $lat2, $lon2)
{
    /*

    Calculate the distance between two points on the Earth using the Vincenty formula, which is more accurate than the Haversine formula for long distances. It accounts for the ellipsoidal shape of the Earth, based on the WGS-84 ellipsoid model.

    Returns the distance in kilometers.

    $lat1, $lon1: Latitude and longitude of point 1 in degrees.
    $lat2, $lon2: Latitude and longitude of point 2 in degrees.

    */

    $a = 6378137.0; // Major semiaxis [m]
    $f = 1 / 298.257223563; // Flattening
    $b = (1 - $f) * $a;

    $phi1 = deg2rad($lat1);
    $phi2 = deg2rad($lat2);
    $U1 = atan((1 - $f) * tan($phi1));
    $U2 = atan((1 - $f) * tan($phi2));
    $L = deg2rad($lon2 - $lon1);
    $Lambda = $L;

    $iterLimit = 100;
    do {
        $sinLambda = sin($Lambda);
        $cosLambda = cos($Lambda);
        $sinSigma = sqrt(
            (cos($U2) * $sinLambda) * (cos($U2) * $sinLambda) +
                (cos($U1) * sin($U2) - sin($U1) * cos($U2) * $cosLambda) *
                (cos($U1) * sin($U2) - sin($U1) * cos($U2) * $cosLambda)
        );
        if ($sinSigma == 0) return 0; // coincident points
        $cosSigma = sin($U1) * sin($U2) + cos($U1) * cos($U2) * $cosLambda;
        $sigma = atan2($sinSigma, $cosSigma);
        $sinAlpha = cos($U1) * cos($U2) * $sinLambda / $sinSigma;
        $cosSqAlpha = 1 - $sinAlpha * $sinAlpha;
        $cos2SigmaM = ($cosSqAlpha != 0) ? ($cosSigma - 2 * sin($U1) * sin($U2) / $cosSqAlpha) : 0;
        $C = $f / 16 * $cosSqAlpha * (4 + $f * (4 - 3 * $cosSqAlpha));
        $LambdaP = $Lambda;
        $Lambda = $L + (1 - $C) * $f * $sinAlpha *
            ($sigma + $C * $sinSigma * ($cos2SigmaM + $C * $cosSigma * (-1 + 2 * $cos2SigmaM * $cos2SigmaM)));
    } while (abs($Lambda - $LambdaP) > 1e-12 && --$iterLimit > 0);

    if ($iterLimit == 0) return NAN; // formula failed to converge

    $uSq = $cosSqAlpha * ($a * $a - $b * $b) / ($b * $b);
    $A = 1 + $uSq / 16384 * (4096 + $uSq * (-768 + $uSq * (320 - 175 * $uSq)));
    $B = $uSq / 1024 * (256 + $uSq * (-128 + $uSq * (74 - 47 * $uSq)));
    $deltaSigma = $B * $sinSigma * (
        $cos2SigmaM + $B / 4 * (
            $cosSigma * (-1 + 2 * $cos2SigmaM * $cos2SigmaM) -
            $B / 6 * $cos2SigmaM * (-3 + 4 * $sinSigma * $sinSigma) * (-3 + 4 * $cos2SigmaM * $cos2SigmaM)
        )
    );
    $s = $b * $A * ($sigma - $deltaSigma);

    return $s / 1000.0; // return distance in kilometers
}

function initialBearing($lat1, $lon1, $lat2, $lon2)
{
    /*
    
    Calculate the initial bearing from point 1 to point 2. The formula is based on the spherical law of cosines.

    Returns the bearing in degrees from True North (0°).

     */

    $phi1 = deg2rad($lat1);
    $phi2 = deg2rad($lat2);
    $dlon = deg2rad($lon2 - $lon1);

    $y = sin($dlon) * cos($phi2);
    $x = cos($phi1) * sin($phi2) -
        sin($phi1) * cos($phi2) * cos($dlon);
    $bearing = atan2($y, $x);
    $bearing = rad2deg($bearing);
    return fmod(($bearing + 360), 360);
}

// Example 1: Haversine distance
$lat1 = 40.7128;  // New York
$lon1 = -74.0060;
$lat2 = 51.5074;  // London
$lon2 = -0.1278;

$distance = haversineDistance($lat1, $lon1, $lat2, $lon2);
echo "Haversine distance: " . round($distance, 2) . " km\n";

// Example 2: Vincenty distance
$distance = vincentyDistance($lat1, $lon1, $lat2, $lon2);
echo "Vincenty distance:  " . round($distance, 2) . " km\n";

$bearing = initialBearing($lat1, $lon1, $lat2, $lon2);
echo "Initial Bearing:    " . round($bearing, 2) . "°\n";
