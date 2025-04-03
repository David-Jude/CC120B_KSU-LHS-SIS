<?php
require_once 'db_connect.php';
require_once 'functions.php';

// Check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Redirect if not logged in
function checkAuth() {
    if (!isLoggedIn()) {
        header("Location: ../login.php");
        exit();
    }
}

// Redirect if user doesn't have required role
function checkRole($requiredRole) {
    checkAuth();
    if ($_SESSION['role'] != $requiredRole) {
        header("Location: ../unauthorized.php");
        exit();
    }
}

// Login function
function login($username, $password) {
    global $conn;
    
    $stmt = $conn->prepare("SELECT user_id, username, password, role, status FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows == 1) {
        $user = $result->fetch_assoc();
        
        // Verify password
        if (password_verify($password, $user['password'])) {
            if ($user['status'] == 'active') {
                // Set session variables
                $_SESSION['user_id'] = $user['user_id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role'] = $user['role'];
                
                // Log activity
                logActivity($user['user_id'], "User logged in");
                
                return true;
            } else {
                return "Account is not active. Please contact administrator.";
            }
        }
    }
    
    return "Invalid username or password.";
}

// Logout function
function logout() {
    // Log activity before destroying session
    if (isset($_SESSION['user_id'])) {
        logActivity($_SESSION['user_id'], "User logged out");
    }
    
    // Unset all session variables
    $_SESSION = array();
    
    // Destroy the session
    session_destroy();
}

// Register new user
function registerUser($username, $password, $email, $role, $firstName, $lastName) {
    global $conn;
    
    // Check if username or email exists
    $stmt = $conn->prepare("SELECT user_id FROM users WHERE username = ? OR email = ?");
    $stmt->bind_param("ss", $username, $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        return "Username or email already exists.";
    }
    
    // Hash password
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    
    // Insert user
    $stmt = $conn->prepare("INSERT INTO users (username, password, email, role) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssss", $username, $hashedPassword, $email, $role);
    
    if ($stmt->execute()) {
        $userId = $stmt->insert_id;
        
        // Insert profile
        $stmt = $conn->prepare("INSERT INTO user_profiles (user_id, first_name, last_name) VALUES (?, ?, ?)");
        $stmt->bind_param("iss", $userId, $firstName, $lastName);
        $stmt->execute();
        
        // If student, insert into students table
        if ($role == 'student') {
            $stmt = $conn->prepare("INSERT INTO students (user_id, grade_level, school_year) VALUES (?, 7, '2023-2024')");
            $stmt->bind_param("i", $userId);
            $stmt->execute();
        }
        
        logActivity($userId, "User registered");
        return true;
    }
    
    return "Registration failed. Please try again.";
}
?>