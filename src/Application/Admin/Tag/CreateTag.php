<?php

namespace WanPHP\Plugins\WeiXin\Application\Admin\Tag;

use Psr\Http\Message\ResponseInterface as Response;
use WanPHP\Core\Action;
use WanPHP\Core\Attribute\Route;
use WanPHP\Core\Middleware\AdminPermissionMiddlewareInterface;
use WanPHP\Core\Repositories\WeiXin\WeChatBase;

class CreateTag extends Action
{

  public function __construct(private readonly WeChatBase $wechatBase)
  {
  }

  #[Route(
    path: '/admin/wx/user/tag/create',
    methods: ['POST'],
    description: '创建用户标签',
    name: 'wx.user.tag.create',
    middleware: [AdminPermissionMiddlewareInterface::class]
  )]
  protected function action(): Response
  {
    $data = $this->getFormData();
    if ($data['name'] != '') {
      $result = $this->wechatBase->createTag($data['name']);
      return $this->respondWithData($result, 201);
    } else {
      return $this->respondWithError('缺少标签名称');
    }
  }
}