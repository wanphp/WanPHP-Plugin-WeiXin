<?php

namespace WanPHP\Plugins\WeiXin\Application\Admin\Message;

use Psr\Http\Message\ResponseInterface as Response;
use WanPHP\Core\Action;
use WanPHP\Core\Attribute\Route;
use WanPHP\Core\Middleware\AdminPermissionMiddlewareInterface;
use WanPHP\Core\Repositories\WeiXin\WeChatBase;

class DeleteTemplate extends Action
{
  public function __construct(private readonly WeChatBase $wechatBase)
  {
  }

  #[Route(
    path: '/admin/wx/template/message/{template_id}',
    methods: ['DELETE'],
    description: '删除消息模板',
    name: 'wx.message.template.delete',
    middleware: [AdminPermissionMiddlewareInterface::class]
  )]
  protected function action(): Response
  {
    $template_id = $this->resolveArg('template_id');
    if ($template_id) {
      $result = $this->wechatBase->delTemplateMessage($template_id);
      if ($result && $result['errcode'] == 0) {
        return $this->respondWithData($result, 204);
      } else {
        return $this->respondWithError($result['errmsg']);
      }
    } else {
      return $this->respondWithError('缺少模板ID');
    }
  }
}