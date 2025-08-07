<?php
/**
 * 安装程序入口
 */
session_start();

// 检查是否已安装
if (file_exists(__DIR__ . '/../config/installed.lock')) {
    die("系统已安装，若需重新安装，请删除 config/installed.lock 文件");
}

// 初始化安装步骤
if (!isset($_SESSION['install_step']) || !in_array($_SESSION['install_step'], [1,2,3,4])) {
    $_SESSION['install_step'] = 1;
}

// 跳转至当前步骤
header("Location: step-{$_SESSION['install_step']}.php");
exit;
?>