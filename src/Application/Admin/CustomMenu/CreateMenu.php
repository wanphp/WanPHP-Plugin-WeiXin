<?php
/**
 * Created by PhpStorm.
 * User: 火子 QQ：284503866.
 * Date: 2021/3/11
 * Time: 16:52
 */

namespace WanPHP\Plugins\WeiXin\Application\Admin\CustomMenu;


use Psr\Http\Message\ResponseInterface as Response;
use WanPHP\Core\Action;
use WanPHP\Core\Attribute\Route;
use WanPHP\Core\Middleware\AdminPermissionMiddlewareInterface;
use WanPHP\Core\Repositories\WeiXin\WeChatBase;
use WanPHP\Plugins\WeiXin\Service\CustomMenuService;

class CreateMenu extends Action
{
  public function __construct(private readonly WeChatBase $weChatBase, private readonly CustomMenuService $customMenu)
  {
  }

  #[Route(
    path: '/admin/wx/custom_menu/create/{tag_id:[0-9]+}',
    methods: ['POST'],
    description: '创建公众号自定义菜单',
    name: 'wx.custom_menu.create',
    middleware: [AdminPermissionMiddlewareInterface::class]
  )]
  protected function action(): Response
  {
    $tag_id = $this->resolveArg('tag_id', 0);
    $where = ['tag_id' => $tag_id, 'parent_id' => 0, 'ORDER' => ['tag_id' => 'ASC', 'parent_id' => 'ASC', 'sortOrder' => 'ASC']];
    $menus = [];
    foreach ($this->customMenu->select('*', $where) as $item) {
      $menu = ['name' => $item['name']];
      $where['parent_id'] = $item['id'];
      $subMenus = $this->customMenu->select('*', $where);
      if (count($subMenus) > 0) {
        foreach ($subMenus as $btn) {
          $subBtn = ['name' => $btn['name'], 'type' => $btn['type']];
          $menu['sub_button'][] = $this->getSubBtn($btn, $subBtn);
        }
      } else {
        $menu['type'] = $item['type'];
        $menu = $this->getSubBtn($item, $menu);
      }
      $menus[] = $menu;
    }
    if ($menus) {
      if ($tag_id == 0) {
        $result = $this->weChatBase->createMenu(['button' => $menus]);
      } else {
        $result = $this->weChatBase->addconditional(['button' => $menus, 'matchrule' => ['tag_id' => $tag_id]]);
      }
      if ($result['errcode'] == 0) {
        $result['message'] = '菜单生成成功!';
        return $this->respondWithData($result);
      }
      return $this->respondWithError($result['errmsg']);
    } else {
      return $this->respondWithError('菜单为空！');
    }
  }

  /**
   * @param mixed $btn
   * @param array $subBtn
   * @return array
   */
  private function getSubBtn(mixed $btn, array $subBtn): array
  {
    switch ($btn['type']) {
      case 'view':
        $subBtn['url'] = $btn['url'];
        break;
      case 'miniprogram':
        $subBtn['url'] = $btn['url'];
        $subBtn['appid'] = $btn['appid'];
        $subBtn['pagepath'] = $btn['pagepath'];
        break;
      default:
        $subBtn['key'] = $btn['key'];
    }
    return $subBtn;
  }
}
