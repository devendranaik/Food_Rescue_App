<?php

header("Content-Type:application/json");
require_once __DIR__ . "/includes/Logger.php";
include_once 'DB.php';

// Validate input
$user_id = $_POST['user_id'] ?? null;
$page = $_POST['page'] ?? 1;
$limit = $_POST['limit'] ?? 10;

try{
    if (empty($user_id)) {
        echo json_encode(["status" => "error", "message" => "User ID is required"]);
        Logger::log("User ID is required");
        closeDBConnection();
    }

    if ($page < 0 || $limit < 1) {
        echo json_encode(["status" => "error", "message" => "Invalid Page/Limit value"]);
        Logger::log("Invalid Page/Limit value");
        closeDBConnection();
    }

    if (empty($db)) {
        echo json_encode(["status" => "error", "message" => "Database not connected"]);
        Logger::log("Database not connected");
        closeDBConnection();
    }

    // Fetch user details
    $db->where('id', $user_id);
    $user = $db->getOne('user');

    if (!$user) {
        echo json_encode(["status" => "error", "message" => "User not found"]);
        Logger::log("User not found");
        closeDBConnection();
    }

    if (empty($user['latitude']) || empty($user['longitude'])) {
        echo json_encode(["status" => "error", "message" => "User location is not set"]);
        Logger::log("User location is not set");
        closeDBConnection();
    }

    // Bounding Box Calculation for square area around user's location
    function calculateBoundingBox($latitude, $longitude, $radiusKm = 3) {
        $latDiff = $radiusKm / 111; // 1 degree ≈ 111 km
        $lonDiff = $radiusKm / abs(cos(deg2rad($latitude)) * 111);

        return [
            'min_lat' => $latitude - $latDiff,
            'max_lat' => $latitude + $latDiff,
            'min_lon' => $longitude - $lonDiff,
            'max_lon' => $longitude + $lonDiff,
        ];
    }

    $userLat = (float)$user['latitude'];
    $userLon = (float)$user['longitude'];
    $isVeg = (int)$user['is_vegetarian'];
    $offset = ($page - 1) * $limit;

    $boundingBox = calculateBoundingBox($userLat, $userLon);
    $params = [$isVeg, $boundingBox['min_lat'], $boundingBox['max_lat'], $boundingBox['min_lon'], $boundingBox['max_lon'], $limit, $offset];

    $cancelled_orders = $db->rawQuery("CALL ListCancelledOrdersByUserId(?, ?, ?, ?, ?, ?, ?)", $params);
    Logger::log("Called ListCancelledOrdersByUserId with params: " . json_encode($params));

    $finalOrders = [];
    if ($cancelled_orders) {
        foreach ($cancelled_orders as $order) {
            $lat = (float)$order['latitude'];
            $lon = (float)$order['longitude'];

            // Haversine formula for precise filtering of orders within 3 km
            $earthRadius = 6371;
            $dLat = deg2rad($lat - $userLat);
            $dLon = deg2rad($lon - $userLon);
            $a = sin($dLat / 2) * sin($dLat / 2) +
                cos(deg2rad($userLat)) * cos(deg2rad($lat)) * sin($dLon / 2) * sin($dLon / 2);
            $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
            $distance = $earthRadius * $c;

            // Keep only valid orders within 3 km
            if ($distance <= 3) {
                $order['distance_km'] = number_format($distance, 2, '.', '');
                $finalOrders[] = $order;
            }
        }
    }

    if (!empty($finalOrders)) {
        echo json_encode(["status" => "success", "message" => "Cancelled Orders Found", "data" => $finalOrders]);
    } else {
        echo json_encode(["status" => "success", "message" => "No Cancelled Orders Found", "data" => []]);
        Logger::log("No Cancelled Orders Found");
    }
    closeDBConnection();
}
catch(Exception $e)
{
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
    Logger::log($e->getMessage());
    closeDBConnection();
}
