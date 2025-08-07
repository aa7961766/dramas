<?php
namespace App\Middleware;

use App\Http\Request;

class ImageUploadValidation
{
    public function handle(Request $request, $next)
    {
        // 检查是否有文件上传
        if (!$request->hasFile('image')) {
            return json_encode([
                'success' => false,
                'code' => 6001,
                'message' => '请选择要上传的图片'
            ], JSON_UNESCAPED_UNICODE);
        }
        
        $file = $request->file('image');
        
        // 检查文件是否有效
        if (!$file || $file['error'] !== UPLOAD_ERR_OK) {
            $errorMessages = [
                UPLOAD_ERR_INI_SIZE => '上传的文件超过了php.ini中upload_max_filesize选项限制的值',
                UPLOAD_ERR_FORM_SIZE => '上传文件的大小超过了HTML表单中MAX_FILE_SIZE选项指定的值',
                UPLOAD_ERR_PARTIAL => '文件只有部分被上传',
                UPLOAD_ERR_NO_FILE => '没有文件被上传',
                UPLOAD_ERR_NO_TMP_DIR => '找不到临时文件夹',
                UPLOAD_ERR_CANT_WRITE => '文件写入失败',
                UPLOAD_ERR_EXTENSION => '文件上传被扩展阻止'
            ];
            
            $errorMessage = $errorMessages[$file['error']] ?? '上传的文件无效';
            
            return json_encode([
                'success' => false,
                'code' => 6002,
                'message' => $errorMessage
            ], JSON_UNESCAPED_UNICODE);
        }
        
        // 检查文件类型
        $mimeType = mime_content_type($file['tmp_name']);
        $allowedTypes = ['image/jpeg', 'image/png', 'image/webp'];
        
        if (!in_array($mimeType, $allowedTypes)) {
            return json_encode([
                'success' => false,
                'code' => 6003,
                'message' => '仅支持JPG、PNG、WebP格式的图片'
            ], JSON_UNESCAPED_UNICODE);
        }
        
        // 检查文件大小（最大5MB）
        if ($file['size'] > 5 * 1024 * 1024) {
            return json_encode([
                'success' => false,
                'code' => 6004,
                'message' => '图片大小不能超过5MB'
            ], JSON_UNESCAPED_UNICODE);
        }
        
        return $next($request);
    }
}
