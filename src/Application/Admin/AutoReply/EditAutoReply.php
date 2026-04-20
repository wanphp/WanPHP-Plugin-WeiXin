<?php

namespace WanPHP\Plugins\WeiXin\Application\Admin\AutoReply;

use Psr\Http\Message\ResponseInterface as Response;
use WanPHP\Core\Action;
use WanPHP\Core\Attribute\Route;
use WanPHP\Core\Middleware\AdminPermissionMiddlewareInterface;
use WanPHP\Plugins\WeiXin\Service\AutoReplyService;

class EditAutoReply extends Action
{

  public function __construct(private readonly AutoReplyService $autoReply)
  {
  }

  #[Route(
    path: '/admin/wx/auto_reply/edit/{id:[0-9]+}',
    methods: ['GET', 'POST'],
    description: '修改公众号自动回复',
    name: 'wx.auto_reply.edit',
    middleware: [AdminPermissionMiddlewareInterface::class]
  )]
  protected function action(): Response
  {
    $id = (int)$this->resolveArg('id', 0);
    if ($id > 0) {
      if ($this->isPost()) {
        $data = $this->getFormData();
        // 检查关键词是否已被使用
        if ($this->autoReply->checkKey($data['key'], $id)) {
          return $this->respondWithData(['message' => '关键词已被添加过,重新换一个', 'errors' => ['key' => [$data['key'] . '已被添加过']]], 422);
        }

        return $this->respondWithData(['upNum' => $this->autoReply->update($id, $data)]);
      } else {
        $data = [
          'title' => '修改公众号自动回复',
          'reply' => $this->autoReply->getReply($id),
          'action' => $this->urlFor('wx.auto_reply.edit', ['id' => $id]),
          'modalName' => 'wx.auto_reply.modal',
        ];

        return $this->respondView('@weixin/auto-reply/modal.twig', $data);
      }
    } else {
      return $this->respondWithError('ID有误');
    }
  }
}