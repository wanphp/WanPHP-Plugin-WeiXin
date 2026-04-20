<?php

namespace WanPHP\Plugins\WeiXin\Application\Admin\CustomMenu;

use Psr\Http\Message\ResponseInterface as Response;
use WanPHP\Core\Action;
use WanPHP\Core\Attribute\Route;
use WanPHP\Core\Middleware\AdminPermissionMiddlewareInterface;
use WanPHP\Plugins\WeiXin\Service\CustomMenuService;

class DeleteMenu extends Action
{
public function __construct(private readonly CustomMenuService $customMenuService)
{
}

  #[Route(
    path: '/admin/wx/custom_menu/delete/{id:[0-9]+}',
    methods: ['DELETE'],
    description: '删除自定义菜单',
    name: 'wx.custom_menu.delete',
    middleware: [AdminPermissionMiddlewareInterface::class]
  )]
  protected function action(): Response
  {
    $id = (int)$this->resolveArg('id', 0);
    if ($id > 0) {
      return $this->respondWithData(['upNum' => $this->customMenuService->delete($id)], 204);
    } else {
      return $this->respondWithError('ID有误');
    }
  }
}