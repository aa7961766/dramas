<?php
/**
 * 安装步骤4：完成安装（修复表已存在问题）
 */
// 强制启动Session并处理错误
if (session_status() == PHP_SESSION_NONE) {
    $sessionStarted = session_start();
    if (!$sessionStarted) {
        die("无法启动Session，请检查服务器PHP配置中的session.save_path是否可写");
    }
}

$currentStep = $_SESSION['install_step'] ?? '未设置';
error_log("step-4.php访问 - 当前install_step: {$currentStep}");

if ($currentStep != 3 || !isset($_SESSION['db_config']) || !isset($_SESSION['admin_data'])) {
    if (isset($_SERVER['HTTP_REFERER']) && strpos($_SERVER['HTTP_REFERER'], 'step-3.php') !== false) {
        $_SESSION['install_step'] = 3;
    } else {
        echo '<script>alert("请先完成管理员创建步骤"); window.location="step-3.php";</script>';
        exit;
    }
}

$dbConfig = $_SESSION['db_config'];
$adminData = $_SESSION['admin_data'];
$success = false;
$message = '';

// 执行安装
if ($_SERVER['REQUEST_METHOD'] === 'POST' || !isset($_POST['confirm'])) {
    try {
        // 1. 连接数据库
        $dsn = "mysql:host={$dbConfig['host']};port={$dbConfig['port']};dbname={$dbConfig['database']};charset=utf8mb4";
        $pdo = new PDO($dsn, $dbConfig['username'], $dbConfig['password'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]);

        // 2. 执行数据库脚本
        $sqlFile = __DIR__ . '/database.sql';
        if (!file_exists($sqlFile)) throw new Exception("数据库脚本不存在：{$sqlFile}");
        
        $sql = file_get_contents($sqlFile);
        $pdo->exec($sql);

        // 3. 创建管理员账户（检查是否已存在）
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = :username LIMIT 1");
        $stmt->execute([':username' => $adminData['username']]);
        $existingUser = $stmt->fetch();

        if ($existingUser) {
            // 如果管理员已存在，更新密码
            $passwordHash = password_hash($adminData['password'], PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("
                UPDATE users SET 
                    email = :email, 
                    password = :password, 
                    role = 'admin',
                    updated_at = NOW() 
                WHERE id = :id
            ");
            $stmt->execute([
                ':email' => $adminData['email'],
                ':password' => $passwordHash,
                ':id' => $existingUser['id']
            ]);
            $message .= "管理员账户已存在，已更新密码和信息；";
        } else {
            // 全新创建管理员
            $passwordHash = password_hash($adminData['password'], PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("
                INSERT INTO users (username, email, password, role, created_at, updated_at)
                VALUES (:username, :email, :password, 'admin', NOW(), NOW())
            ");
            $stmt->execute([
                ':username' => $adminData['username'],
                ':email' => $adminData['email'],
                ':password' => $passwordHash
            ]);
            $message .= "管理员账户创建成功；";
        }

        // 4. 生成配置文件（保持不变）
        $configDir = dirname(__DIR__) . '/config';
        if (!file_exists($configDir)) mkdir($configDir, 0755, true);
        
        $configContent = "<?php\nreturn [\n";
        $configContent .= "    'host' => '{$dbConfig['host']}',\n";
        $configContent .= "    'port' => '{$dbConfig['port']}',\n";
        $configContent .= "    'database' => '{$dbConfig['database']}',\n";
        $configContent .= "    'username' => '{$dbConfig['username']}',\n";
        $configContent .= "    'password' => '{$dbConfig['password']}',\n";
        $configContent .= "    'charset' => 'utf8mb4',\n";
        $configContent .= "    'prefix' => ''\n];\n";
        
        $configFile = $configDir . '/database.php';
        file_put_contents($configFile, $configContent);

        // 5. 生成环境变量文件（保持不变）
        $envContent = "APP_NAME=图片和内容管理系统\n";
        $envContent .= "APP_ENV=production\n";
        $envContent .= "APP_DEBUG=false\n";
        $envContent .= "APP_KEY=" . bin2hex(random_bytes(16)) . "\n\n";
        $envContent .= "DB_CONNECTION=mysql\n";
        $envContent .= "DB_HOST={$dbConfig['host']}\n";
        $envContent .= "DB_PORT={$dbConfig['port']}\n";
        $envContent .= "DB_DATABASE={$dbConfig['database']}\n";
        $envContent .= "DB_USERNAME={$dbConfig['username']}\n";
        $envContent .= "DB_PASSWORD={$dbConfig['password']}\n";
        
        file_put_contents(dirname(__DIR__) . '/.env', $envContent);

        // 6. 创建安装锁定文件
        file_put_contents($configDir . '/installed.lock', date('Y-m-d H:i:s') . " - 安装完成\n");

        // 7. 安装成功
        $success = true;
        $message .= "系统安装完成！";
        unset($_SESSION['install_step'], $_SESSION['db_config'], $_SESSION['admin_data']);

    } catch (Exception $e) {
        $success = false;
        $message = "安装失败：{$e->getMessage()}";
    }
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>系统安装 - 完成安装</title>
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
                <div class="progress active" style="width: <?= $success ? '100%' : '75%' ?>"></div>
            </div>
            <div class="progress-steps">
                <div class="step active">环境检测</div>
                <div class="step active">数据库配置</div>
                <div class="step active">创建管理员</div>
                <div class="step <?= $success ? 'active' : '' ?>">完成安装</div>
            </div>
        </div>
        
        <div class="install-content">
            <?php if (!$success): ?>
                <h2>准备完成安装</h2>
                <p class="intro">系统将执行以下操作：</p>
                
                <div class="install-tasks">
                    <ul>
                        <li><i class="icon icon-check-circle"></i> 创建数据库表结构</li>
                        <li><i class="icon icon-check-circle"></i> 添加管理员账户</li>
                        <li><i class="icon icon-check-circle"></i> 生成系统配置文件</li>
                        <li><i class="icon icon-check-circle"></i> 完成安装并锁定</li>
                    </ul>
                </div>
                
                <?php if (!empty($message)): ?>
                <div class="error-box mb-4">
                    <h3><i class="icon icon-exclamation-circle"></i> 安装失败</h3>
                    <p><?= $message ?></p>
                    <button type="button" onclick="window.location.reload()" class="btn btn-primary">
                        重试安装
                    </button>
                </div>
                <?php else: ?>
                <form method="post" class="form-actions">
                    <button type="button" onclick="window.location='step-3.php'" class="btn btn-secondary">
                        <i class="icon icon-arrow-left"></i> 上一步
                    </button>
                    <button type="submit" name="confirm" value="1" class="btn btn-primary">
                        确认安装 <i class="icon icon-check"></i>
                    </button>
                </form>
                <?php endif; ?>
                
            <?php else: ?>
                <div class="success-box text-center">
                    <div class="success-icon">
                        <i class="icon icon-check-circle"></i>
                    </div>
                    <h2>安装完成！</h2>
                    <p class="intro"><?= $message ?></p>
                    
                    <div class="install-summary">
                        <h3>安装信息</h3>
                        <p><strong>管理员账户：</strong><?= htmlspecialchars($adminData['username']) ?></p>
                        <p><strong>登录地址：</strong> <a href="/admin" target="_blank"><?= $_SERVER['HTTP_HOST'] ?>/admin</a></p>
                    </div>
                    
                    <div class="security-note">
                        <h4><i class="icon icon-shield"></i> 安全提示</h4>
                        <p>建议删除服务器上的 <code>install</code> 目录</p>
                    </div>
                    
                    <a href="/admin" class="btn btn-primary btn-large">
                        进入管理后台 <i class="icon icon-arrow-right"></i>
                    </a>
                </div>
            <?php endif; ?>
        </div>
        
        <div class="install-footer">
            <p>&copy; 2023 图片和内容管理系统</p>
        </div>
    </div>
</body>
</html>
    