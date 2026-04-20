<?php

namespace WanPHP\Plugins\WeiXin\Application\Api\User;

use Psr\Http\Message\ResponseInterface as Response;
use WanPHP\Core\Action;
use WanPHP\Core\Attribute\Route;
use WanPHP\Core\Middleware\ResourceServerMiddleware;
use WanPHP\Core\Service\UserService;

class ClientGetScope extends Action
{

  public function __construct(private readonly UserService $user)
  {
  }

  #[Route(
    path: '/api/wx/client/scope/get',
    methods: ['POST'],
    description: 'openid取用户信息',
    name: 'wx.client.scope.get',
    middleware: [ResourceServerMiddleware::class]
  )]
  protected function action(): Response
  {
    if (!$this->isClient()) return $this->respondWithError('非法请求');
    $data = $this->getFormData();
    if (empty($data['scopeIds'])) return $this->respondWithError('无效请求');
    return $this->respondWithData($this->user->getOauthScope((array)$data['scopeIds']));
  }
}