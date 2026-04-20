<?php

namespace WanPHP\Plugins\WeiXin\Application\Admin\Material;

use Psr\Http\Message\ResponseInterface as Response;
use WanPHP\Core\Action;
use WanPHP\Core\Attribute\Route;
use WanPHP\Core\Middleware\AdminPermissionMiddlewareInterface;
use WanPHP\Core\Repositories\WeiXin\WeChatBase;

class DeleteMaterial extends Action
{
  public function __construct(private readonly WeChatBase $weChatBase)
  {
  }

  #[Route(
    path: '/admin/wx/material/delete/{media_id}',
    methods: ['DELETE'],
    description: '删除素材',
    name: 'wx.material.delete',
    middleware: [AdminPermissionMiddlewareInterface::class]
  )]
  protected function action(): Response
  {
    return $this->respondWithData($this->weChatBase->delMaterial($this->resolveArg('media_id')));
  }
}