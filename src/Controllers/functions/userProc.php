<?php
function createUser($db, $name, $email, $password, $phone, $address, $isAdmin) {
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    $sql = 'INSERT INTO customers (name, email, password, phone, address, isAdmin) VALUES (:name, :email, :password, :phone, :address, :isAdmin)';
    $stmt = $db->prepare($sql);
    $stmt->bindParam(':name', $name, PDO::PARAM_STR);
    $stmt->bindParam(':email', $email, PDO::PARAM_STR);
    $stmt->bindParam(':password', $hashedPassword, PDO::PARAM_STR);
    $stmt->bindParam(':phone', $phone, PDO::PARAM_STR);
    $stmt->bindParam(':address', $address, PDO::PARAM_STR);
    $stmt->bindParam(':isAdmin', $isAdmin, PDO::PARAM_INT);
    if ($stmt->execute()) {
        return $db->lastInsertId();
    } else {
        error_log("Failed to insert user: " . implode(", ", $stmt->errorInfo()));
        return false;
    }
}

function findUserByEmail($db, $email) {
    $sql = 'SELECT * FROM customers WHERE email = :email';
    $stmt = $db->prepare($sql);
    $stmt->bindParam(':email', $email, PDO::PARAM_STR);
    $stmt->execute();
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

session_start();

function loginUser($db, $email, $password) {
    $user = findUserByEmail($db, $email);
    if ($user) {
        if (password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id']; 
            $_SESSION['email'] = $user['email']; 
            $_SESSION['token'] = bin2hex(random_bytes(32));

            return [
                'token' => $_SESSION['token'],
                'user' => $user,
                'isAdmin' => (int)$user['isAdmin']
            ];
        } else {
            // Password does not match
            return ['error' => 'Invalid password'];
        }
    } else {
        // Email does not exist in the database
        return ['error' => 'Invalid email'];
    }
}




function updatePassword($db, $userId, $newPassword) {
    $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
    $sql = 'UPDATE customers SET password = :password WHERE id = :id';
    $stmt = $db->prepare($sql);
    $stmt->bindParam(':password', $hashedPassword, PDO::PARAM_STR);
    $stmt->bindParam(':id', $userId, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->rowCount() > 0;
}

