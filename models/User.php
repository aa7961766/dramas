<?php
    /**
     * 用户模型
     */
    class User {
        private $db;
        
        public function __construct(Db $db) {
            $this->db = $db;
        }
        
        /**
         * 通过ID获取用户
         */
        public function getById($id) {
            return $this->db->fetch(
                "SELECT * FROM users WHERE id = :id LIMIT 1",
                [':id' => $id]
            );
        }
        
        /**
         * 通过用户名获取用户
         */
        public function getByUsername($username) {
            return $this->db->fetch(
                "SELECT * FROM users WHERE username = :username LIMIT 1",
                [':username' => $username]
            );
        }
        
        /**
         * 获取所有用户
         */
        public function getAll($page = 1, $perPage = 15) {
            $offset = ($page - 1) * $perPage;
            return $this->db->fetchAll(
                "SELECT * FROM users ORDER BY created_at DESC LIMIT :perPage OFFSET :offset",
                [':perPage' => $perPage, ':offset' => $offset]
            );
        }
        
        /**
         * 创建用户
         */
        public function create($data) {
            // 确保密码已加密
            if (!isset($data['password']) || !password_get_info($data['password'])['algo']) {
                $data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
            }
            
            $data['created_at'] = date('Y-m-d H:i:s');
            $data['updated_at'] = date('Y-m-d H:i:s');
            
            return $this->db->insert('users', $data);
        }
        
        /**
         * 更新用户
         */
        public function update($id, $data) {
            // 如果更新密码，确保加密
            if (isset($data['password']) && !empty($data['password'])) {
                $data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
            } else {
                unset($data['password']); // 不更新密码
            }
            
            $data['updated_at'] = date('Y-m-d H:i:s');
            
            return $this->db->update(
                'users',
                $data,
                'id = :id',
                [':id' => $id]
            );
        }
        
        /**
         * 删除用户
         */
        public function delete($id) {
            return $this->db->delete(
                'users',
                'id = :id',
                [':id' => $id]
            );
        }
    }
    ?>