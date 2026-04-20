<?php
/**
 * Created by PhpStorm.
 * User: 火子 QQ：284503866.
 * Date: 2020/12/28
 * Time: 15:48
 */

namespace WanPHP\Plugins\WeiXin\Application\Admin\User;


use Exception;
use GuzzleHttp\Client;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\SimpleCache\CacheInterface;
use WanPHP\Core\Action;
use WanPHP\Core\Attribute\Route;
use WanPHP\Core\Middleware\AdminPermissionMiddlewareInterface;
use WanPHP\Core\Service\UserService;
use WanPHP\Core\Traits\HttpTrait;
use WanPHP\Core\Repositories\WeiXin\WeChatBase;
use WanPHP\Plugins\WeiXin\Service\OfficialAccountService;

class UserList extends Action
{
  use HttpTrait;

  private string $appid;

  public function __construct(
    private readonly UserService            $user,
    private readonly OfficialAccountService $officialAccountService,
    private readonly WeChatBase             $weChatBase,
    private readonly CacheInterface         $cache)
  {
    $this->appid = getenv('WECHAT_OFFICIAL_ACCOUNT_APPID') ?: '';
  }

  #[Route(
    path: '/admin/wx/user/list',
    methods: ['GET'],
    description: '用户管理',
    name: 'wx.user.list',
    isNav: true,
    middleware: [AdminPermissionMiddlewareInterface::class]
  )]
  protected function action(): Response
  {
    if ($this->isAjax()) {
      $params = $this->request->getQueryParams();

      $data = $this->user->getUserList($params);
      $users = $data['users'];
      $recordsFiltered = $data['total'];

      $userList = [];
      if (!empty($users)) {
        foreach ($users as $user) {
          $user['update'] = $this->urlFor('wx.user.update', ['openid' => $user['openid']]);
          $user['tag_action'] = $this->urlFor('wx.user.tag', ['openid' => $user['openid']]);
          $userList[$user['openid']] = $user;
        }
      }

      $data = [
        "draw" => $params['draw'],
        "recordsTotal" => $this->user->count(),
        "recordsFiltered" => $recordsFiltered,
        'data' => array_values($userList),
      ];
      if ($data['recordsTotal'] == 0) {
        $data['syncFollowers'] = $this->urlFor('wx.syncFollowers');
      }
      // 是否有未同步基本信息的用户
      if ($total = $this->officialAccountService->count(['subscribe' => 1, 'subscribe_time' => ''])) {
        $data['syncFollowersInfo'] = $this->urlFor('wx.syncFollowers');
        $data['sync_total'] = $data['recordsTotal'] - $total;
      }

      return $this->respondWithData($data);
    } else {
      $data = [
        'title' => '微信用户管理',
        'tags' => [],
        'issetCookie' => $this->cache->has('forever_' . $this->appid . '_official_account_cookie')
      ];
      try {
        $userTags = $this->weChatBase->getTags();
        $data['tags'] = $userTags['tags'];
        $userTags = array_column($userTags['tags'], 'name', 'id');
        $data['userTags'] = json_encode($userTags);
      } catch (Exception) {
        // 无获取用户标签权限
      }
      return $this->respondView('@weixin/user/user-list.twig', $data);
    }
  }


  /**
   * @throws Exception
   */
  #[Route(
    path: '/admin/wx/syncFollowers',
    methods: ['POST'],
    description: '设置公众号Cookie',
    name: 'wx.syncFollowers',
    middleware: [AdminPermissionMiddlewareInterface::class]
  )]
  public function syncFollowers(ServerRequestInterface $request, Response $response, array $args): Response
  {
    $this->request = $request;
    $this->response = $response;
    $this->args = $args;

    $data = $this->getFormData();
    if (!empty($data['user_info'])) {
      if ($this->cache->has('forever_' . $this->appid . '_official_account_cookie')) {
        $cookie = $this->cache->get('forever_' . $this->appid . '_official_account_cookie');
        $next_openid = $_SESSION['next_openid'] ?? '';
        $begin_create_time = $_SESSION['begin_create_time'] ?? time();
        $res = $this->request(new Client(), 'GET', 'https://mp.weixin.qq.com/cgi-bin/user_tag?action=get_user_list&groupid=-2&begin_openid=' . $next_openid . '&begin_create_time=' . $begin_create_time . '&limit=20&offset=0&backfoward=1&token=' . $cookie['token'] . '&lang=zh_CN&f=json&ajax=1&random=' . (mt_rand() / mt_getrandmax()),
          [
            'headers' => [
              'cookie' => trim($cookie['cookies'])
            ]
          ]);
        $userUpdate = [];
        $oaUpdate = [];
        if (!empty($res['user_list']['user_info_list'])) {
          foreach ($res['user_list']['user_info_list'] as $user) {
            $userUpdate[] = [
              'openid' => $user['user_openid'],
              'nickname' => $user['user_name'],
              'avatar' => parse_url($user['user_head_img'], PHP_URL_PATH),
              'remark' => $user['user_remark'],
              'createdAt' => $user['user_create_time']
            ];
            $oaUpdate[] = [
              'openid' => $user['user_openid'],
              'subscribe' => 1,
              'tag_id_list' => $user['user_group_id'],
              'subscribe_time' => $user['user_create_time']
            ];
            $next_openid = $user['user_openid'];
            $begin_create_time = $user['user_create_time'];
          }
        } else {
          return $this->respondWithError('未取到用户信息');
        }
        $num = $this->user->batchUpdate($userUpdate, 'openid');
        $num += $this->officialAccountService->batchUpdate($oaUpdate, 'openid');

        if (empty($data['user_total'])) $data['user_total'] = $this->user->count();
        if (empty($data['sync_total'])) $data['sync_total'] = $this->user->count(['createdAt[>=]' => $begin_create_time]);
        $user_total = $data['user_total'];
        $sync_total = $data['sync_total'] + count($oaUpdate);

        $_SESSION['next_openid'] = $next_openid;
        $_SESSION['begin_create_time'] = $begin_create_time;
        return $this->respondWithData([
          'syncFollowers' => count($oaUpdate),
          'num' => $num ?? 0,
          'user_total' => $user_total,
          'sync_total' => $sync_total
        ]);
      } else {
        // 同步用户基本信息，取未同步用户的openid
        $openid = array_map(fn($val) => ['openid' => $val],
          $this->officialAccountService->select('openid', ['subscribe' => 1, 'subscribe_time' => '', 'LIMIT' => 100])
        );

        // 更新关注用户信息
        if (!empty($openid)) {
          try {
            $userListInfo = $this->weChatBase->getUserListInfo($openid);
            $userUpdate = [];
            $oaUpdate = [];
            if (!empty($userListInfo['user_info_list'])) foreach ($userListInfo['user_info_list'] as $userinfo) {
              if ($userinfo['subscribe']) {
                $userUpdate[] = [
                  'openid' => $userinfo['openid'],
                  'createdAt' => $userinfo['subscribe_time']
                ];
                $oaUpdate[] = [
                  'openid' => $userinfo['openid'],
                  'subscribe' => 1,
                  'tag_id_list' => $userinfo['tagid_list'],
                  'subscribe_time' => $userinfo['subscribe_time'],
                  'subscribe_scene' => $userinfo['subscribe_scene']
                ];
              } else {
                $oaUpdate[] = [
                  'openid' => $userinfo['openid'],
                  'subscribe' => 0
                ];
              }
            }
            $num = $this->user->batchUpdate($userUpdate, 'openid');
            $num += $this->officialAccountService->batchUpdate($oaUpdate, 'openid');
          } catch (Exception $e) {
            // 无获取用户信息权限
            return $this->respondWithError($e->getMessage());
          }
        }
        $user_total = $data['user_total'];
        $sync_total = $data['sync_total'] + count($openid);
        return $this->respondWithData(['syncFollowers' => count($openid), 'num' => $num ?? 0, 'user_total' => $user_total, 'sync_total' => $sync_total]);
      }
    } else {
      $nextOpenid = $data['next_openid'] ?? '';
      if (empty($nextOpenid) && $this->user->count() > 0) {
        return $this->respondWithData();
      }

      // 公众号刚加入，本地无用户，同步已关注粉丝
      $users = $this->weChatBase->getUserList($nextOpenid);
      $user_total = $users['total'];
      $sync_total = $data['sync_total'] + $users['count'];
      $userData = [];
      $officialAccountUserData = [];
      foreach ($users['data']['openid'] as $openid) {
        $userData[] = ['openid' => $openid, 'status' => 1, 'createdAt' => 0];
        $officialAccountUserData[] = ['openid' => $openid, 'tag_id_list[JSON]' => [], 'subscribe' => 1];
      }

      foreach (array_chunk($userData, 1000) as $chunk) {
        $this->user->insertEntityToArray($chunk);
      }
      foreach (array_chunk($officialAccountUserData, 1000) as $chunk) {
        $this->officialAccountService->insertEntityToArray($chunk);
      }

      if ($users['count'] < 10000) return $this->respondWithData();
      return $this->respondWithData(['next_openid' => $users['next_openid'], 'user_total' => $user_total, 'sync_total' => $sync_total]);
    }
  }

  /**
   * 设置公众号Cookie
   * @param Request $request
   * @param Response $response
   * @param array $args
   * @return Response
   * @throws Exception
   */
  #[Route(
    path: '/admin/wx/setCookie',
    methods: ['POST'],
    description: '设置公众号Cookie',
    name: 'wx.setCookie',
    middleware: [AdminPermissionMiddlewareInterface::class]
  )]
  public function setCookie(Request $request, Response $response, array $args): Response
  {
    $this->request = $request;
    $this->response = $response;
    $this->args = $args;
    $data = $this->getFormData();
    if (!empty($this->appid)) {
      // 设置缓存
      $this->cache->set('forever_' . $this->appid . '_official_account_cookie', $data, 316800);// 88小时
      return $this->respondWithData(['message' => '设置成功！']);
    } else {
      return $this->respondWithError('Error!');
    }
  }
}
