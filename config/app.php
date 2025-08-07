<?php
/**
 * 应用配置文件
 * 调整为兼容PHP 7.4+
 */
return [
    // 应用名称
    'name' => '图片和内容管理系统',
    
    // 应用环境
    'env' => env('APP_ENV', 'production'),
    
    // 调试模式
    'debug' => env('APP_DEBUG', false),
    
    // 应用密钥
    'key' => env('APP_KEY'),
    
    // 时区设置
    'timezone' => 'Asia/Shanghai',
    
    // 字符编码
    'charset' => 'UTF-8',
    
    // 路由配置
    'routes' => [
        'web' => BASE_PATH . '/routes/web.php',
        'api' => BASE_PATH . '/routes/api.php'
    ],
    
    // 控制器命名空间
    'controller_namespace' => 'App\\Controllers',
    
    // 分页配置
    'pagination' => [
        'per_page' => 15,
        'max_per_page' => 100
    ],
    
    // 图片上传配置
    'image' => [
        // 允许的图片类型
        'allowed_mime_types' => [
            'image/jpeg',
            'image/png',
            'image/webp'
        ],
        // 最大文件大小 (MB)
        'max_size' => 5,
        // 最大尺寸 (像素)
        'max_dimensions' => [
            'width' => 2000,
            'height' => 2000
        ],
        // 缩略图尺寸
        'thumbnail' => [
            'width' => 300,
            'height' => 300,
            'quality' => 80
        ]
    ],
    
    // JWT配置
    'jwt' => [
        'secret' => env('JWT_SECRET', env('APP_KEY')),
        'expires_in' => 3600, // 1小时
        'algorithm' => 'HS256'
    ],
    
    // 日志配置
    'log' => [
        'path' => BASE_PATH . '/storage/logs',
        'level' => env('LOG_LEVEL', 'info')
    ]
];
?>