<?php
namespace App\Models;

use PDO;

class Image
{
    /**
     * 根据ID查找图片
     */
    public static function findById($db, $id)
    {
        $stmt = $db->getConnection()->prepare("
            SELECT * FROM images WHERE id = :id
        ");
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        
        $image = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($image) {
            // 添加URL字段
            $image['url'] = image_url($image['path'], $image['filename']);
            $image['thumbnail_url'] = thumbnail_url($image['path'], $image['filename']);
        }
        
        return $image;
    }

    /**
     * 查找符合条件的图片列表
     */
    public static function findAll($db, $params = [], $page = 1, $limit = 20)
    {
        $offset = ($page - 1) * $limit;
        $conditions = [];
        $bindings = [];
        
        if (!empty($params['type'])) {
            $conditions[] = "type = :type";
            $bindings[':type'] = $params['type'];
        }
        
        if (!empty($params['related_id'])) {
            $conditions[] = "related_id = :related_id";
            $bindings[':related_id'] = $params['related_id'];
        }
        
        if (!empty($params['related_type'])) {
            $conditions[] = "related_type = :related_type";
            $bindings[':related_type'] = $params['related_type'];
        }
        
        $whereClause = $conditions ? "WHERE " . implode(" AND ", $conditions) : "";
        
        $stmt = $db->getConnection()->prepare("
            SELECT * FROM images 
            {$whereClause}
            ORDER BY created_at DESC
            LIMIT :limit OFFSET :offset
        ");
        
        foreach ($bindings as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        
        $images = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // 为每张图片添加URL
        foreach ($images as &$image) {
            $image['url'] = image_url($image['path'], $image['filename']);
            $image['thumbnail_url'] = thumbnail_url($image['path'], $image['filename']);
        }
        
        return $images;
    }

    /**
     * 根据关联对象查找图片
     */
    public static function findByRelated($db, $relatedType, $relatedId, $limit = 0)
    {
        $params = [
            'related_type' => $relatedType,
            'related_id' => $relatedId
        ];
        
        return self::findAll($db, $params, 1, $limit);
    }

    /**
     * 统计符合条件的图片数量
     */
    public static function count($db, $params = [])
    {
        $conditions = [];
        $bindings = [];
        
        if (!empty($params['type'])) {
            $conditions[] = "type = :type";
            $bindings[':type'] = $params['type'];
        }
        
        if (!empty($params['related_id'])) {
            $conditions[] = "related_id = :related_id";
            $bindings[':related_id'] = $params['related_id'];
        }
        
        if (!empty($params['related_type'])) {
            $conditions[] = "related_type = :related_type";
            $bindings[':related_type'] = $params['related_type'];
        }
        
        $whereClause = $conditions ? "WHERE " . implode(" AND ", $conditions) : "";
        
        $stmt = $db->getConnection()->prepare("
            SELECT COUNT(*) as total FROM images {$whereClause}
        ");
        
        foreach ($bindings as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return (int)$result['total'];
    }

    /**
     * 创建图片记录
     */
    public static function create($db, $data)
    {
        $stmt = $db->getConnection()->prepare("
            INSERT INTO images (
                filename, path, type, size, width, height, 
                related_id, related_type, uploaded_by, created_at
            ) VALUES (
                :filename, :path, :type, :size, :width, :height,
                :related_id, :related_type, :uploaded_by, NOW()
            )
        ");
        
        $success = $stmt->execute([
            ':filename' => $data['filename'],
            ':path' => $data['path'],
            ':type' => $data['type'],
            ':size' => $data['size'],
            ':width' => $data['width'],
            ':height' => $data['height'],
            ':related_id' => $data['related_id'] ?? 0,
            ':related_type' => $data['related_type'] ?? '',
            ':uploaded_by' => $data['uploaded_by']
        ]);
        
        return $success ? $db->getConnection()->lastInsertId() : false;
    }

    /**
     * 删除图片记录
     */
    public static function delete($db, $id)
    {
        $stmt = $db->getConnection()->prepare("
            DELETE FROM images WHERE id = :id
        ");
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        return $stmt->execute();
    }
}
