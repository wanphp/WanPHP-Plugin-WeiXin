<?php
/**
 * Created by PhpStorm.
 * User: 火子 QQ：284503866.
 * Date: 2020/9/25
 * Time: 10:48
 */

namespace WanPHP\Plugins\WeiXin\Application\Api\User;


use Exception;
use OpenApi\Attributes as OA;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use WanPHP\Core\Action;
use WanPHP\Core\Attribute\Route;
use WanPHP\Core\Entities\UserEntity;
use WanPHP\Core\Middleware\ResourceServerMiddleware;
use WanPHP\Core\Middleware\ScopeMiddleware;
use WanPHP\Core\Service\UserService;
use WanPHP\Plugins\WeiXin\Service\OfficialAccountService;

class CurrentUser extends Action
{
  /**
   * @param UserService $user
   * @param OfficialAccountService $officialAccountService
   */
  public function __construct(private readonly UserService $user, private readonly OfficialAccountService $officialAccountService)
  {
  }

  #[Route(
    path: '/api/user/update',
    methods: ['POST'],
    description: '更新用户信息',
    name: 'wx.user.update',
    middleware: [[ScopeMiddleware::class, 'user.write'], ResourceServerMiddleware::class]
  )]
  #[OA\Post(
    path: "/api/user/update",
    operationId: "updateUser",
    summary: "更新用户信息",
    requestBody: new OA\RequestBody(
      required: true,
      content: new OA\JsonContent(
        properties: [
          new OA\Property(property: "name", description: "姓名", type: "string", example: "张三"),
          new OA\Property(property: "tel", description: "手机号", type: "string", example: "13800138000"),
          new OA\Property(property: "password", description: "新密码", type: "string", format: "password")
        ]
      )
    ),
    tags: ["wx.User"],
    responses: [
      new OA\Response(
        response: 200,
        description: "更新成功",
        content: new OA\JsonContent(
          properties: [
            new OA\Property(property: "success", description: "是否成功", type: "boolean"),
            new OA\Property(property: "message", description: "说明", type: "string")
          ]
        )
      ),
      new OA\Response(
        response: 400,
        description: "更新失败",
        content: new OA\JsonContent(ref: "#/components/schemas/Error")
      )
    ]
  )]
  protected function action(): Response
  {
    $openid = $this->getUid();
    // 用户自己修改信息
    $data = $this->getFormData();
    $user = new UserEntity();
    if (!empty($data['name'])) $user->setName($data['name']);
    if (!empty($data['tel'])) $user->setTel($data['tel']);
    if (!empty($data['password'])) $user->setPassword($data['password']);
    $updateData = $user->toArray();
    if (empty($updateData)) return $this->respondWithError('无有效用户数据');
    $num = $this->user->updateEntityToArray($updateData, ['openid' => $openid]);
    return $this->respondWithData(['success' => $num > 0, 'message' => $num ? '更新成功！' : '数据没有变动！']);
  }


  /**
   * 用户基本信息
   * @throws Exception
   */
  #[Route(
    path: '/api/userProfile',
    methods: ['GET'],
    description: '用户基本信息',
    name: 'wx.getBasicUser',
    middleware: [[ScopeMiddleware::class, 'user.read'], ResourceServerMiddleware::class]
  )]
  #[OA\Get(
    path: "/api/userProfile",
    summary: "取用户信息",
    tags: ["wx.User"],
    responses: [
      new OA\Response(response: 200, description: "用户信息", content: new OA\JsonContent(
        properties: [
          new OA\Property(property: "name", description: "姓名", type: "string"),
          new OA\Property(property: "tel", description: "手机", type: "string"),
          new OA\Property(property: "nickname", description: "呢称", type: "string"),
          new OA\Property(property: "avatar", description: "头像", type: "string"),
          new OA\Property(property: "tagId", description: "标签", type: "array", items: new OA\Items(type: "int", example: "100"))
        ]
      )),
      new OA\Response(response: 400, description: "请求失败", content: new OA\JsonContent(ref: "#/components/schemas/Error"))
    ]
  )]
  public function userProfile(Request $request, Response $response, array $args): Response
  {
    $this->request = $request;
    $this->response = $response;
    $this->args = $args;

    $openid = $this->getUid();
    $user = $this->user->userProfile($openid);
    $user['tagId'] = $this->officialAccountService->getTagId($openid);
    return $this->respondWithData($user);
  }

}
