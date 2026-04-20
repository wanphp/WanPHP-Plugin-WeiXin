<?php

namespace WanPHP\Plugins\WeiXin\Application\Admin\User;

use Psr\Http\Message\ResponseInterface as Response;
use WanPHP\Core\Action;
use WanPHP\Core\Attribute\Route;
use WanPHP\Core\Middleware\AdminPermissionMiddlewareInterface;
use WanPHP\Core\Service\UserService;

class SearchUser extends Action
{

  public function __construct(private readonly UserService $user)
  {
  }

  #[Route(
    path: '/admin/wx/search/user',
    methods: ['GET'],
    description: '搜索用户',
    name: 'wx.search.user',
    middleware: [AdminPermissionMiddlewareInterface::class]
  )]
  protected function action(): Response
  {
    $params = $this->request->getQueryParams();
    if (isset($params['q']) && $params['q'] != '') {
      $keyword = trim($params['q']);
    } else {
      return $this->respondWithError('关键词不能为空！');
    }
    $page = intval($params['page'] ?? 1);

    $data = $this->user->searchUsers($keyword, $page);
    return $this->respondWithData($data);
  }
}
