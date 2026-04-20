<?php

namespace WanPHP\Plugins\WeiXin\Application\Admin\Tag;

use Psr\Http\Message\ResponseInterface as Response;
use WanPHP\Core\Action;
use WanPHP\Core\Attribute\Route;
use WanPHP\Core\Middleware\AdminPermissionMiddlewareInterface;
use WanPHP\Core\Repositories\WeiXin\WeChatBase;

class DeleteTag extends Action
{

  public function __construct(private readonly WeChatBase $wechatBase)
  {
  }

  #[Route(
    path: '/admin/wx/user/tag/delete/{id:[0-9]+}',
    methods: ['POST'],
    description: '删除用户标签',
    name: 'wx.user.tag.delete',
    middleware: [AdminPermissionMiddlewareInterface::class]
  )]
  protected function action(): Response
  {
    $id = $this->resolveArg('id',0);
    if ($id > 0) {
      $result = $this->wechatBase->deleteTag($id);
      return $this->respondWithData($result);
    } else {
      return $this->respondWithError('缺少ID');
    }
  }
}