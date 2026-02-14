<?php
session_start();
include '../config/database.php';

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function isAdmin() {
    return isset($_SESSION['role']) && $_SESSION['role'] == 'admin';
}

function isPembina() {
    return isset($_SESSION['role']) && $_SESSION['role'] == 'pembina';
}

function redirect($url) {
    header("Location: $url");
    exit();
}
?>