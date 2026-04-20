<?php

namespace WanPHP\Plugins\WeiXin\Application\Admin\Message;

use Psr\Http\Message\ResponseInterface as Response;
use WanPHP\Core\Action;
use WanPHP\Core\Attribute\Route;
use WanPHP\Core\Middleware\AdminPermissionMiddlewareInterface;
use WanPHP\Core\Repositories\WeiXin\WeChatBase;

class AddTemplate extends Action
{

  public function __construct(private readonly WeChatBase $wechatBase)
  {
  }

  #[Route(
    path: '/admin/wx/template/message/add',
    methods: ['POST'],
    description: '添加消息模板',
    name: 'wx.message.template.add',
    middleware: [AdminPermissionMiddlewareInterface::class]
  )]
  protected function action(): Response
  {
    $data = $this->getFormData();
    if (empty($data['template_id'])) return $this->respondWithError('模板ID不能为空');
    $result = $this->wechatBase->addTemplateMessage($data['template_id']);
    if ($result && $result['errcode'] == 0) {
      $result['message'] = '模板添加成功';
      return $this->respondWithData($result, 201);
    } else {
      return $this->respondWithError($result['errmsg']);
    }
  }
}