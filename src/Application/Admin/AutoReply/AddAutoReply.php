<?php

namespace WanPHP\Plugins\WeiXin\Application\Admin\AutoReply;

use Psr\Http\Message\ResponseInterface as Response;
use WanPHP\Core\Action;
use WanPHP\Core\Attribute\Route;
use WanPHP\Core\Middleware\AdminPermissionMiddlewareInterface;
use WanPHP\Plugins\WeiXin\Service\AutoReplyService;

class AddAutoReply extends Action
{
  public function __construct(private readonly AutoReplyService $autoReply)
  {
  }

  #[Route(
    path: '/admin/wx/auto_reply/add',
    methods: ['GET','POST'],
    description: '添加公众号自动回复',
    name: 'wx.auto_reply.add',
    middleware: [AdminPermissionMiddlewareInterface::class]
  )]
  protected function action(): Response
  {

    if ($this->isPost()) {
      $data = $this->getFormData();
      // 检查关键词是否已被使用
      if ($this->autoReply->checkKey($data['key'])) {
        return $this->respondWithData(['message' => '关键词已被添加过,重新换一个', 'errors' => ['key' => [$data['key'] . '已被添加过']]], 422);
      }

      $data['id'] = $this->autoReply->save($data);
      return $this->respondWithData($data, 201);
    } else {
      $data = [
        'title' => '添加公众号自动回复',
        'action' => $this->urlFor('wx.auto_reply.add'),
        'modalName' => 'wx.auto_reply.modal',
      ];

      return $this->respondView('@weixin/auto-reply/modal.twig', $data);
    }
  }
}