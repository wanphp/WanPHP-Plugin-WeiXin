<?php

namespace WanPHP\Plugins\WeiXin\Application\Api\User;

use OpenApi\Attributes as OA;
use Psr\Http\Message\ResponseInterface as Response;
use WanPHP\Core\Action;
use WanPHP\Core\Attribute\Route;
use WanPHP\Core\Middleware\ResourceServerMiddleware;
use WanPHP\Core\Middleware\ScopeMiddleware;
use WanPHP\Core\Service\UserService;

class ClientSearchUser extends Action
{

  public function __construct(private readonly UserService $user)
  {
  }

  #[Route(
    path: '/api/wx/client/user/search',
    methods: ['GET'],
    description: '搜索用户信息',
    name: 'wx.client.user.search',
    middleware: [[ScopeMiddleware::class, 'user.read'], ResourceServerMiddleware::class]
  )]
  #[OA\Get(
    path: "/api/wx/client/user/search",
    operationId: "searchUser",
    summary: "搜索用户信息",
    tags: ["wx.User"],
    parameters: [
      new OA\Parameter(
        name: "q",
        description: "搜索关键词",
        in: "query",
        required: true,
        schema: new OA\Schema(type: "string", example: "张三")
      ),
      new OA\Parameter(
        name: "page",
        description: "页码",
        in: "query",
        schema: new OA\Schema(type: "string", example: "1")
      ),
    ],
    responses: [
      new OA\Response(response: 200, description: "返回用户信息", content: new OA\JsonContent()),
      new OA\Response(response: 400, description: "参数错误")
    ]
  )]
  protected function action(): Response
  {
    if (!$this->isClient()) $this->respondWithError('无权限');
    $params = $this->request->getQueryParams();
    if (!empty($params['q'])) {
      $keyword = trim($params['q']);
      return $this->respondWithData($this->user->searchUsers($keyword, intval($params['page'] ?? 1)));
    } else {
      return $this->respondWithError('关键词不能为空！');
    }
  }
}