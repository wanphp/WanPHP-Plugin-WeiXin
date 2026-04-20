<?php

namespace WanPHP\Plugins\WeiXin\Application\Admin\User;

use Psr\Http\Message\ResponseInterface as Response;
use WanPHP\Core\Action;
use WanPHP\Core\Attribute\Route;
use WanPHP\Core\Middleware\AdminPermissionMiddlewareInterface;
use WanPHP\Core\Repositories\WeiXin\WeChatBase;
use WanPHP\Core\Service\UserService;
use WanPHP\Plugins\WeiXin\Service\OfficialAccountService;

class UpdateUser extends Action
{
  public function __construct(
    private readonly UserService            $user,
    private readonly OfficialAccountService $officialAccountService,
    private readonly WeChatBase             $weChatBase
  )
  {
  }

  #[Route(
    path: '/admin/wx/user/update/{openid}',
    methods: ['GET', 'POST'],
    description: '更新用户信息',
    name: 'wx.user.update',
    middleware: [AdminPermissionMiddlewareInterface::class]
  )]
  protected function action(): Response
  {
    $openid = $this->resolveArg('openid');
    if (empty($openid)) return $this->respondWithError('缺少openid');
    if ($this->isPost()) {
      $data = $this->getFormData();
      if (empty($data)) return $this->respondWithError('无用户数据');
      $num = $this->user->updateEntityToArray($data, ['openid' => $openid]);
      return $this->respondWithData(['upNum' => $num]);
    } else {
      $userinfo = $this->weChatBase->getUserInfo($openid);
      if ($userinfo['subscribe']) {//用户已关注公众号
        $pubData = [
          'subscribe' => $userinfo['subscribe'],
          'tag_id_list[JSON]' => $userinfo['tagid_list'],
          'subscribe_time' => $userinfo['subscribe_time'],
          'subscribe_scene' => $userinfo['subscribe_scene']
        ];
        $this->officialAccountService->updateEntityToArray($pubData, ['openid' => $openid]);
        return $this->respondWithData($pubData);
      } else {
        return $this->respondWithError('用户还未关注公众号');
      }
    }

  }
}