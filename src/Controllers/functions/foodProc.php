<?php 
function getAllFoods($db) {
    $sql = 'SELECT * FROM foods'; 
    $stmt = $db->prepare($sql); 
    $stmt->execute(); 
    return $stmt->fetchAll(PDO::FETCH_ASSOC); 
}

function getFood($db, $foodId) {
    $sql = 'SELECT * FROM foods WHERE id = :id';
    $stmt = $db->prepare($sql);
    $stmt->bindParam(':id', $foodId, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function getFoodsByCategory($db, $category) {
    $sql = 'SELECT * FROM foods WHERE category = :category'; 
    $stmt = $db->prepare($sql); 
    $stmt->bindParam(':category', $category, PDO::PARAM_STR);
    $stmt->execute(); 
    return $stmt->fetchAll(PDO::FETCH_ASSOC); 
}

function createOrder($db, $customer_id, $customer_name, $table_number, $order_status, $foods) {
    $db->beginTransaction();  // Start a transaction

    try {
        $orderSql = "INSERT INTO orders (customer_id, customer_name, table_number, order_status) VALUES (:customer_id, :customer_name, :table_number, :order_status)";
        $stmt = $db->prepare($orderSql);
        $stmt->execute([
            ':customer_id' => $customer_id,
            ':customer_name' => $customer_name,
            ':table_number' => $table_number,
            ':order_status' => $order_status
        ]);
        $orderId = $db->lastInsertId();

        foreach ($foods as $food) {
            $foodCheckSql = "SELECT id FROM foods WHERE id = :food_id";
            $foodCheckStmt = $db->prepare($foodCheckSql);
            $foodCheckStmt->execute([':food_id' => $food['id']]);
            if ($foodCheckStmt->rowCount() > 0) {
                $itemSql = "INSERT INTO order_items (order_id, food_id, quantity) VALUES (:order_id, :food_id, :quantity)";
                $stmt = $db->prepare($itemSql);
                $stmt->execute([
                    ':order_id' => $orderId,
                    ':food_id' => $food['id'],
                    ':quantity' => $food['quantity']
                ]);
            } else {
                error_log("Food ID " . $food['id'] . " does not exist in the foods table.");  // Log a message if food_id is invalid
                throw new Exception("Invalid food_id: " . $food['id']);
            }
        }

        $db->commit();  // Commit the transaction
        return $orderId;
    } catch (Exception $e) {
        $db->rollBack();  // Rollback the transaction on failure
        error_log('Failed to create order: ' . $e->getMessage());  // Log the detailed error message
        return false;
    }
}

function getOrderHistory($db, $userId) {
    $sql = "SELECT o.id as order_id, oi.quantity as item_quantity, f.name, f.price 
            FROM orders o
            INNER JOIN order_items oi ON o.id = oi.order_id
            INNER JOIN foods f ON oi.food_id = f.id
            WHERE o.customer_id = :userId
            ORDER BY o.created_at DESC";

    $stmt = $db->prepare($sql);
    $stmt->bindParam(':userId', $userId, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getAllOrders($db) {
    $sql = "SELECT 
                o.id as order_id, 
                o.customer_name, 
                o.table_number, 
                o.order_status, 
                oi.quantity as item_quantity, 
                f.name, 
                f.price
            FROM orders o
            JOIN order_items oi ON o.id = oi.order_id
            JOIN foods f ON oi.food_id = f.id
            ORDER BY o.created_at DESC";

    $stmt = $db->prepare($sql);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}


// Function to update order status
function updateOrderStatus($db, $orderId, $data) {
    $stmt = $db->prepare("UPDATE orders SET order_status = :status WHERE id = :id");
    $stmt->bindParam(':status', $data['order_status'], PDO::PARAM_STR);
    $stmt->bindParam(':id', $orderId, PDO::PARAM_INT);
    $stmt->execute();

    if ($stmt->rowCount()) {
        return ['message' => 'Order updated successfully'];
    } else {
        return ['error' => 'Failed to update order'];
    }
}


// Function to delete an order
function deleteOrder($db, $orderId) {
    $stmt = $db->prepare("DELETE FROM orders WHERE id = :id");
    $stmt->bindParam(':id', $orderId, PDO::PARAM_INT);
    $stmt->execute();

    if ($stmt->rowCount()) {
        return ['message' => 'Order deleted successfully'];
    } else {
        return ['error' => 'Failed to delete order'];
    }
}