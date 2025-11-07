<?php

const DB_HOST = '127.127.126.5';
const DB_NAME = 'BossOfThisGym';
const DB_USER = 'root';
const DB_PASS = '';

function getDB() {
    return mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
}

function getCartSessionId() {
    if (!empty($_COOKIE['cart_session'])) {
        return $_COOKIE['cart_session'];
    }
    
    $sessionId = uniqid('cart_', true);
    setcookie('cart_session', $sessionId, time() + 86400 * 30, '/');
    return $sessionId;
}