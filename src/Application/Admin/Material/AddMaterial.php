<?php

namespace WanPHP\Plugins\WeiXin\Application\Admin\Material;

use Exception;
use Psr\Http\Message\ResponseInterface as Response;
use WanPHP\Core\Action;
use WanPHP\Core\Attribute\Route;
use WanPHP\Core\Middleware\AdminPermissionMiddlewareInterface;
use WanPHP\Core\Repositories\WeiXin\WeChatBase;

class AddMaterial extends Action
{
  private string $filepath;

  /**
   * @param WeChatBase $weChatBase
   */
  public function __construct(private readonly WeChatBase $weChatBase)
  {
    $this->filepath = ROOT_PATH . getenv('APP_UPLOAD_FILE_PATH');
  }

  #[Route(
    path: '/admin/wx/material/add/{type:image|voice|video|thumb}',
    methods: ['POST'],
    description: '添加素材',
    name: 'wx.material.add',
    middleware: [AdminPermissionMiddlewareInterface::class]
  )]
  protected function action(): Response
  {
    $type = $this->resolveArg('type');
    $data = $this->getFormData();
    $file = $this->filepath . $data['filePath'];
    if (is_file($file)) {
      try {
        if (isset($data['type']) && $data['type'] == 'uploadImage') return $this->respondWithData($this->weChatBase->uploadImage($file));
        elseif ($type == 'video') return $this->respondWithData($this->weChatBase->addMaterial($type, $file, $data['description']));
        else return $this->respondWithData($this->weChatBase->addMaterial($type, $file));
      } catch (Exception $exception) {
        return $this->respondWithError($exception->getMessage());
      }
    } else {
      return $this->respondWithError('找不到文件');
    }
  }
}