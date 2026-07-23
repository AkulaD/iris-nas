<?php
session_start();
require_once '../config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    if (empty($username) || empty($email) || empty($password) || empty($confirm_password)) {
        $_SESSION['error'] = "All fields are required.";
        header("Location: ../register.php");
        exit();
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['error'] = "Invalid email format.";
        header("Location: ../register.php");
        exit();
    }

    if ($password !== $confirm_password) {
        $_SESSION['error'] = "Passwords do not match.";
        header("Location: ../register.php");
        exit();
    }

    if (strlen($password) < 8) {
        $_SESSION['error'] = "Password must be at least 8 characters long.";
        header("Location: ../register.php");
        exit();
    }

    try {
        $stmt = $koneksi->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $_SESSION['error'] = "Email is already registered.";
            header("Location: ../register.php");
            exit();
        }

        $date_part = date('YmdHis');
        $special_num = rand(10, 99);
        $characters = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $random_char = '';
        for ($i = 0; $i < 8; $i++) {
            $random_char .= $characters[rand(0, strlen($characters) - 1)];
        }
        $id = $date_part . '-' . $special_num . '-' . $random_char;

        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        $stmt = $koneksi->prepare("INSERT INTO users (id, username, email, password) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssss", $id, $username, $email, $hashed_password);
        $stmt->execute();

        $_SESSION['success'] = "Registration successful! You can now login.";
        header("Location: ../register.php");
        exit();

    } catch (mysqli_sql_exception $e) {
        $_SESSION['error'] = "Database error occurred. Please try again.";
        header("Location: ../register.php");
        exit();
    }
} else {
    header("Location: ../register.php");
    exit();
}