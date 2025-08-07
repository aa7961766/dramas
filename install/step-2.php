<?php
/**
 * 安装步骤2：数据库配置（调整验证逻辑）
 */
// 强制启动Session并处理错误
if (session_status() == PHP_SESSION_NONE) {
    $sessionStarted = session_start();
    if (!$sessionStarted) {
        die("无法启动Session，请检查服务器PHP配置中的session.save_path是否可写");
    }
}

// 调整验证逻辑，增加容错
$currentStep = $_SESSION['install_step'] ?? null;

// 调试：查看当前Session状态（实际部署可删除）
error_log("step-2.php访问 - 当前install_step: " . $currentStep);

// 更宽松的验证逻辑，允许直接访问时手动设置步骤（仅安装阶段临时使用）
if ($currentStep != 1) {
    // 尝试从step-1.php的提交中恢复
    if (isset($_SERVER['HTTP_REFERER']) && strpos($_SERVER['HTTP_REFERER'], 'step-1.php') !== false) {
        $_SESSION['install_step'] = 1;
        $currentStep = 1;
    } else {
        echo '<script>alert("请先完成环境检测步骤"); window.location="step-1.php";</script>';
        exit;
    }
}

// 后续代码保持不变...
$errors = [];
$dbConfig = [
    'host' => 'localhost',
    'port' => '3306',
    'database' => '',
    'username' => '',
    'password' => ''
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $dbConfig = array_merge($dbConfig, [
        'host' => $_POST['db_host'] ?? 'localhost',
        'port' => $_POST['db_port'] ?? '3306',
        'database' => trim($_POST['db_name'] ?? ''),
        'username' => trim($_POST['db_user'] ?? ''),
        'password' => $_POST['db_pass'] ?? ''
    ]);

    if (empty($dbConfig['database'])) $errors[] = "请输入数据库名称";
    if (empty($dbConfig['username'])) $errors[] = "请输入数据库用户名";

    if (empty($errors)) {
        try {
            $dsn = "mysql:host={$dbConfig['host']};port={$dbConfig['port']};charset=utf8mb4";
            $pdo = new PDO($dsn, $dbConfig['username'], $dbConfig['password'], [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_TIMEOUT => 5
            ]);

            try {
                $pdo->exec("USE {$dbConfig['database']}");
            } catch (PDOException $e) {
                $pdo->exec("CREATE DATABASE {$dbConfig['database']} CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
                $pdo->exec("USE {$dbConfig['database']}");
            }

            $_SESSION['db_config'] = $dbConfig;
            $_SESSION['install_step'] = 3;
            
            echo '<script>setTimeout(function(){window.location="step-3.php";}, 500);</script>';
            exit;

        } catch (PDOException $e) {
            $errors[] = "数据库连接失败：{$e->getMessage()}";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>系统安装 - 数据库配置</title>
    <link rel="stylesheet" href="/public/css/install.css">
    <link rel="stylesheet" href="/public/css/icons.css">
</head>
<body>
    <!-- 页面内容保持不变 -->
    <div class="install-container">
        <div class="install-header">
            <h1>图片和内容管理系统</h1>
            <p>安装向导</p>
        </div>
        
        <div class="install-progress">
            <div class="progress-bar">
                <div class="progress active" style="width: 50%"></div>
            </div>
            <div class="progress-steps">
                <div class="step active">环境检测</div>
                <div class="step active">数据库配置</div>
                <div class="step">创建管理员</div>
                <div class="step">完成安装</div>
            </div>
        </div>
        
        <div class="install-content">
            <h2>数据库配置</h2>
            <p class="intro">请输入MySQL数据库信息，系统将自动创建数据表。</p>
            
            <?php if (!empty($errors)): ?>
            <div class="error-box mb-4">
                <h3><i class="icon icon-exclamation-circle"></i> 配置错误</h3>
                <ul><?php foreach ($errors as $error): ?><li><?= $error ?></li><?php endforeach; ?></ul>
            </div>
            <?php endif; ?>
            
            <form method="post" class="form-container">
                <div class="form-group">
                    <label for="db_host">数据库主机</label>
                    <input type="text" id="db_host" name="db_host" 
                           value="<?= htmlspecialchars($dbConfig['host']) ?>" required>
                    <small>通常为 localhost 或 127.0.0.1</small>
                </div>
                
                <div class="form-group">
                    <label for="db_port">数据库端口</label>
                    <input type="text" id="db_port" name="db_port" 
                           value="<?= htmlspecialchars($dbConfig['port']) ?>" required>
                    <small>MySQL默认端口：3306</small>
                </div>
                
                <div class="form-group">
                    <label for="db_name">数据库名称</label>
                    <input type="text" id="db_name" name="db_name" 
                           value="<?= htmlspecialchars($dbConfig['database']) ?>" required>
                    <small>系统将使用或创建该数据库</small>
                </div>
                
                <div class="form-group">
                    <label for="db_user">数据库用户名</label>
                    <input type="text" id="db_user" name="db_user" 
                           value="<?= htmlspecialchars($dbConfig['username']) ?>" required>
                    <small>需有创建数据库和表的权限</small>
                </div>
                
                <div class="form-group">
                    <label for="db_pass">数据库密码</label>
                    <input type="password" id="db_pass" name="db_pass" 
                           value="<?= htmlspecialchars($dbConfig['password']) ?>">
                    <small>无密码请留空</small>
                </div>
                
                <div class="form-actions">
                    <button type="button" onclick="window.location='step-1.php'" class="btn btn-secondary">
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
    