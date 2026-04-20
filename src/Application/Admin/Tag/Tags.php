<?php
/**
 * Created by PhpStorm.
 * User: 火子 QQ：284503866.
 * Date: 2020/12/28
 * Time: 16:35
 */

namespace WanPHP\Plugins\WeiXin\Application\Admin\Tag;


use Psr\SimpleCache\CacheInterface;
use WanPHP\Core\Action;
use WanPHP\Core\Attribute\Route;
use WanPHP\Core\Middleware\AdminPermissionMiddlewareInterface;
use WanPHP\Core\Repositories\WeiXin\WeChatBase;
use Psr\Http\Message\ResponseInterface as Response;
use Exception;

class Tags extends Action
{
  public function __construct(private readonly WeChatBase $weChatBase, private readonly CacheInterface $cache)
  {
  }

  #[Route(
    path: '/admin/wx/tags',
    methods: ['GET'],
    description: '粉丝标签',
    name: 'wx.user.tags',
    isNav: true,
    middleware: [AdminPermissionMiddlewareInterface::class]
  )]
  protected function action(): Response
  {
    //公众号粉丝数
    try {
      $user_total = $this->cache->get('wxuser_total');
      if (empty($user_total)) {
        $list = $this->weChatBase->getUserList();
        $user_total = $list['total'];
        $this->cache->set('wxuser_total', $user_total, 3600);
      }
      $userTags = $this->weChatBase->getTags();
      $data = [
        'title' => '粉丝标签管理',
        'tags' => $userTags['tags'] ?? [],
        'total' => $user_total
      ];

      return $this->respondView('@weixin/user/tags.twig', $data);
    } catch (Exception $exception) {
      return $this->respondWithError('错误代码：' . $exception->getMessage());
    }
  }
}
