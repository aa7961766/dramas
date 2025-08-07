<?php
/**
 * 应用入口文件
 */

// 定义基础路径
define('BASE_PATH', dirname(__DIR__));
define('APP_ENV', getenv('APP_ENV') ?: 'production');

// 检查是否已安装
if (!file_exists(BASE_PATH . '/config/installed.lock') && 
    strpos($_SERVER['REQUEST_URI'], '/install') === false) {
    header('Location: /install');
    exit;
}

// 加载Composer自动加载
require_once BASE_PATH . '/vendor/autoload.php';

// 加载环境变量
$dotenv = Dotenv\Dotenv::createImmutable(BASE_PATH);
$dotenv->load();

// 初始化日志
$log = new Monolog\Logger('admin-panel');
$log->pushHandler(new Monolog\Handler\StreamHandler(BASE_PATH . '/storage/logs/app.log', 
    APP_ENV === 'production' ? Monolog\Logger::INFO : Monolog\Logger::DEBUG));

try {
    // 初始化应用配置
    $config = require_once BASE_PATH . '/config/app.php';

    // 设置错误报告级别
    if ($config['debug']) {
        error_reporting(E_ALL);
        ini_set('display_errors', 1);
    } else {
        error_reporting(0);
        ini_set('display_errors', 0);
    }

    // 设置时区
    date_default_timezone_set($config['timezone']);

    // 连接数据库
    $db = new App\Database\Connection($config['database']);

    // 处理请求
    $request = new App\Http\Request();
    $router = new App\Routing\Router($request, $db, $log);

    // 加载路由
    require_once $config['routes']['web'];
    require_once $config['routes']['api'];

    // 分发请求
    $response = $router->dispatch();

    // 发送响应
    if ($response) {
        echo $response;
    } else {
        http_response_code(404);
        if ($request->isApi()) {
            echo json_encode([
                'success' => false,
                'code' => 404,
                'message' => '请求的资源不存在'
            ]);
        } else {
            echo $router->getView()->render('errors/404');
        }
    }
} catch (Exception $e) {
    // 记录错误日志
    $log->error($e->getMessage(), [
        'file' => $e->getFile(),
        'line' => $e->getLine(),
        'trace' => $e->getTraceAsString(),
        'ip' => $request->getIp() ?? 'unknown'
    ]);

    // 输出错误响应
    http_response_code(500);
    if ($request->isApi()) {
        $errorResponse = [
            'success' => false,
            'code' => 500,
            'message' => $config['debug'] ? $e->getMessage() : '服务器内部错误'
        ];
        if ($config['debug']) {
            $errorResponse['debug'] = [
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ];
        }
        echo json_encode($errorResponse);
    } else {
        if ($config['debug']) {
            echo '<pre>'.htmlspecialchars($e).'</pre>';
        } else {
            echo $router->getView()->render('errors/500');
        }
    }
}
