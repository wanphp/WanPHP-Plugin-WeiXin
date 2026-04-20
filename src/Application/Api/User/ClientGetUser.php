<?php

namespace WanPHP\Plugins\WeiXin\Application\Api\User;

use OpenApi\Attributes as OA;
use Psr\Http\Message\ResponseInterface as Response;
use WanPHP\Core\Action;
use WanPHP\Core\Attribute\Route;
use WanPHP\Core\Middleware\ResourceServerMiddleware;
use WanPHP\Core\Middleware\ScopeMiddleware;
use WanPHP\Core\Service\UserService;

class ClientGetUser extends Action
{
  public function __construct(private readonly UserService $user)
  {
  }

  #[Route(
    path: '/api/wx/client/user/get',
    methods: ['POST'],
    description: 'openid取用户信息',
    name: 'wx.client.user.get',
    middleware: [[ScopeMiddleware::class, 'user.read'], ResourceServerMiddleware::class]
  )]
  #[OA\Post(
    path: "/api/wx/client/user/get",
    operationId: "getUser",
    summary: "获取用户信息（支持单个或多个 OpenID）",
    requestBody: new OA\RequestBody(
      required: true,
      content: new OA\JsonContent(
        required: ["openid"],
        properties: [
          new OA\Property(
            property: "openid",
            description: "用户openid，支持传单个字符串或字符串数组",
            oneOf: [
              // 情况 1：单个字符串
              new OA\Schema(type: "string", example: "oUp6S5S_****uA40"),
              // 情况 2：字符串数组
              new OA\Schema(
                type: "array",
                items: new OA\Items(type: "string"),
                example: ["oUp6S5S_****uA40", "oUp6S5S_****uB51"]
              )
            ]
          )
        ]
      )
    ),
    tags: ["wx.User"],
    responses: [
      new OA\Response(response: 200, description: "返回用户信息", content: new OA\JsonContent()),
      new OA\Response(response: 400, description: "参数错误")
    ]
  )]
  protected function action(): Response
  {
    if (!$this->isClient()) return $this->respondWithData($this->user->getUser($this->getUid()));
    $data = $this->getFormData();
    if (empty($data['openid'])) return $this->respondWithError('无用户OPENID');
    if (is_array($data['openid'])) return $this->respondWithData($this->user->getUsers($data['openid']));
    else return $this->respondWithData($this->user->getUser($data['openid']));
  }
}
