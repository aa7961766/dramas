<?php
    /**
     * 退出登录
     */
    session_start();
    
    // 清除所有会话数据
    $_SESSION = [];
    
    // 销毁会话
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    session_destroy();
    
    // 跳转到登录页
    header('Location: /admin/login.php');
    exit;
    ?>