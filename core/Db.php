<?php
    /**
     * 数据库连接类（单例模式）
     */
    class Db {
        private static $instance;  // 单例实例
        private $pdo;              // PDO连接对象
        private $config;           // 数据库配置

        // 私有构造方法，防止外部实例化
        private function __construct() {
            // 加载配置文件
            $this->config = require dirname(__DIR__) . '/config/database.php';
            
            try {
                // 构建DSN
                $dsn = "mysql:host={$this->config['host']};port={$this->config['port']};dbname={$this->config['database']};charset={$this->config['charset']}";
                
                // 连接选项
                $options = [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false
                ];
                
                // 创建PDO连接
                $this->pdo = new PDO($dsn, $this->config['username'], $this->config['password'], $options);
                
            } catch (PDOException $e) {
                die("数据库连接失败: " . $e->getMessage());
            }
        }

        // 防止克隆
        private function __clone() {}

        // 防止反序列化
        private function __wakeup() {}

        // 静态方法，获取单例实例
        public static function getInstance() {
            if (!self::$instance instanceof self) {
                self::$instance = new self();
            }
            return self::$instance;
        }

        // 数据库查询方法
        public function fetch($sql, $params = []) {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetch();
        }

        public function fetchAll($sql, $params = []) {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll();
        }

        public function execute($sql, $params = []) {
            $stmt = $this->pdo->prepare($sql);
            return $stmt->execute($params);
        }
    }
    ?>