<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-API-Key");

include_once 'config/Database.php';
include_once 'config/Auth.php';
include_once 'models/OrderModel.php';

$database = new Database();
$db = $database->getConnection();
$orderModel = new OrderModel($db);

function sendResponse($status, $data) {
    http_response_code($status);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function getJsonBody() {
    $data = file_get_contents("php://input");
    $decoded = json_decode($data, true);
    return is_array($decoded) ? $decoded : [];
}

$auth = new Auth($db);
if ($_SERVER['REQUEST_METHOD'] !== 'OPTIONS') {
    $auth->authenticate(); 
} else {
    http_response_code(200);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];

$request_uri = trim($_SERVER['REQUEST_URI'], '/');
$project_base_path = 'ipr_2';

if (strpos($request_uri, $project_base_path) === 0) {
    $request_uri = substr($request_uri, strlen($project_base_path)); 
}

$request_uri_parts = explode('/', trim($request_uri, '/'));

if (empty($request_uri_parts[0]) || $request_uri_parts[0] !== 'api') {
    sendResponse(404, array("message" => "Unknown URL."));
}

$entity = isset($request_uri_parts[1]) ? $request_uri_parts[1] : '';
$id = isset($request_uri_parts[2]) ? (int)$request_uri_parts[2] : null;

if ($entity !== 'orders') {
    sendResponse(404, array("message" => "URL not found: use /ipr_2/api/orders"));
}

$valid_statuses = ['new', 'processing', 'shipped', 'delivered', 'cancelled'];

switch ($method) {
    case 'GET':
        if ($id) {
            $order = $orderModel->readOne($id);
            if ($order) {
                sendResponse(200, $order);
            } else {
                sendResponse(404, array("message" => "Order not found"));
            }
        } else {
            $orders = $orderModel->readAll();
            sendResponse(200, $orders);
        }
        break;

    case 'POST':
        $data = getJsonBody();
        $user_id = filter_var($data['user_id'] ?? null, FILTER_VALIDATE_INT);
        $total_amount_str = filter_var($data['total_amount'] ?? null, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
        $total_amount = is_numeric($total_amount_str) ? (float)$total_amount_str : false;
        $status = $data['status'] ?? 'new'; 
        
        if (!$user_id || $user_id <= 0 || $total_amount === false || $total_amount <= 0 || !in_array($status, $valid_statuses)) {
            sendResponse(400, array("message" => "Incorrect body: user_id (INT > 0), total_amount (DECIMAL > 0), status (valid)."));
        }
        
        $new_id = $orderModel->create($user_id, $total_amount, $status);
        if ($new_id) {
            sendResponse(201, array("message" => "Order created", "id" => $new_id));
        } else {
            sendResponse(500, array("message" => "DB Error: can not create order"));
        }
        break;

    case 'PUT':
        if (!$id) {
            sendResponse(400, array("message" => "You must set /:order_id in URL "));
        }
        
        $data = getJsonBody();
        $update_data = [];
        $is_valid = true;
        
        if (isset($data['user_id'])) {
            $user_id = filter_var($data['user_id'], FILTER_VALIDATE_INT);
            if ($user_id && $user_id > 0) {
                $update_data['user_id'] = $user_id;
            } else {
                $is_valid = false;
                sendResponse(400, array("message" => "Incorrect user_id."));
            }
        }
        
        if ($is_valid && isset($data['total_amount'])) {
            $total_amount_str = filter_var($data['total_amount'], FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
            $total_amount = is_numeric($total_amount_str) ? (float)$total_amount_str : false;
            
            if ($total_amount !== false && $total_amount >= 0) {
                $update_data['total_amount'] = $total_amount;
            } else {
                $is_valid = false;
                sendResponse(400, array("message" => "Incorrect total_amount."));
            }
        }
        
        // Валидация status
        if ($is_valid && isset($data['status'])) {
            if (in_array($data['status'], $valid_statuses)) {
                $update_data['status'] = $data['status'];
            } else {
                $is_valid = false;
                sendResponse(400, array("message" => "Incorrect status."));
            }
        }

        if (!$is_valid) {
            break;
        }

        if (empty($update_data)) {
            sendResponse(400, array("message" => "Body for update don't exist"));
        }
        
        $rows_affected = $orderModel->update($id, $update_data);
        
        if ($rows_affected !== false) {
             if ($rows_affected > 0) {
                 sendResponse(200, array("message" => "Order updated"));
             } else {
                 // Проверяем, существует ли ID
                 if ($orderModel->readOne($id)) {
                      sendResponse(200, array("message" => "Update error: data is identical or not found changing fields"));
                 } else {
                      sendResponse(404, array("message" => "Order not found"));
                 }
             }
        } else {
            sendResponse(500, array("message" => "DB Error: can not update order"));
        }
        break;

    case 'DELETE':
        if (!$id) {
            sendResponse(400, array("message" => "You must set /:order_id in URL "));
        }
        
        $rows_deleted = $orderModel->delete($id);
        
        if ($rows_deleted > 0) {
            sendResponse(200, array("message" => "Orderd deleted"));
        } else {
            sendResponse(404, array("message" => "Order not found"));
        }
        break;

    default:
        sendResponse(405, array("message" => "Method not allowed"));
        break;
}
?>