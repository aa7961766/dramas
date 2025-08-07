<?php
namespace App\Controllers;

use App\Models\User;
use Firebase\JWT\JWT;
use App\Http\Request;
use App\Middleware\Auth;
use Respect\Validation\Validator as v;

class AuthController extends BaseController
{
    /**
     * 用户登录
     */
    public function login(Request $request)
    {
        // 验证请求数据
        $validator = $this->validate($request, [
            'username' => v::notEmpty()->setName('用户名'),
            'password' => v::notEmpty()->setName('密码')
        ]);

        if (!$validator->isValid()) {
            return $this->errorResponse($validator->getErrors(), 4002);
        }

        $username = $request->post('username');
        $password = $request->post('password');

        // 查找用户
        $user = User::findByUsername($this->db, $username);
        if (!$user) {
            $this->log->warning('登录失败：用户不存在', [
                'username' => $username,
                'ip' => $request->getIp()
            ]);
            return $this->errorResponse('用户名或密码不正确', 2001);
        }

        // 验证密码
        if (!password_verify($password, $user['password'])) {
            $this->log->warning('登录失败：密码错误', [
                'username' => $username,
                'ip' => $request->getIp()
            ]);
            return $this->errorResponse('用户名或密码不正确', 2001);
        }

        // 生成JWT令牌
        $payload = [
            'iss' => $_SERVER['HTTP_HOST'],
            'sub' => $user['id'],
            'iat' => time(),
            'exp' => time() + 3600, // 1小时有效期
            'user' => [
                'id' => $user['id'],
                'username' => $user['username'],
                'role' => $user['role']
            ]
        ];

        $token = JWT::encode($payload, $_ENV['JWT_SECRET'], 'HS256');

        // 记录登录日志
        $this->log->info('用户登录成功', [
            'user_id' => $user['id'],
            'username' => $user['username'],
            'ip' => $request->getIp()
        ]);

        // 更新最后登录时间
        User::updateLastLogin($this->db, $user['id']);

        // 返回响应
        return $this->successResponse([
            'access_token' => $token,
            'expires_in' => 3600,
            'user' => [
                'id' => $user['id'],
                'username' => $user['username'],
                'role' => $user['role'],
                'email' => $user['email']
            ]
        ]);
    }

    /**
     * 用户登出
     */
    public function logout(Request $request)
    {
        // 在实际应用中，可以将令牌加入黑名单
        $user = $request->user;
        
        $this->log->info('用户登出', [
            'user_id' => $user->sub,
            'ip' => $request->getIp()
        ]);
        
        return $this->successResponse([], '登出成功');
    }

    /**
     * 获取当前用户信息
     */
    public function me(Request $request)
    {
        $user = User::findById($this->db, $request->user->sub);
        
        if (!$user) {
            return $this->errorResponse('用户不存在', 5001);
        }
        
        // 移除敏感信息
        unset($user['password']);
        
        return $this->successResponse($user);
    }
}
