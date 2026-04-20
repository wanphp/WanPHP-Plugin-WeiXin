<?php

namespace WanPHP\Plugins\WeiXin\Application\Admin\CustomMenu;

use Psr\Http\Message\ResponseInterface as Response;
use WanPHP\Core\Action;
use WanPHP\Core\Attribute\Route;
use WanPHP\Core\Middleware\AdminPermissionMiddlewareInterface;
use WanPHP\Plugins\WeiXin\Service\CustomMenuService;

class EditMenu extends Action
{
  public function __construct(private readonly CustomMenuService $customMenu)
  {
  }

  #[Route(
    path: '/admin/wx/custom_menu/edit/{id:[0-9]+}',
    methods: ['GET', 'POST'],
    description: '修改公众号自定义菜单',
    name: 'wx.custom_menu.edit',
    middleware: [AdminPermissionMiddlewareInterface::class]
  )]
  protected function action(): Response
  {
    $id = (int)$this->resolveArg('id', 0);
    if ($id > 0) {
      if ($this->isPost()) {
        $data = $this->getFormData();
        // 检查关键词是否已被使用
        if ($this->customMenu->checkMenu(['id[!]' => $id, 'name' => $data['name'], 'tag_id' => $data['tag_id']])) {
          return $this->respondWithData(['message' => '菜单已被添加过,重新换一个', 'errors' => ['name' => [$data['name'] . '已被添加过']]], 422);
        }

        return $this->respondWithData(['upNum' => $this->customMenu->update($id, $data)]);
      } else {
        $data = [
          'title' => '修改自定义菜单',
          'menu' => $this->customMenu->load($id),
          'action' => $this->urlFor('wx.custom_menu.edit', ['id' => $id]),
        ];

        return $this->respondView('@weixin/custom-menu/menu-form.twig', $data);
      }
    } else {
      return $this->respondWithError('ID有误');
    }
  }
}