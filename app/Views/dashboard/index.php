<?php $this->layout('layouts/admin', ['title' => '后台首页']) ?>

<div class="container mx-auto px-4 py-8">
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-8">
        <div>
            <h1 class="text-2xl font-bold">仪表盘</h1>
            <p class="text-gray-600 mt-1">欢迎回来，<?= htmlspecialchars($user['username']) ?></p>
        </div>
        <div class="mt-4 md:mt-0">
            <span class="text-sm text-gray-500">当前时间: <?= date('Y-m-d H:i:s') ?></span>
        </div>
    </div>

    <!-- 统计卡片 -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center">
                <div class="w-12 h-12 rounded-full bg-blue-100 flex items-center justify-center mr-4">
                    <i class="fa fa-film text-blue-600 text-xl"></i>
                </div>
                <div>
                    <p class="text-gray-500 text-sm">剧集总数</p>
                    <p class="text-2xl font-bold mt-1"><?= $stats['dramas'] ?></p>
                </div>
            </div>
            <div class="mt-4 pt-4 border-t border-gray-100">
                <a href="/admin/dramas" class="text-blue-600 hover:text-blue-800 text-sm flex items-center">
                    查看全部 <i class="fa fa-arrow-right ml-1"></i>
                </a>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center">
                <div class="w-12 h-12 rounded-full bg-green-100 flex items-center justify-center mr-4">
                    <i class="fa fa-image text-green-600 text-xl"></i>
                </div>
                <div>
                    <p class="text-gray-500 text-sm">图片总数</p>
                    <p class="text-2xl font-bold mt-1"><?= $stats['images'] ?></p>
                </div>
            </div>
            <div class="mt-4 pt-4 border-t border-gray-100">
                <a href="/admin/images" class="text-green-600 hover:text-green-800 text-sm flex items-center">
                    查看全部 <i class="fa fa-arrow-right ml-1"></i>
                </a>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center">
                <div class="w-12 h-12 rounded-full bg-purple-100 flex items-center justify-center mr-4">
                    <i class="fa fa-users text-purple-600 text-xl"></i>
                </div>
                <div>
                    <p class="text-gray-500 text-sm">用户总数</p>
                    <p class="text-2xl font-bold mt-1"><?= $stats['users'] ?></p>
                </div>
            </div>
            <div class="mt-4 pt-4 border-t border-gray-100">
                <a href="/admin/users" class="text-purple-600 hover:text-purple-800 text-sm flex items-center">
                    查看全部 <i class="fa fa-arrow-right ml-1"></i>
                </a>
            </div>
        </div>
    </div>

    <!-- 最近上传的图片 -->
    <div class="bg-white rounded-lg shadow p-6 mb-8">
        <div class="flex justify-between items-center mb-6">
            <h2 class="text-lg font-semibold">最近上传的图片</h2>
            <a href="/admin/images" class="text-primary text-sm">查看全部</a>
        </div>
        
        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-4">
            <?php foreach ($recentImages as $image): ?>
            <div class="relative group">
                <img src="<?== htmlspecialchars($image['thumbnail_url']) ?>" 
                     alt="图片预览" 
                     class="w-full h-24 object-cover rounded">
                <div class="absolute inset-0 bg-black bg-opacity-50 opacity-0 group-hover:opacity-100 transition-opacity-opacity flex items-center justify-center">
                    <div class="flex space-x-2">
                        <a href="/admin/images/edit/<?= $image['id'] ?>" class="text-white hover:text-blue-300">
                            <i class="fa fa-pencil"></i>
                        </a>
                        <button data-id="<?= $image['id'] ?>" class="text-white hover:text-red-300 delete-image">
                            <i class="fa fa-trash"></i>
                        </button>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- 最近更新的剧集 -->
    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex justify-between items-center mb-6">
            <h2 class="text-lg font-semibold">最近更新的剧集</h2>
            <a href="/admin/dramas" class="text-primary text-sm">查看全部</a>
        </div>
        
        <div class="overflow-x-auto">
            <table class="min-w-full">
                <thead>
                    <tr class="bg-gray-50">
                        <th class="py-3 px-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">标题</th>
                        <th class="py-3 px-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">状态</th>
                        <th class="py-3 px-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">图片数</th>
                        <th class="py-3 px-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">更新时间</th>
                        <th class="py-3 px-4 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">操作</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php foreach ($recentDramas as $drama): ?>
                    <tr>
                        <td class="py-3 px-4 whitespace-nowrap">
                            <div class="font-medium text-gray-900"><?= htmlspecialchars($drama['title']) ?></div>
                        </td>
                        <td class="py-3 px-4 whitespace-nowrap">
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                <?= $drama['status'] == 'published' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800' ?>">
                                <?= $drama['status'] == 'published' ? '已发布' : '草稿' ?>
                            </span>
                        </td>
                        <td class="py-3 px-4 whitespace-nowrap text-sm text-gray-500">
                            <?= $drama['image_count'] ?>
                        </td>
                        <td class="py-3 px-4 whitespace-nowrap text-sm text-gray-500">
                            <?= $drama['updated_at'] ?>
                        </td>
                        <td class="py-3 px-4 whitespace-nowrap text-right text-sm font-medium">
                            <a href="/admin/dramas/edit/<?= $drama['id'] ?>" class="text-indigo-600 hover:text-indigo-900 mr-3">编辑</a>
                            <a href="/admin/dramas/images/<?= $drama['id'] ?>" class="text-green-600 hover:text-green-900 mr-3">图片</a>
                            <button data-id="<?= $drama['id'] ?>" class="text-red-600 hover:text-red-900 delete-drama">删除</button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
// 删除图片确认
document.querySelectorAll('.delete-image').forEach(button => {
    button.addEventListener('click', function() {
        const imageId = this.getAttribute('data-id');
        if (confirm('确定要删除这张图片吗？')) {
            fetch(`/api/v1/images/${imageId}`, {
                method: 'DELETE',
                headers: {
                    'Authorization': 'Bearer <?= $_SESSION['token'] ?>',
                    'Content-Type': 'application/json'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('图片已删除');
                    location.reload();
                } else {
                    alert('删除失败: ' + data.message);
                }
            })
            .catch(error => {
                alert('操作失败，请重试');
            });
        }
    });
});

// 删除剧集确认
document.querySelectorAll('.delete-drama').forEach(button => {
    button.addEventListener('click', function() {
        const dramaId = this.getAttribute('data-id');
        if (confirm('确定要删除这个剧集吗？相关图片也会被删除。')) {
            fetch(`/api/v1/dramas/${dramaId}`, {
                method: 'DELETE',
                headers: {
                    'Authorization': 'Bearer <?= $_SESSION['token'] ?>',
                    'Content-Type': 'application/json'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('剧集已删除');
                    location.reload();
                } else {
                    alert('删除失败: ' + data.message);
                }
            })
            .catch(error => {
                alert('操作失败，请重试');
            });
        }
    });
});
</script>
