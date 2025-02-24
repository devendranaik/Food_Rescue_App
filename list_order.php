<?php

header("Content-Type:application/json");
require_once __DIR__ . "/includes/Logger.php";
$user_id = $_POST['user_id'] ?? null;
$page = $_POST['page'] ?? 1;
$limit = $_POST['limit'] ?? 10;

if(empty($user_id)){
    echo json_encode(["status" => "error","User ID is required"]);
    Logger::log("User ID is required");
    exit;
}

if($page < 0 || $limit < 1){
    echo json_encode(["status" => "error","Invalid Page/Limit value"]);
    Logger::log("Invalid Page/Limit value");
    exit;
}

include_once 'DB.php';

if(empty($db)){
    echo "Database not connected.";
    Logger::log("Database not connected");
    closeDBConnection();
}

$db->where('id', $user_id);
$user = $db->getOne('user');

if(!$user) {
    echo json_encode(['status' => 'error', 'message' => 'User not found']);
    Logger::log("User not Found");
    closeDBConnection();
}

if ((isset($user['latitude']) && $user['latitude'] !== '') && (isset($user['longitude']) && $user['longitude'] !== '')) {
    $offset = ($page - 1) * $limit;
    $params = [$user['is_vegetarian'], $user['latitude'], $user['longitude'], $limit, $offset];
    $cancelled_orders = $db->rawQuery("CALL ListCancelledOrdersByUserId(?, ?, ?, ?, ?)", $params);
    if($cancelled_orders)
    {
        echo json_encode(['status' => 'success', 'message' => 'Cancelled Orders Found', 'data' => $cancelled_orders]);
    }
    else
    {
        echo json_encode(['status' => 'success', 'message' => 'No Cancelled Orders Found', 'data' => []]);
        Logger::log("No Cancelled Orders Found");
    }
}
else {
    echo json_encode(['status' => 'error', 'message' => 'User Location is not set']);
    Logger::log("User location is not set");
}
closeDBConnection();
