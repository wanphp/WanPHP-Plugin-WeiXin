<?php
/**
 * Created by PhpStorm.
 * User: 火子 QQ：284503866.
 * Date: 2020/12/29
 * Time: 14:59
 */

namespace WanPHP\Plugins\WeiXin\Application\Admin\Message;


use Exception;
use WanPHP\Core\Action;
use WanPHP\Core\Attribute\Route;
use WanPHP\Core\Middleware\AdminPermissionMiddlewareInterface;
use WanPHP\Core\Repositories\WeiXin\WeChatBase;
use Psr\Http\Message\ResponseInterface as Response;

class Template extends Action
{

  public function __construct(private readonly WeChatBase $weChatBase)
  {
  }

  #[Route(
    path: '/admin/wx/template/message',
    methods: ['GET'],
    description: '消息模板',
    name: 'wx.message.template',
    isNav: true,
    middleware: [AdminPermissionMiddlewareInterface::class]
  )]
  protected function action(): Response
  {
    if ($this->isAjax()) {
      $templates = $this->weChatBase->templateMessage();
      $list = [];
      foreach ($templates['template_list'] as $template) {
        $template['content'] = nl2br($template['content']);
        $template['example'] = nl2br($template['example']);
        $template['delete'] = $this->urlFor('wx.message.template.delete', ['template_id' => $template['template_id']]);
        $list[] = $template;
      }

      return $this->respondWithData([
        'data' => $list
      ]);
    } else {
      try {
        $industry = $this->weChatBase->getIndustry();
      } catch (Exception) {
        $industry = [];
      }
      $data = [
        'title' => '消息模板管理',
        'industry' => $industry
      ];
      return $this->respondView('@weixin/template/list.twig', $data);
    }
  }
}
