<?php
namespace App\Controllers;

use App\Models\Image;
use App\Http\Request;
use App\Services\ImageService;
use Respect\Validation\Validator as v;

class ImageController extends BaseController
{
    private $imageService;

    public function __construct($db, $log, $view)
    {
        parent::__construct($db, $log, $view);
        $this->imageService = new ImageService($db, $log);
    }

    /**
     * 上传图片
     */
    public function upload(Request $request)
    {
        try {
            // 验证请求数据
            $validator = $this->validate($request, [
                'type' => v::in(['drama', 'article', 'user'])->setName('图片类型')
            ]);

            if (!$validator->isValid()) {
                return $this->errorResponse($validator->getErrors(), 4002);
            }

            // 获取上传文件
            $file = $request->file('image');
            if (!$file || $file['error'] !== UPLOAD_ERR_OK) {
                return $this->errorResponse('请上传有效的图片文件', 6001);
            }

            $type = $request->post('type');
            $relatedId = $request->post('related_id', 0);

            // 处理图片上传
            $image = $this->imageService->processUpload(
                $file,
                $type,
                $relatedId,
                $request->user->sub
            );

            return $this->successResponse($image);
        } catch (\Exception $e) {
            $this->log->error('图片上传失败: ' . $e->getMessage(), [
                'user_id' => $request->user->sub,
                'error' => $e->getTraceAsString()
            ]);
            
            return $this->errorResponse($e->getMessage(), 6000);
        }
    }

    /**
     * 获取图片列表
     */
    public function index(Request $request)
    {
        $type = $request->get('type');
        $page = (int)$request->get('page', 1);
        $limit = (int)$request->get('limit', 20);
        $relatedId = $request->get('related_id');

        $params = [];
        if ($type) $params['type'] = $type;
        if ($relatedId) $params['related_id'] = $relatedId;

        $images = Image::findAll($this->db, $params, $page, $limit);
        $total = Image::count($this->db, $params);

        return $this->successResponse([
            'items' => $images,
            'pagination' => [
                'total' => $total,
                'page' => $page,
                'limit' => $limit,
                'pages' => ceil($total / $limit)
            ]
        ]);
    }

    /**
     * 获取单张图片
     */
    public function show(Request $request, $id)
    {
        $image = Image::findById($this->db, $id);
        
        if (!$image) {
            return $this->errorResponse('图片不存在', 5001);
        }
        
        return $this->successResponse($image);
    }

    /**
     * 删除图片
     */
    public function destroy(Request $request, $id)
    {
        $image = Image::findById($this->db, $id);
        
        if (!$image) {
            return $this->errorResponse('图片不存在', 5001);
        }
        
        // 检查权限（只有管理员或上传者可以删除）
        if ($request->user->user->role !== 'admin' && 
            $image['uploaded_by'] != $request->user->sub) {
            return $this->errorResponse('没有删除权限', 3001);
        }
        
        try {
            // 删除图片文件和记录
            $this->imageService->deleteImage($image);
            
            $this->log->info('图片已删除', [
                'image_id' => $id,
                'user_id' => $request->user->sub
            ]);
            
            return $this->successResponse([], '图片已成功删除');
        } catch (\Exception $e) {
            $this->log->error('删除图片失败: ' . $e->getMessage(), [
                'image_id' => $id,
                'user_id' => $request->user->sub
            ]);
            
            return $this->errorResponse('删除图片失败: ' . $e->getMessage(), 6005);
        }
    }
}
