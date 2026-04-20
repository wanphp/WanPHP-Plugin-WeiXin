<?php

namespace WanPHP\Plugins\WeiXin\Application\Admin\AutoReply;

use Psr\Http\Message\ResponseInterface as Response;
use WanPHP\Core\Action;
use WanPHP\Core\Attribute\Route;
use WanPHP\Core\Middleware\AdminPermissionMiddlewareInterface;
use WanPHP\Plugins\WeiXin\Service\AutoReplyService;

class DeleteAutoReply extends Action
{

  public function __construct(private readonly AutoReplyService $autoReply)
  {
  }

  #[Route(
    path: '/admin/wx/auto_reply/delete/{id:[0-9]+}',
    methods: ['DELETE'],
    description: '删除公众号自动回复',
    name: 'wx.auto_reply.delete',
    middleware: [AdminPermissionMiddlewareInterface::class]
  )]
  protected function action(): Response
  {
    $id = (int)$this->resolveArg('id', 0);
    if ($id > 0) {
      return $this->respondWithData(['upNum' => $this->autoReply->delete($id)], 204);
    } else {
      return $this->respondWithError('ID有误');
    }
  }
}