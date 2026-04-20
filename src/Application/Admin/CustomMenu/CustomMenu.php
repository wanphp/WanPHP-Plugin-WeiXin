<?php
/**
 * Created by PhpStorm.
 * User: 火子 QQ：284503866.
 * Date: 2021/3/9
 * Time: 9:00
 */

namespace WanPHP\Plugins\WeiXin\Application\Admin\CustomMenu;


use Exception;
use Psr\Http\Message\ResponseInterface as Response;
use WanPHP\Core\Action;
use WanPHP\Core\Attribute\Route;
use WanPHP\Core\Middleware\AdminPermissionMiddlewareInterface;
use WanPHP\Core\Repositories\WeiXin\WeChatBase;
use WanPHP\Plugins\WeiXin\Service\CustomMenuService;

class CustomMenu extends Action
{
  public function __construct(private readonly CustomMenuService $customMenu, private readonly WeChatBase $weChatBase)
  {
  }

  #[Route(
    path: '/admin/wx/custom_menu[/{tag_id:[0-9]+}]',
    methods: ['GET'],
    description: '自定义菜单',
    name: 'wx.custom_menu',
    isNav: true,
    middleware: [AdminPermissionMiddlewareInterface::class]
  )]
  protected function action(): Response
  {
    try {
      if ($this->customMenu->count() == 0) {
        // 同步菜单
        $menus = $this->weChatBase->getMenu();
        if (!empty($menus['menu']['button'])) foreach ($menus['menu']['button'] as $index => $menu) {
          $menu['sortOrder'] = $index + 1;
          $parent_id = $this->customMenu->save($menu);
          if (!empty($menu['sub_button'])) foreach ($menu['sub_button'] as $i => $sub_menu) {
            $menu['sortOrder'] = $i + 1;
            $sub_menu['parent_id'] = $parent_id;
            $this->customMenu->save($sub_menu);
          }
        }
        if (!empty($menus['conditionalmenu'])) foreach ($menus['conditionalmenu'] as $menu) {
          foreach ($menu['button'] as $index => $conditionalMenu) {
            $conditionalMenu['sortOrder'] = $index + 1;
            $conditionalMenu['tag_id'] = $menu['matchrule']['group_id'];
            $parent_id = $this->customMenu->save($conditionalMenu);
            if (!empty($conditionalMenu['sub_button'])) foreach ($conditionalMenu['sub_button'] as $i => $sub_menu) {
              $sub_menu['sortOrder'] = $i + 1;
              $sub_menu['tag_id'] = $menu['matchrule']['group_id'];
              $sub_menu['parent_id'] = $parent_id;
              $this->customMenu->save($sub_menu);
            }
          }
        }
      }
      $userTags = $this->weChatBase->getTags();
    } catch (Exception $exception) {
      // 未认证服务号，无个性化菜单权限
      //return $this->respondWithError($exception->getMessage());
    }

    $data = [
      'tags' => $userTags['tags'] ?? []
    ];
    $data['tag_id'] = intval($this->resolveArg('tag_id', 0));
    $where = ['tag_id' => $data['tag_id'], 'parent_id' => 0, 'ORDER' => ['tag_id' => 'ASC', 'parent_id' => 'ASC', 'sortOrder' => 'ASC']];
    $menus = [];
    foreach ($this->customMenu->select('*', $where) as $item) {
      $where['parent_id'] = $item['id'];
      $item['subBtn'] = $this->customMenu->select('*', $where);
      $menus[] = $item;
    }
    $tags = array_column($data['tags'], 'name', 'id');
    $data['tagName'] = $tags[$data['tag_id']] ?? '默认';
    $data['menus'] = $menus;

    return $this->respondView('@weixin/custom-menu/index.twig', $data);
  }
}
