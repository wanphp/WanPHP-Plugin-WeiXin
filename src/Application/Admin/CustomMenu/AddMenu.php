<?php

namespace WanPHP\Plugins\WeiXin\Application\Admin\CustomMenu;

use Exception;
use Psr\Http\Message\ResponseInterface as Response;
use WanPHP\Core\Action;
use WanPHP\Core\Attribute\Route;
use WanPHP\Core\Middleware\AdminPermissionMiddlewareInterface;
use WanPHP\Plugins\WeiXin\Service\CustomMenuService;

class AddMenu extends Action
{

  public function __construct(private readonly CustomMenuService $customMenu)
  {
  }

  #[Route(
    path: '/admin/wx/custom_menu/add/{tag_id:[0-9]+}[/{parent_id:[0-9]+}]',
    methods: ['GET', 'POST'],
    description: '添加菜单',
    name: 'wx.custom_menu.add',
    middleware: [AdminPermissionMiddlewareInterface::class]
  )]
  protected function action(): Response
  {
    if ($this->isPost()) {
      $data = $this->getFormData();
      // 检查关键词是否已被使用
      if ($this->customMenu->checkMenu(['name' => $data['name'], 'tag_id' => $data['tag_id']])) {
        return $this->respondWithData(['message' => '菜单已被添加过,重新换一个', 'errors' => ['name' => [$data['name'] . '已被添加过']]], 422);
      }
      $id = $this->customMenu->save($data);
      return $this->respondWithData(['id' => $id], 201);
    } else {
      try {
        $tag_id = (int)$this->resolveArg('tag_id', 0);
        $parent_id = (int)$this->resolveArg('parent_id', 0);
        //$userTags = $this->wechatBase->getTags();
        //$tags = array_column($userTags, 'name', 'id');
        //$tag = $tags[$data['tag_id']] ?? '默认';
        $tag = '默认';
        if ($parent_id > 0) {
          // 二级菜单最多5个
          $subMenu = $this->customMenu->count(['tag_id' => $tag_id, 'parent_id' => $parent_id]);
          if ($subMenu == 5) return $this->respondWithError('二级菜单最多5个');
          $parent = $this->customMenu->getColumn('name', ['id' => $parent_id]);
          $title = "添加{$tag}组“{$parent}”的二级菜单";
        } else {
          $menu = $this->customMenu->count(['tag_id' => $tag_id, 'parent_id' => 0]);
          if ($menu == 5) return $this->respondWithError('一级菜单最多3个');
          $title = "添加{$tag}组一级菜单";
        }

        $data = [
          'title' => $title,
          'action' => $this->urlFor('wx.custom_menu.add', ['tag_id' => $tag_id, 'parent_id' => $parent_id]),
          'tag_id' => $tag_id,
          'parent_id' => $parent_id,
        ];

        return $this->respondView('@weixin/custom-menu/menu-form.twig', $data);
      } catch (Exception $exception) {
        return $this->respondWithError($exception->getMessage());
      }
    }
  }
}