<?php
/**
 * 管理员登录页面
 */
session_start();

// 检查是否已登录
if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    header('Location: index.php');
    exit;
}

$error = '';

// 处理登录提交
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');
    
    // 验证输入
    if (empty($username) || empty($password)) {
        $error = '请输入用户名和密码';
    } else {
        try {
            // 引入数据库类（修复路径问题）
            require_once dirname(__DIR__) . '/core/Db.php';
            
            // 获取数据库实例
            $db = Db::getInstance();
            
            // 查询用户
            $user = $db->fetch(
                "SELECT * FROM users WHERE username = :username AND role = 'admin' LIMIT 1",
                [':username' => $username]
            );
            
            // 验证用户和密码
            if ($user && password_verify($password, $user['password'])) {
                // 登录成功
                $_SESSION['admin_logged_in'] = true;
                $_SESSION['admin_user'] = $user['username'];
                $_SESSION['admin_id'] = $user['id'];
                
                header('Location: index.php');
                exit;
            } else {
                $error = '用户名或密码错误';
            }
            
        } catch (Exception $e) {
            $error = '登录失败: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>管理员登录 - 短剧网站</title>
    <link rel="stylesheet" href="/public/css/login.css">
    <link rel="stylesheet" href="/public/css/icons.css">
</head>
<body class="login-body">
    <div class="login-container">
        <div class="login-header">
            <h1>短剧网站管理后台</h1>
            <p>请输入管理员账号和密码</p>
        </div>
        
        <?php if (!empty($error)): ?>
        <div class="error-message">
            <i class="icon icon-exclamation-circle"></i> <?= $error ?>
        </div>
        <?php endif; ?>
        
        <form method="post" class="login-form">
            <div class="form-group">
                <label for="username">用户名</label>
                <div class="input-icon">
                    <i class="icon icon-user"></i>
                    <input type="text" id="username" name="username" required autofocus>
                </div>
            </div>
            
            <div class="form-group">
                <label for="password">密码</label>
                <div class="input-icon">
                    <i class="icon icon-lock"></i>
                    <input type="password" id="password" name="password" required>
                </div>
            </div>
            
            <button type="submit" class="btn-login">登录</button>
        </form>
    </div>
</body>
</html>
