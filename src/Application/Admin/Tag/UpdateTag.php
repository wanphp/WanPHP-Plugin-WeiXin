<?php

namespace WanPHP\Plugins\WeiXin\Application\Admin\Tag;

use Psr\Http\Message\ResponseInterface as Response;
use WanPHP\Core\Action;
use WanPHP\Core\Attribute\Route;
use WanPHP\Core\Middleware\AdminPermissionMiddlewareInterface;
use WanPHP\Core\Repositories\WeiXin\WeChatBase;

class UpdateTag extends Action
{

  public function __construct(private readonly WeChatBase $wechatBase)
  {
  }

  #[Route(
    path: '/admin/wx/user/tag/update/{id:[0-9]+}',
    methods: ['POST'],
    description: '更新用户标签',
    name: 'wx.user.tag.update',
    middleware: [AdminPermissionMiddlewareInterface::class]
  )]
  protected function action(): Response
  {
    $id = $this->resolveArg('id',0);
    $data = $this->getFormData();
    if ($id > 0 && $data['name'] != '') {
      $result = $this->wechatBase->updateTag($id, $data['name']);
      return $this->respondWithData($result);
    } else {
      return $this->respondWithError('缺少ID或标签名称');
    }
  }
}