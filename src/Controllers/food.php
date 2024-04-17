<?php 

use Slim\Http\Request; 
use Slim\Http\Response; 

include __DIR__ .'/functions/foodProc.php';
include __DIR__ .'/functions/userProc.php';

$app->options('/{routes:.+}', function ($request, $response, $args) {
    return $response
        ->withHeader('Access-Control-Allow-Origin', '*')
        ->withHeader('Access-Control-Allow-Headers', 'X-Requested-With, Content-Type, Accept, Origin, Authorization')
        ->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS');
});

$app->add(function ($req, $res, $next) {
    $response = $next($req, $res);
    return $response
        ->withHeader('Access-Control-Allow-Origin', '*')
        ->withHeader('Access-Control-Allow-Credentials', 'true')
        ->withHeader('Access-Control-Allow-Headers', 'X-Requested-With, Content-Type, Accept, Origin, Authorization, x-access-token') // Include 'x-access-token' here
        ->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS');
});


// FOR FOOD

// List all foods or filter by category
$app->get('/foods', function (Request $request, Response $response, array $args) {
    $queryParams = $request->getQueryParams(); // Get query params from URL
    $category = $queryParams['category'] ?? null; // Null if not set
    
    if ($category) {
        $data = getFoodsByCategory($this->db, $category); 
    } else {
        $data = getAllFoods($this->db); 
    }

    return $response->withJson(array('data' => $data), 200); 
});


// Get a single food item by id
$app->get('/food/[{id}]', function ($request, $response, $args){   
    $foodId = $args['id']; 
    $data = getFood($this->db, $foodId); 
    if (empty($data)) { 
        return $response->withJson(array('error' => 'no data'), 404); 
    } 
    return $response->withJson(array('data' => $data), 200);
});

$app->post('/orders/create', function (Request $request, Response $response, array $args) {
    $body = $request->getParsedBody();
    error_log(print_r($body, true));  // Log incoming request data

    if (!isset($body['id']) || !isset($body['cart'])) {  // Changed from 'userId' to 'id'
        return $response->withJson(['error' => 'Missing required fields'], 400);
    }

    $customer_id = $body['id'];  // Changed from 'userId' to 'id'
    $customer_name = $body['customerName'];
    $table_number = $body['tableNumber'];
    $foods = $body['cart']['products'];
    $order_status = 'placed';

    $orderId = createOrder($this->db, $customer_id, $customer_name, $table_number, $order_status, $foods);

    if ($orderId) {
        return $response->withJson(['orderId' => $orderId], 201);
    } else {
        return $response->withJson(['error' => 'Order creation failed'], 500);
    }
});

// Get the order history for a user
$app->get('/orders', function (Request $request, Response $response, array $args) {
    $queryParams = $request->getQueryParams();
    $userId = $queryParams['userId'] ?? null;

    if ($userId) {
        $orders = getOrderHistory($this->db, $userId);
    } else {
        // If no userId is provided, fetch all orders
        $orders = getAllOrders($this->db);
    }

    if ($orders) {
        return $response->withJson(['data' => $orders], 200);
    } else {
        return $response->withJson(['error' => ['message' => 'No orders found']], 404);
    }
});




$app->post('/register', function ($request, $response, $args) {
    $body = $request->getParsedBody();
    $name = $body['name'];
    $email = $body['email'];
    $password = $body['password'];
    $phone = $body['phone'];
    $address = $body['address'];
    $isAdmin = $body['isAdmin'] ?? 0;  // Correctly taking the default as 0

    $userId = createUser($this->db, $name, $email, $password, $phone, $address, $isAdmin);
    if ($userId) {
        return $response->withJson(['userId' => $userId, 'isAdmin' => $isAdmin], 201);
    } else {
        return $response->withJson(['error' => 'User creation failed'], 500);
    }
});



$app->post('/login', function ($request, $response, $args) {
    $body = $request->getParsedBody();
    $email = $body['email'];
    $password = $body['password'];

    $loginResult = loginUser($this->db, $email, $password);
    
    if (!empty($loginResult['token'])) {
        // Login successful, send the user and token
        return $response->withJson(['user' => $loginResult['user'], 'token' => $loginResult['token']], 200);
    } else {
        // Login failed, send the error
        return $response->withJson(['error' => $loginResult['error']], 401);
    }
});

// Endpoint to reset the password without sending an email
$app->put('/users/{id}/reset-password', function ($request, $response, $args) {
    $userId = $args['id'];
    $body = $request->getParsedBody();
    $newPassword = $body['newPassword'];
    
    if (!$userId || !$newPassword) {
        return $response->withJson(['error' => 'Invalid request, user ID and password required'], 400);
    }
    
    $result = updatePassword($this->db, $userId, $newPassword);
    
    if ($result) {
        return $response->withJson(['message' => 'Password updated successfully'], 200);
    } else {
        return $response->withJson(['error' => 'Failed to update password'], 500);
    }
});

$app->put('/orders/{id}', function (Request $request, Response $response, array $args) {
    $orderId = $args['id'];
    $body = $request->getParsedBody();
    error_log(print_r($body, true)); // Log the body to see what is received
    return updateOrderStatus($this->db, $orderId, $body);
});



$app->delete('/orders/{id}', function ($request, $response, $args) {
    $orderId = $args['id'];
    return deleteOrder($this->db, $orderId);
});



