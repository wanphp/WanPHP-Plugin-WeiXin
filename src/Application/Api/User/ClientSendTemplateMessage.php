<?php

namespace WanPHP\Plugins\WeiXin\Application\Api\User;

use OpenApi\Attributes as OA;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Log\LoggerInterface;
use WanPHP\Core\Action;
use WanPHP\Core\Attribute\Route;
use WanPHP\Core\Middleware\ResourceServerMiddleware;
use WanPHP\Core\Middleware\ScopeMiddleware;
use WanPHP\Core\Repositories\WeiXin\WeChatBase;

class ClientSendTemplateMessage extends Action
{

  public function __construct(private readonly WeChatBase $weChatBase, private readonly LoggerInterface $logger)
  {
  }

  #[Route(
    path: '/api/wx/client/sendMsg',
    methods: ['POST'],
    description: '发送模板消息',
    name: 'wx.sendTemplateMessage',
    middleware: [[ScopeMiddleware::class, 'message.send'], ResourceServerMiddleware::class]
  )]
  #[OA\Post(
    path: "/api/wx/client/sendMsg",
    operationId: "sendTemplateMessage",
    summary: "公众号发送模板消息",
    requestBody: new OA\RequestBody(
      required: true,
      content: new OA\JsonContent(
        required: ["users", "msgData"],
        properties: [
          new OA\Property(property: "users", description: "用户openid", type: "array", items: new OA\Items(type: "string"), example: ["oUp6S5S_****uA40", "oUp6S5S_****uB51"]),
          new OA\Property(property: "msgData", description: "模板消息数据", type: "array", items: new OA\Items(type: "string"))
        ]
      )
    ),
    tags: ["officialAccount"],
    responses: [
      new OA\Response(response: 200, description: "返回发送成功|失败数量", content: new OA\JsonContent()),
      new OA\Response(response: 400, description: "参数错误")
    ]
  )]
  protected function action(): Response
  {
    $post = $this->request->getParsedBody();
    if (empty($post['users'])) return $this->respondWithData(['ok' => '0', 'message' => '未检测到用户ID']);
    if (empty($post['msgData'])) return $this->respondWithData(['ok' => '0', 'message' => '无模板信息内容']);
    $ok = 0;
    $error = 0;
    $msgData = $post['msgData'];
    foreach ($post['users'] as $openid) {
      $msgData['touser'] = $openid;
      try {
        $this->weChatBase->sendTemplateMessage($msgData);
        $ok++;
      } catch (\Exception $exception) {
        $error++;
        $this->logger->debug($exception->getMessage());
      }
    }
    return $this->respondWithData(['ok' => $ok, 'error' => $error]);
  }
}
