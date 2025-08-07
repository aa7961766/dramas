<?php
namespace App\Services;

use App\Models\Image;

class ImageService
{
    private $db;
    private $log;

    public function __construct($db, $log)
    {
        $this->db = $db;
        $this->log = $log;
    }

    /**
     * 处理图片上传和处理
     */
    public function processUpload($file, $type, $relatedId = 0, $userId)
    {
        // 验证文件是否有效
        if (!$file || $file['error'] !== UPLOAD_ERR_OK) {
            throw new \RuntimeException("上传的文件无效", 6002);
        }

        // 验证文件类型
        $mimeType = mime_content_type($file['tmp_name']);
        $allowedTypes = ['image/jpeg', 'image/png', 'image/webp'];
        
        if (!in_array($mimeType, $allowedTypes)) {
            throw new \InvalidArgumentException("仅支持JPG、PNG、WebP格式的图片", 6003);
        }

        // 验证文件大小（最大5MB）
        if ($file['size'] > 5 * 1024 * 1024) {
            throw new \InvalidArgumentException("图片大小不能超过5MB", 6004);
        }

        // 获取图片尺寸
        $dimensions = get_image_dimensions($file['tmp_name']);
        
        // 验证图片尺寸（最大2000x2000）
        if ($dimensions['width'] > 2000 || $dimensions['height'] > 2000) {
            throw new \InvalidArgumentException("图片尺寸不能超过2000x2000像素", 6006);
        }

        // 创建存储目录
        $uploadDir = $type;
        if (!create_image_directory($uploadDir)) {
            throw new \RuntimeException("无法创建图片存储目录", 6007);
        }

        // 生成唯一文件名
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION) ?: 'jpg';
        $filename = uniqid() . '.' . strtolower($extension);
        $destinationPath = BASE_PATH . '/public/uploads/' . $uploadDir;
        $destinationFile = $destinationPath . '/' . $filename;

        // 移动文件
        if (!move_uploaded_file($file['tmp_name'], $destinationFile)) {
            throw new \RuntimeException("文件上传失败", 6008);
        }

        // 设置文件权限
        chmod($destinationFile, 0644);

        // 创建缩略图
        $this->createThumbnail($destinationFile, $destinationPath . '/thumb_' . $filename, 300);

        // 保存记录到数据库
        $imageId = Image::create($this->db, [
            'filename' => $filename,
            'path' => $uploadDir,
            'type' => $type,
            'size' => $file['size'],
            'width' => $dimensions['width'],
            'height' => $dimensions['height'],
            'related_id' => $relatedId,
            'related_type' => $type,
            'uploaded_by' => $userId
        ]);

        if (!$imageId) {
            // 如果数据库记录失败，删除已上传的文件
            @unlink($destinationFile);
            @unlink($destinationPath . '/thumb_' . $filename);
            throw new \RuntimeException("无法保存图片记录到数据库", 6009);
        }

        // 获取完整的图片信息
        $image = Image::findById($this->db, $imageId);

        // 记录图片上传日志
        $this->log->info('图片上传成功', [
            'image_id' => $imageId,
            'filename' => $filename,
            'type' => $type,
            'size' => round($file['size'] / 1024, 2) . 'KB',
            'dimensions' => "{$dimensions['width']}x{$dimensions['height']}",
            'user_id' => $userId
        ]);

        return $image;
    }

    /**
     * 创建缩略图
     */
    private function createThumbnail($source, $destination, $maxWidth)
    {
        // 获取源图片信息
        $sourceInfo = getimagesize($source);
        $sourceWidth = $sourceInfo[0];
        $sourceHeight = $sourceInfo[1];
        $mimeType = $sourceInfo['mime'];

        // 计算缩略图尺寸
        $ratio = $maxWidth / $sourceWidth;
        $thumbWidth = $maxWidth;
        $thumbHeight = (int)($sourceHeight * $ratio);

        // 根据图片类型创建源图像资源
        switch ($mimeType) {
            case 'image/jpeg':
                $sourceImage = imagecreatefromjpeg($source);
                break;
            case 'image/png':
                $sourceImage = imagecreatefrompng($source);
                imagesavealpha($sourceImage, true); // 保留透明通道
                break;
            case 'image/webp':
                $sourceImage = imagecreatefromwebp($source);
                break;
            default:
                throw new \InvalidArgumentException("不支持的图片格式", 6003);
        }

        // 创建缩略图画布
        $thumbImage = imagecreatetruecolor($thumbWidth, $thumbHeight);
        
        // 处理PNG透明背景
        if ($mimeType == 'image/png') {
            imagealphablending($thumbImage, false);
            imagesavealpha($thumbImage, true);
            $transparent = imagecolorallocatealpha($thumbImage, 255, 255, 255, 127);
            imagefilledrectangle($thumbImage, 0, 0, $thumbWidth, $thumbHeight, $transparent);
        }

        // 生成缩略图
        imagecopyresampled(
            $thumbImage,
            $sourceImage,
            0, 0, 0, 0,
            $thumbWidth, $thumbHeight,
            $sourceWidth, $sourceHeight
        );

        // 保存缩略图
        switch ($mimeType) {
            case 'image/jpeg':
                imagejpeg($thumbImage, $destination, 85);
                break;
            case 'image/png':
                imagepng($thumbImage, $destination, 6);
                break;
            case 'image/webp':
                imagewebp($thumbImage, $destination, 85);
                break;
        }

        // 释放资源
        imagedestroy($sourceImage);
        imagedestroy($thumbImage);

        // 设置文件权限
        chmod($destination, 0644);
    }

    /**
     * 删除图片文件和记录
     */
    public function deleteImage($image)
    {
        // 删除文件
        $deleted = delete_image_files($image['path'], $image['filename']);
        
        if (!$deleted) {
            $this->log->warning('删除图片文件失败', [
                'image_id' => $image['id'],
                'path' => $image['path'],
                'filename' => $image['filename']
            ]);
            // 即使文件删除失败，仍然继续删除数据库记录
        }

        // 删除数据库记录
        $success = Image::delete($this->db, $image['id']);
        
        if (!$success) {
            throw new \RuntimeException("无法删除数据库中的图片记录", 6010);
        }
        
        return true;
    }
}
