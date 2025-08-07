<?php
    /**
     * 移动端优化的管理后台首页
     */
    session_start();

    // 登录验证
    if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
        header('Location: login.php');
        exit;
    }

    // 引入数据库类
    require_once dirname(__DIR__) . '/core/Db.php';
    $db = Db::getInstance();

    // 获取统计数据
    $userCount = $db->fetch("SELECT COUNT(*) as count FROM users")['count'];
    $dramaCount = $db->fetch("SELECT COUNT(*) as count FROM dramas WHERE is_deleted=0")['count'];
    $articleCount = $db->fetch("SELECT COUNT(*) as count FROM articles WHERE is_deleted=0")['count'];
    $commentCount = $db->fetch("SELECT COUNT(*) as count FROM comments WHERE is_deleted=0")['count'];
    ?>
    <!DOCTYPE html>
    <html lang="zh-CN">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
        <title>管理后台 - 短剧网站</title>
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/font-awesome@4.7.0/css/font-awesome.min.css">
        <link rel="stylesheet" href="../public/css/admin.css">
    </head>
    <body class="admin-body">
        <!-- 顶部导航栏 -->
        <header class="admin-header">
            <div class="logo">
                <i class="fa fa-shield"></i>
                <span>短剧管理</span>
            </div>
            
            <div class="user-info">
                <a href="logout.php" class="logout-btn">
                    <i class="fa fa-sign-out"></i>
                </a>
            </div>
        </header>

        <div class="admin-container">
            <!-- 传统侧边栏（仅桌面端显示） -->
            <aside class="sidebar">
                <nav class="sidebar-nav">
                    <ul>
                        <li class="active">
                            <a href="index.php">
                                <i class="fa fa-dashboard"></i> 仪表盘
                            </a>
                        </li>
                        <li>
                            <a href="dramas.php">
                                <i class="fa fa-film"></i> 剧集管理
                            </a>
                        </li>
                        <li>
                            <a href="articles.php">
                                <i class="fa fa-file-text"></i> 文章管理
                            </a>
                        </li>
                        <li>
                            <a href="users.php">
                                <i class="fa fa-users"></i> 用户管理
                            </a>
                        </li>
                        <li>
                            <a href="comments.php">
                                <i class="fa fa-comments"></i> 评论管理
                            </a>
                        </li>
                        <li>
                            <a href="settings.php">
                                <i class="fa fa-cog"></i> 系统设置
                            </a>
                        </li>
                    </ul>
                </nav>
            </aside>

            <!-- 主内容区 -->
            <main class="main-content">
                <div class="page-header">
                    <h1>仪表盘</h1>
                    <p>系统概览与关键数据统计</p>
                </div>

                <!-- 统计卡片 -->
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-icon bg-primary">
                            <i class="fa fa-users"></i>
                        </div>
                        <div class="stat-info">
                            <h3>用户总数</h3>
                            <p class="stat-value"><?= $userCount ?></p>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon bg-accent">
                            <i class="fa fa-film"></i>
                        </div>
                        <div class="stat-info">
                            <h3>剧集总数</h3>
                            <p class="stat-value"><?= $dramaCount ?></p>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon bg-blue">
                            <i class="fa fa-file-text"></i>
                        </div>
                        <div class="stat-info">
                            <h3>文章总数</h3>
                            <p class="stat-value"><?= $articleCount ?></p>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon bg-purple">
                            <i class="fa fa-comments"></i>
                        </div>
                        <div class="stat-info">
                            <h3>评论总数</h3>
                            <p class="stat-value"><?= $commentCount ?></p>
                        </div>
                    </div>
                </div>

                <!-- 最近内容表格 -->
                <div class="recent-content">
                    <div class="section-header">
                        <h2>最近剧集</h2>
                        <a href="dramas.php" class="view-all">查看全部</a>
                    </div>
                    <div class="data-table-container">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>名称</th>
                                    <th>分类</th>
                                    <th>发布时间</th>
                                    <th>操作</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>1</td>
                                    <td>测试短剧1</td>
                                    <td>喜剧</td>
                                    <td>2025-08-01</td>
                                    <td>
                                        <button class="btn btn-sm btn-edit">
                                            <i class="fa fa-edit"></i> 编辑
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </main>
        </div>

        <!-- 移动端底部导航（仅手机端显示） -->
        <nav class="mobile-nav">
            <ul>
                <li>
                    <a href="index.php" class="active">
                        <i class="fa fa-dashboard"></i>
                        <span>首页</span>
                    </a>
                </li>
                <li>
                    <a href="dramas.php">
                        <i class="fa fa-film"></i>
                        <span>剧集</span>
                    </a>
                </li>
                <li>
                    <a href="articles.php">
                        <i class="fa fa-file-text"></i>
                        <span>文章</span>
                    </a>
                </li>
                <li>
                    <a href="users.php">
                        <i class="fa fa-users"></i>
                        <span>用户</span>
                    </a>
                </li>
                <li>
                    <a href="settings.php">
                        <i class="fa fa-cog"></i>
                        <span>设置</span>
                    </a>
                </li>
            </ul>
        </nav>

        <script>
            // 自动激活当前页面的导航项
            document.addEventListener('DOMContentLoaded', function() {
                const currentUrl = window.location.pathname.split('/').pop();
                const navLinks = document.querySelectorAll('.mobile-nav a, .sidebar-nav a');
                
                navLinks.forEach(link => {
                    if (link.getAttribute('href') === currentUrl) {
                        link.classList.add('active');
                        // 为父元素添加active类（针对侧边栏）
                        const parentLi = link.closest('li');
                        if (parentLi) parentLi.classList.add('active');
                    }
                });
            });
        </script>
    </body>
</html>
