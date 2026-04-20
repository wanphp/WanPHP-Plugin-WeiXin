<?php
/**
 * Created by PhpStorm.
 * User: 火子 QQ：284503866.
 * Date: 2021/3/8
 * Time: 10:24
 */

namespace WanPHP\Plugins\WeiXin\Application\Api\OfficialAccount;


use OpenApi\Attributes as OA;
use Psr\Http\Message\ResponseInterface as Response;
use WanPHP\Core\Action;
use WanPHP\Core\Attribute\Route;
use WanPHP\Core\Repositories\WeiXin\WeChatBase;

class UrlSign extends Action
{
  public function __construct(private readonly WeChatBase $weChatBase)
  {
  }

  #[Route(path: '/wx/getSignPackage', methods: ['POST'], description: '微信JS-SDK URL签名', name: 'wx.signPackage')]
  #[OA\Post(
    path: "/wx/getSignPackage",
    operationId: "signPackage",
    summary: "微信JS-SDK URL签名",
    requestBody: new OA\RequestBody(
      required: true,
      content: new OA\JsonContent(
        required: ["url"],
        properties: [
          new OA\Property(property: "url", description: "签名URL", type: "string", example: 'https://www.example.com')
        ]
      )
    ),
    tags: ["officialAccount"],
    responses: [
      new OA\Response(response: 200, description: "返回签名信息", content: new OA\JsonContent(
        properties: [
          new OA\Property(property: "appId", description: "公众号的唯一标识", type: "string"),
          new OA\Property(property: "timestamp", description: "生成签名的时间戳", type: "string"),
          new OA\Property(property: "nonceStr", description: "生成签名的随机串", type: "string"),
          new OA\Property(property: "signature", description: "签名", type: "string")
        ]
      )),
      new OA\Response(response: 400, description: "参数错误", content: new OA\JsonContent(ref: "#/components/schemas/Error"))
    ]
  )]
  protected function action(): Response
  {
    $data = $this->getFormData();
    if (empty($data['url'])) return $this->respondWithError('缺少签名url');
    return $this->respondWithData($this->weChatBase->getSignPackage($data['url']));
  }
}
