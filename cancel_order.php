<?php

header("Content-Type:application/json");
require_once __DIR__ . "/includes/Logger.php";
include_once 'DB.php';
$order_id = $_POST['order_id'] ?? null;

if(!$order_id){
    echo json_encode(['status' => 'error', 'message' => 'Order ID is required']);
    Logger::log("Order ID is required");
    closeDBConnection();
}

if(empty($db)){
    echo "Database not connected.";
    Logger::log("Database not connected");
    closeDBConnection();
}
try{
    Logger::log("Getting Order with id: ".$order_id);
    $db->where('id', $order_id);
    $order = $db->getOne('food_order');

    if(!$order) {
        echo json_encode(['status' => 'error', 'message' => 'Order not found']);
        Logger::log("Order not found for order_id: ".$order_id);
        closeDBConnection();
    }

    // Mark order as cancelled
    $db->where('id', $order_id);
    $update_order = $db->update('food_order', ['status' => 'Cancelled']);
    Logger::log("Marked order as cancelled with order_id: ".$order_id);

    if ($update_order) {
        echo json_encode(['status' => 'success', 'message' => 'Order marked as cancelled']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Failed to update order']);
    }
    closeDBConnection();
}
catch(Exception $e){
    $db->rollback();
    echo 'Exception Message: ' .$e->getMessage();
    closeDBConnection();
}
