<?php
/**
 * 安装步骤1：环境检测（修复Session传递问题）
 */
// 强制启动Session并处理错误
if (session_status() == PHP_SESSION_NONE) {
    $sessionStarted = session_start();
    if (!$sessionStarted) {
        die("无法启动Session，请检查服务器PHP配置中的session.save_path是否可写");
    }
}

// 调试：显示当前Session状态（实际部署可删除）
error_log("step-1.php访问 - 当前install_step: " . ($_SESSION['install_step'] ?? '未设置'));

$errors = [];
$checkDirs = [
    'public' => dirname(__DIR__) . '/public',
    'uploads' => dirname(__DIR__) . '/public/uploads',
    'storage' => dirname(__DIR__) . '/storage',
    'session' => session_save_path() ?: sys_get_temp_dir()
];

// 环境检测逻辑
$phpVersion = PHP_VERSION;
$phpVersionOk = version_compare($phpVersion, '7.0.0') >= 0;
if (!$phpVersionOk) $errors[] = "PHP版本需≥7.0，当前版本：{$phpVersion}";

$pdoOk = extension_loaded('pdo_mysql');
if (!$pdoOk) $errors[] = "未安装PDO MySQL扩展";

$gdOk = extension_loaded('gd');
if (!$gdOk) $errors[] = "未安装GD扩展（用于图片处理）";

$fileinfoOk = extension_loaded('fileinfo');
if (!$fileinfoOk) $errors[] = "未安装fileinfo扩展";

// 目录权限检测
foreach ($checkDirs as $name => $path) {
    if (!file_exists($path) && $name !== 'session') {
        $created = mkdir($path, 0755, true);
        if (!$created) $errors[] = "{$name}目录创建失败，路径：{$path}";
    }
    if (!is_writable($path)) {
        $errors[] = "{$name}目录不可写（影响安装流程），路径：{$path}";
    }
}

$canProceed = empty($errors);

// 表单提交处理（重点修复Session设置）
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($canProceed) {
        // 强制设置Session值
        $_SESSION['install_step'] = 1;
        
        // 调试：确认Session已设置（实际部署可删除）
        error_log("step-1.php提交 - 设置install_step为1，当前值: " . $_SESSION['install_step']);
        
        // 延迟跳转确保Session写入
        echo '<script>
                // 短暂延迟确保Session保存
                setTimeout(function() {
                    window.location = "step-2.php";
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
    <title>系统安装 - 环境检测</title>
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
                <div class="progress active" style="width: 25%"></div>
            </div>
            <div class="progress-steps">
                <div class="step active">环境检测</div>
                <div class="step">数据库配置</div>
                <div class="step">创建管理员</div>
                <div class="step">完成安装</div>
            </div>
        </div>
        
        <div class="install-content">
            <h2>环境检测</h2>
            <p class="intro">系统正在检测服务器环境是否满足安装要求...</p>
            
            <div class="check-list">
                <div class="check-item <?= $phpVersionOk ? 'success' : 'error' ?>">
                    <span class="item-name">PHP版本 ≥ 7.0</span>
                    <span class="item-status"><?= $phpVersionOk ? '通过' : '不通过' ?></span>
                    <span class="item-details"><?= $phpVersion ?></span>
                </div>
                <div class="check-item <?= $pdoOk ? 'success' : 'error' ?>">
                    <span class="item-name">PDO MySQL扩展</span>
                    <span class="item-status"><?= $pdoOk ? '通过' : '不通过' ?></span>
                </div>
                <div class="check-item <?= $gdOk ? 'success' : 'error' ?>">
                    <span class="item-name">GD扩展 (图片处理)</span>
                    <span class="item-status"><?= $gdOk ? '通过' : '不通过' ?></span>
                </div>
                <div class="check-item <?= $fileinfoOk ? 'success' : 'error' ?>">
                    <span class="item-name">fileinfo扩展</span>
                    <span class="item-status"><?= $fileinfoOk ? '通过' : '不通过' ?></span>
                </div>
                <?php foreach ($checkDirs as $name => $path): ?>
                <?php if ($name !== 'session'): ?>
                <div class="check-item <?= is_writable($path) ? 'success' : 'error' ?>">
                    <span class="item-name"><?= ucfirst($name) ?>目录可写</span>
                    <span class="item-status"><?= is_writable($path) ? '通过' : '不通过' ?></span>
                </div>
                <?php endif; ?>
                <?php endforeach; ?>
            </div>
            
            <?php if (!empty($errors)): ?>
            <div class="error-box">
                <h3><i class="icon icon-exclamation-circle"></i> 检测未通过</h3>
                <p>请解决以下问题后重试：</p>
                <ul><?php foreach ($errors as $error): ?><li><?= $error ?></li><?php endforeach; ?></ul>
                <button type="button" onclick="window.location.reload()" class="btn btn-primary">
                    重新检测
                </button>
            </div>
            <?php else: ?>
            <div class="success-box">
                <h3><i class="icon icon-check-circle"></i> 环境检测通过</h3>
                <p>服务器环境满足安装要求，可以继续。</p>
                <form method="post">
                    <button type="submit" class="btn btn-primary">
                        下一步 <i class="icon icon-arrow-right"></i>
                    </button>
                </form>
            </div>
            <?php endif; ?>
        </div>
        
        <div class="install-footer">
            <p>&copy; 2023 图片和内容管理系统</p>
        </div>
    </div>
</body>
</html>
    