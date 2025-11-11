<?php

header("Content-Type: application/json; charset=UTF-8");

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With, X-API-Key");

include_once 'config/Database.php';
include_once 'config/Auth.php'; 
include_once 'models/OrderModel.php';

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'OPTIONS') {
    http_response_code(200);
    exit();
}

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$uri_path = trim($uri, '/');
$uri_parts = explode('/', $uri_path);

$api_start_index = array_search('api', $uri_parts);

if ($api_start_index === false) {
    http_response_code(404);
    echo json_encode(["message" => "Not Found. API entry point 'api' not found in URI."]);
    exit();
}

$uri_parts = array_slice($uri_parts, $api_start_index);

if (($uri_parts[1] ?? '') !== 'orders') {
    http_response_code(404);
    echo json_encode(["message" => "Not Found. Invalid API endpoint."]); 
    exit();
}

$id = $uri_parts[2] ?? null;

$database = new Database();
$db = $database->getConnection();

$order = new OrderModel($db);
$auth = new Auth($db);

$auth->checkApiKey();

switch ($method) {
    case 'GET':
        handleGetRequest($order, $id);
        break;
    case 'POST':
        handlePostRequest($order);
        break;
    case 'PUT':
        handlePutRequest($order, $id);
        break;
    case 'DELETE':
        handleDeleteRequest($order, $id);
        break;
    default:
        http_response_code(405);
        echo json_encode(["message" => "Method not allowed."]);
        break;
}

function handleGetRequest($order, $id) {
    if ($id) {
        $order->id = $id;
        if ($order->readOne()) {
            $order_arr = array(
                "id" => $order->id,
                "user_id" => $order->user_id,
                "total_amount" => $order->total_amount,
                "status" => $order->status,
                "created_at" => $order->created_at
            );
            http_response_code(200);
            echo json_encode($order_arr);
        } else {
            http_response_code(404);
            echo json_encode(["message" => "Order not found."]);
        }
    } else {
        $stmt = $order->readAll();
        $num = $stmt->rowCount();

        if ($num > 0) {
            $orders_arr = array();
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                extract($row);
                $order_item = array(
                    "id" => $id,
                    "user_id" => $user_id,
                    "total_amount" => $total_amount,
                    "status" => $status,
                    "created_at" => $created_at
                );
                array_push($orders_arr, $order_item);
            }
            http_response_code(200);
            echo json_encode($orders_arr);
        } else {
            http_response_code(404);
            echo json_encode(["message" => "No orders found."]);
        }
    }
}

function handlePostRequest($order) {
    $data = json_decode(file_get_contents("php://input"), true);

    if (empty($data['user_id']) || empty($data['total_amount'])) {
        http_response_code(400);
        echo json_encode(["message" => "Unable to create order. Data is incomplete (user_id and total_amount required)."]);
        return;
    }
    
    $newId = $order->create($data);
    
    if ($newId) {
        http_response_code(201);
        echo json_encode(["message" => "Order created successfully.", "id" => $newId]);
    } else {
        http_response_code(503); // Service Unavailable
        echo json_encode(["message" => "Unable to create order. Database error."]);
    }
}

function handlePutRequest($order, $id) {
    if (!$id) {
        http_response_code(400);
        echo json_encode(["message" => "Missing ID for update."]);
        return;
    }
    
    $data = json_decode(file_get_contents("php://input"), true);
    
    if (empty($data['user_id']) || empty($data['total_amount']) || empty($data['status'])) {
        http_response_code(400); 
        echo json_encode(["message" => "Unable to update order. Data is incomplete."]);
        return;
    }

    $order->id = $id;
    if ($order->update($data)) {
        http_response_code(200);
        echo json_encode(["message" => "Order updated successfully."]);
    } else {
        http_response_code(503);
        echo json_encode(["message" => "Unable to update order. Maybe ID not found or no changes made."]);
    }
}

function handleDeleteRequest($order, $id) {
    if (!$id) {
        http_response_code(400);
        echo json_encode(["message" => "Missing ID for delete."]);
        return;
    }
    
    $order->id = $id;
    if ($order->delete()) {
        http_response_code(200);
        echo json_encode(["message" => "Order deleted successfully."]); 
    } else {
        http_response_code(404);
        echo json_encode(["message" => "Unable to delete order. ID not found."]);
    }
}

?>