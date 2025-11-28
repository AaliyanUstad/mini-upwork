<?php
session_start();

class Session {
    public static function set($key, $value) {
        $_SESSION[$key] = $value;
    }
    
    public static function get($key) {
        return isset($_SESSION[$key]) ? $_SESSION[$key] : null;
    }
    
    public static function destroy() {
        session_destroy();
    }
    
    public static function isLoggedIn() {
        return isset($_SESSION['user_id']);
    }
    
    public static function getUserType() {
        return isset($_SESSION['user_type']) ? $_SESSION['user_type'] : null;
    }
}
?>