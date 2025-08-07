<?php
namespace App\Controllers;

use App\Models\Drama;
use App\Models\Image;
use App\Http\Request;
use App\Services\ImageService;
use Respect\Validation\Validator as v;

class DramaController extends BaseController
{
    private $imageService;

    public function __construct($db, $log, $view)
    {
        parent::__construct($db, $log, $view);
        $this->imageService = new ImageService($db, $log);
    }

    /**
     * 获取剧集列表
     */
    public function index(Request $request)
    {
        $page = (int)$request->get('page', 1);
        $limit = (int)$request->get('limit', 20);
        $search = $request->get('search');

        $params = [];
        if ($search) $params['search'] = $search;

        $dramas = Drama::findAll($this->db, $params, $page, $limit);
        $total = Drama::count($this->db, $params);

        // 为每个剧集添加图片信息
        foreach ($dramas as &$drama) {
            $drama['images'] = Image::findByRelated($this->db, 'drama', $drama['id'], 1);
        }

        return $this->successResponse([
            'items' => $dramas,
            'pagination' => [
                'total' => $total,
                'page' => $page,
                'limit' => $limit,
                'pages' => ceil($total / $limit)
            ]
        ]);
    }

    /**
     * 获取单个剧集
     */
    public function show(Request $request, $id)
    {
        $drama = Drama::findById($this->db, $id);
        
        if (!$drama) {
            return $this->errorResponse('剧集不存在', 5001);
        }
        
        // 获取剧集关联的图片
        $drama['images'] = Image::findByRelated($this->db, 'drama', $id);
        
        return $this->successResponse($drama);
    }

    /**
     * 创建剧集
     */
    public function store(Request $request)
    {
        // 验证请求数据
        $validator = $this->validate($request, [
            'title' => v::notEmpty()->length(1, 100)->setName('剧集标题'),
            'description' => v::optional(v::length(0, 2000))->setName('剧集描述'),
            'release_date' => v::optional(v::date('Y-m-d'))->setName('发布日期'),
            'status' => v::in(['draft', 'published'])->setName('状态')
        ]);

        if (!$validator->isValid()) {
            return $this->errorResponse($validator->getErrors(), 4002);
        }

        $data = [
            'title' => $request->post('title'),
            'description' => $request->post('description', ''),
            'release_date' => $request->post('release_date'),
            'status' => $request->post('status', 'draft'),
            'created_by' => $request->user->sub,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ];

        $dramaId = Drama::create($this->db, $data);
        
        if (!$dramaId) {
            return $this->errorResponse('创建剧集失败', 5002);
        }
        
        $this->log->info('创建新剧集', [
            'drama_id' => $dramaId,
            'title' => $data['title'],
            'user_id' => $request->user->sub
        ]);
        
        $drama = Drama::findById($this->db, $dramaId);
        
        return $this->successResponse($drama, '剧集创建成功');
    }

    /**
     * 更新剧集
     */
    public function update(Request $request, $id)
    {
        $drama = Drama::findById($this->db, $id);
        
        if (!$drama) {
            return $this->errorResponse('剧集不存在', 5001);
        }

        // 验证请求数据
        $validator = $this->validate($request, [
            'title' => v::optional(v::notEmpty()->length(1, 100))->setName('剧集标题'),
            'description' => v::optional(v::length(0, 2000))->setName('剧集描述'),
            'release_date' => v::optional(v::date('Y-m-d'))->setName('发布日期'),
            'status' => v::optional(v::in(['draft', 'published']))->setName('状态')
        ]);

        if (!$validator->isValid()) {
            return $this->errorResponse($validator->getErrors(), 4002);
        }

        $data = [
            'title' => $request->post('title', $drama['title']),
            'description' => $request->post('description', $drama['description']),
            'release_date' => $request->post('release_date', $drama['release_date']),
            'status' => $request->post('status', $drama['status']),
            'updated_at' => date('Y-m-d H:i:s')
        ];

        $success = Drama::update($this->db, $id, $data);
        
        if (!$success) {
            return $this->errorResponse('更新剧集失败', 5002);
        }
        
        $this->log->info('更新剧集', [
            'drama_id' => $id,
            'user_id' => $request->user->sub
        ]);
        
        $updatedDrama = Drama::findById($this->db, $id);
        
        return $this->successResponse($updatedDrama, '剧集更新成功');
    }

    /**
     * 删除剧集
     */
    public function destroy(Request $request, $id)
    {
        $drama = Drama::findById($this->db, $id);
        
        if (!$drama) {
            return $this->errorResponse('剧集不存在', 5001);
        }
        
        // 删除关联的图片
        $images = Image::findByRelated($this->db, 'drama', $id);
        foreach ($images as $image) {
            $this->imageService->deleteImage($image);
        }
        
        // 删除剧集
        $success = Drama::delete($this->db, $id);
        
        if (!$success) {
            return $this->errorResponse('删除剧集失败', 5003);
        }
        
        $this->log->info('删除剧集', [
            'drama_id' => $id,
            'title' => $drama['title'],
            'user_id' => $request->user->sub
        ]);
        
        return $this->successResponse([], '剧集已成功删除');
    }

    /**
     * 为剧集添加图片
     */
    public function addImage(Request $request, $id)
    {
        $drama = Drama::findById($this->db, $id);
        
        if (!$drama) {
            return $this->errorResponse('剧集不存在', 5001);
        }
        
        try {
            // 获取上传文件
            $file = $request->file('image');
            if (!$file || $file['error'] !== UPLOAD_ERR_OK) {
                return $this->errorResponse('请上传有效的图片文件', 6001);
            }

            // 处理图片上传
            $image = $this->imageService->processUpload(
                $file,
                'drama',
                $id,
                $request->user->sub
            );

            $this->log->info('为剧集添加图片', [
                'drama_id' => $id,
                'image_id' => $image['id'],
                'user_id' => $request->user->sub
            ]);
            
            return $this->successResponse($image, '图片添加成功');
        } catch (\Exception $e) {
            $this->log->error('为剧集添加图片失败: ' . $e->getMessage(), [
                'drama_id' => $id,
                'user_id' => $request->user->sub
            ]);
            
            return $this->errorResponse($e->getMessage(), 6000);
        }
    }

    /**
     * 从剧集移除图片
     */
    public function removeImage(Request $request, $id, $imageId)
    {
        $drama = Drama::findById($this->db, $id);
        if (!$drama) {
            return $this->errorResponse('剧集不存在', 5001);
        }
        
        $image = Image::findById($this->db, $imageId);
        if (!$image || $image['related_type'] != 'drama' || $image['related_id'] != $id) {
            return $this->errorResponse('图片不存在或不属于该剧集', 5001);
        }
        
        try {
            // 删除图片
            $this->imageService->deleteImage($image);
            
            $this->log->info('从剧集移除图片', [
                'drama_id' => $id,
                'image_id' => $imageId,
                'user_id' => $request->user->sub
            ]);
            
            return $this->successResponse([], '图片已移除');
        } catch (\Exception $e) {
            $this->log->error('从剧集移除图片失败: ' . $e->getMessage(), [
                'drama_id' => $id,
                'image_id' => $imageId,
                'user_id' => $request->user->sub
            ]);
            
            return $this->errorResponse('移除图片失败: ' . $e->getMessage(), 6005);
        }
    }
}
