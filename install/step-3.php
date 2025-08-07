<?php
/**
 * 安装步骤3：创建管理员（修复版）
 */
// 强制启动Session并处理错误
if (session_status() == PHP_SESSION_NONE) {
    $sessionStarted = session_start();
    if (!$sessionStarted) {
        die("无法启动Session，请检查服务器PHP配置中的session.save_path是否可写");
    }
}

// 调试：记录当前步骤状态
$currentStep = $_SESSION['install_step'] ?? '未设置';
error_log("step-3.php访问 - 当前install_step: {$currentStep}");

// 验证步骤合法性，增加容错处理
if ($currentStep != 2 || !isset($_SESSION['db_config'])) {
    // 检查是否从step-2.php跳转过来
    if (isset($_SERVER['HTTP_REFERER']) && strpos($_SERVER['HTTP_REFERER'], 'step-2.php') !== false) {
        // 尝试恢复步骤状态
        $_SESSION['install_step'] = 2;
    } else {
        echo '<script>alert("请先完成数据库配置步骤"); window.location="step-2.php";</script>';
        exit;
    }
}

$errors = [];
$adminData = [
    'username' => '',
    'email' => '',
    'password' => '',
    'confirm_password' => ''
];

// 表单处理
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $adminData = [
        'username' => trim($_POST['username'] ?? ''),
        'email' => trim($_POST['email'] ?? ''),
        'password' => $_POST['password'] ?? '',
        'confirm_password' => $_POST['confirm_password'] ?? ''
    ];

    // 验证表单
    if (strlen($adminData['username']) < 4) $errors[] = "用户名长度不能少于4个字符";
    if (!filter_var($adminData['email'], FILTER_VALIDATE_EMAIL)) $errors[] = "请输入有效的电子邮箱";
    if (strlen($adminData['password']) < 6) $errors[] = "密码长度不能少于6个字符";
    if ($adminData['password'] !== $adminData['confirm_password']) $errors[] = "两次输入的密码不一致";

    // 验证通过，进入下一步
    if (empty($errors)) {
        $_SESSION['admin_data'] = $adminData;
        $_SESSION['install_step'] = 3;
        
        // 调试：确认Session设置
        error_log("step-3.php提交 - 设置install_step为3，当前值: " . $_SESSION['install_step']);
        
        // 延迟跳转确保Session写入
        echo '<script>
                setTimeout(function() {
                    window.location = "step-4.php";
                }, 500);
              </script>';
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>系统安装 - 创建管理员</title>
    <link rel="stylesheet" href="/public/css/install.css">
    <link rel="stylesheet" href="/public/css/icons.css">
</head>
<body>
    <div class="install-container">
        <div class="install-header">
            <h1>图片和内容管理系统</h1>
            <p>安装向导</p>
        </div>
        
        <div class="install-progress">
            <div class="progress-bar">
                <div class="progress active" style="width: 75%"></div>
            </div>
            <div class="progress-steps">
                <div class="step active">环境检测</div>
                <div class="step active">数据库配置</div>
                <div class="step active">创建管理员</div>
                <div class="step">完成安装</div>
            </div>
        </div>
        
        <div class="install-content">
            <h2>创建管理员账户</h2>
            <p class="intro">请设置系统管理员信息，用于登录后台。</p>
            
            <?php if (!empty($errors)): ?>
            <div class="error-box mb-4">
                <h3><i class="icon icon-exclamation-circle"></i> 输入错误</h3>
                <ul><?php foreach ($errors as $error): ?><li><?= $error ?></li><?php endforeach; ?></ul>
            </div>
            <?php endif; ?>
            
            <form method="post" class="form-container">
                <div class="form-group">
                    <label for="username">管理员用户名</label>
                    <input type="text" id="username" name="username" 
                           value="<?= htmlspecialchars($adminData['username']) ?>" required>
                    <small>至少4个字符</small>
                </div>
                
                <div class="form-group">
                    <label for="email">电子邮箱</label>
                    <input type="email" id="email" name="email" 
                           value="<?= htmlspecialchars($adminData['email']) ?>" required>
                    <small>用于密码找回和系统通知</small>
                </div>
                
                <div class="form-group">
                    <label for="password">密码</label>
                    <input type="password" id="password" name="password" 
                           value="<?= htmlspecialchars($adminData['password']) ?>" required>
                    <small>至少6个字符，建议包含字母和数字</small>
                </div>
                
                <div class="form-group">
                    <label for="confirm_password">确认密码</label>
                    <input type="password" id="confirm_password" name="confirm_password" 
                           value="<?= htmlspecialchars($adminData['confirm_password']) ?>" required>
                </div>
                
                <div class="form-actions">
                    <button type="button" onclick="window.location='step-2.php'" class="btn btn-secondary">
                        <i class="icon icon-arrow-left"></i> 上一步
                    </button>
                    <button type="submit" class="btn btn-primary">
                        下一步 <i class="icon icon-arrow-right"></i>
                    </button>
                </div>
            </form>
        </div>
        
        <div class="install-footer">
            <p>&copy; 2023 图片和内容管理系统</p>
        </div>
    </div>
</body>
</html>
    