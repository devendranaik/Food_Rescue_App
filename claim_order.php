<?php

header("Content-Type:application/json");
require_once __DIR__ . "/includes/Logger.php";
// $order_details_ids = is_array($_POST['order_details_ids']) ? $_POST['order_details_ids'] : null;
$order_details_ids = [1];

if(!$order_details_ids){
    echo json_encode(['status' => 'error', 'message' => 'Order details ID\'s are required']);
    Logger::log("Order Detail IDs are required");
    exit;
}

include_once 'DB.php';

if(empty($db)){
    echo "Database not connected.";
    Logger::log("Database not connected");
    closeDBConnection();
}
try{
    $db->startTransaction();
    Logger::log("Getting order details for ids:".print_r($order_details_ids,true));
    $db->where('fod.id', $order_details_ids, 'IN');
    $db->join("restaurant_menu_items rmi", "fod.restaurant_menu_items_id=rmi.id");
    $order_details = $db->get("food_order_details fod");

    if(count($order_details) == 0){
        echo json_encode(['status' => 'error', 'message' => 'Order details ID\'s are invalid']);
        Logger::log("Order Details IDs are invalid");
        closeDBConnection();
    }

    $total_order_amount = 0;
    $claimed_order = [];
    $claimed_order_details = [];
    $total_order_amount = array_sum(array_column($order_details, "discounted_price"));
    foreach($order_details as $order_detail){
        if($order_detail['status'] != "Cancelled"){
            echo json_encode(['status' => 'error', 'message' => 'Status of items in the order have changed please refresh and try to place a new order.']);
            closeDBConnection();
        }
    }
    $db->where('fod.id', $order_details_ids, 'IN');
    $db->update('food_order_details fod', ["status" => "Claimed"]);
    Logger::log("Updated value for status in food_order_details table");

    $claimed_order['total_amount'] = $total_order_amount;
    $claimed_order_id = $db->insert("claimed_order",$claimed_order);
    Logger::log("Inserted data in claimed_order table with id: ".$claimed_order_id);
    foreach($order_details as $order_detail){
        $claimed_order_details[] = [
            "claimed_order_id" => $claimed_order_id,
            "food_order_details_id" => $order_detail['id'],
            "price" => $order_detail['discounted_price']
        ];
    }
    $db->insertMulti("claimed_order_details",$claimed_order_details);
    Logger::log("Inserted data in claim_order_details table for claim_order id: ".$claimed_order_id);
    $db->commit();
}
catch(Exception $e){
    $db->rollback();
    echo 'Exception Message: ' .$e->getMessage();
}
closeDBConnection();
