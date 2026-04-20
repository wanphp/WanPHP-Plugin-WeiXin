<?php
/**
 * Created by PhpStorm.
 * User: 火子 QQ：284503866.
 * Date: 2021/3/17
 * Time: 18:08
 */

namespace WanPHP\Plugins\WeiXin\Application\Admin\User;


use Psr\Http\Message\ResponseInterface as Response;
use WanPHP\Core\Action;
use WanPHP\Core\Attribute\Route;
use WanPHP\Core\Middleware\AdminPermissionMiddlewareInterface;
use WanPHP\Core\Repositories\WeiXin\WeChatBase;
use WanPHP\Plugins\WeiXin\Service\OfficialAccountService;

class UserTag extends Action
{

  public function __construct(
    private readonly OfficialAccountService $officialAccountService,
    private readonly WeChatBase             $weChatBase)
  {
  }

  #[Route(
    path: '/admin/wx/user/tag/{openid}',
    methods: ['PATCH', 'DELETE'],
    description: '粉丝打标签',
    name: 'wx.user.tag',
    middleware: [AdminPermissionMiddlewareInterface::class]
  )]
  protected function action(): Response
  {
    $openid = $this->resolveArg('openid');
    if (empty($openid)) return $this->respondWithError('缺少openid');
    $data = $this->getFormData();
    $tagId = $data['tagId'] ?? $this->request->getQueryParams()['tagId'] ?? '';
    if (empty($tagId)) return $this->respondWithError('缺少tagId');
    switch ($this->request->getMethod()) {
      case 'PATCH':
        $result = $this->weChatBase->membersTagging($tagId, [$openid]);
        if ($result['errcode'] == 0) {
          $tag_id_list = $this->officialAccountService->getColumn('tag_id_list[JSON]', ['openid' => $openid]);
          $tag_id_list[] = $tagId;
          $this->officialAccountService->updateEntityToArray(['tag_id_list[JSON]' => array_unique($tag_id_list)], ['openid' => $openid]);
          $result['message'] = '加入成功';
        }
        return $this->respondWithData($result);
      case 'DELETE':
        $result = $this->weChatBase->membersUnTagging($tagId, [$openid]);
        if ($result['errcode'] == 0) {
          $tag_id_list = $this->officialAccountService->getColumn('tag_id_list[JSON]', ['openid' => $openid]);
          $tag_id_list = array_values(array_diff($tag_id_list, [$tagId]));
          $this->officialAccountService->updateEntityToArray(['tag_id_list[JSON]' => $tag_id_list], ['openid' => $openid]);
          $result['message'] = '取消成功';
        }
        return $this->respondWithData($result, 201);
      default:
        return $this->respondWithError('禁止访问', 403);
    }
  }
}
