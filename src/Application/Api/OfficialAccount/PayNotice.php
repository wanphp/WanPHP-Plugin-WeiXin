<?php
/**
 * Created by PhpStorm.
 * User: 火子 QQ：284503866.
 * Date: 2020/12/29
 * Time: 14:46
 */

namespace WanPHP\Plugins\WeiXin\Application\Api\OfficialAccount;


use OpenApi\Attributes as OA;
use WanPHP\Core\Action;
use WanPHP\Core\Attribute\Route;
use Psr\Http\Message\ResponseInterface as Response;
use WanPHP\Core\Repositories\WeiXin\WeChatPay;
use WeChatPay\Exception\WeChatPayException;

abstract class PayNotice extends Action
{

  public function __construct(protected WeChatPay $wechatPay)
  {
  }

  #[Route(path: '/wx/pay/notice', methods: ['POST'], description: '支付结果通知回调', name: 'wx.pay.notice')]
  #[OA\Post(
    path: "/wx/pay/notice",
    summary: "微信支付结果通知回调",
    security: [],
    requestBody: new OA\RequestBody(
      description: "微信支付回调加密报文",
      required: true,
      content: new OA\JsonContent()
    ),
    tags: ["Pay"],
    responses: [
      new OA\Response(
        response: 200,
        description: "接收成功",
        content: new OA\JsonContent(
          properties: [
            new OA\Property(property: "code", type: "string", example: "SUCCESS"),
            new OA\Property(property: "message", type: "string", example: "成功")
          ]
        )
      ),
      new OA\Response(response: 500, description: "接收失败")
    ]
  )]
  protected function action(): Response
  {
    try {
      return $this->respondWithData($this->notify($this->wechatPay->notify($this->request)));
    } catch (WeChatPayException $e) {
      return $this->respondWithError($e->getMessage());
    }
  }

  abstract function notify(array $data): array;
}
