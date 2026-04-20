<?php

namespace WanPHP\Plugins\WeiXin\Application\Admin\Material;

use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Psr7\Utils;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use WanPHP\Core\Action;
use WanPHP\Core\Attribute\Route;
use WanPHP\Core\Middleware\AdminPermissionMiddlewareInterface;
use WanPHP\Core\Repositories\WeiXin\WeChatBase;

class Material extends Action
{
  /**
   * @param WeChatBase $weChatBase
   */
  public function __construct(private readonly WeChatBase $weChatBase)
  {
  }

  #[Route(
    path: '/admin/wx/material/list[/{type:image|video|voice}]',
    methods: ['GET'],
    description: '素材管理',
    name: 'wx.material',
    isNav: true,
    middleware: [AdminPermissionMiddlewareInterface::class]
  )]
  protected function action(): Response
  {
    if ($this->isAjax()) {
      $params = $this->request->getQueryParams();
      $type = $this->resolveArg('type', 'image');
      $material = $this->weChatBase->batchGetMaterial(["type" => $type, "offset" => $params['start'] ?? 0, "count" => $params['length'] ?? 10]);
      foreach ($material['item'] as &$item) {
        switch ($type) {
          case 'image':
            $item['type'] = 'image';
            $item['url'] = parse_url($item['url'], PHP_URL_PATH) . '?wx_fmt=jpeg';
            break;
          case 'video':
            $item['type'] = 'video';
            $item['cover_url'] = parse_url($item['cover_url'], PHP_URL_PATH) . '?wx_fmt=jpeg';
            $item['url'] = $this->urlFor('wx.material.video', ['media_id' => $item['media_id']]);
            break;
          case 'voice':
            $item['type'] = 'voice';
            $item['url'] = $this->urlFor('wx.material.voice', ['media_id' => $item['media_id']]);
            break;
        }
        $item['delete'] = $this->urlFor('wx.material.delete', ['media_id' => $item['media_id']]);
      }
      unset($item);
      $data = [
        "draw" => $params['draw'],
        "recordsTotal" => $material['total_count'] ?? 0,
        "recordsFiltered" => $material['total_count'] ?? 0,
        'data' => array_chunk($material['item'], 5),
      ];

      return $this->respondWithData($data);
    } else {
      $data = [
        'title' => '永久素材管理',
        'action'=> $this->request->getUri()->getPath()
      ];

      return $this->respondView('@weixin/material/index.twig', $data);
    }
  }

  /**
   * 音频
   * @throws Exception
   */
  #[Route(
    path: '/admin/wx/material/image/{media_id}',
    methods: ['GET'],
    description: '音频素材',
    name: 'wx.material.image',
    middleware: [AdminPermissionMiddlewareInterface::class])]
  #[Route(
    path: '/admin/wx/material/voice/{media_id}',
    methods: ['GET'],
    description: '音频素材',
    name: 'wx.material.voice',
    middleware: [AdminPermissionMiddlewareInterface::class])]
  public function voice(Request $request, Response $response, array $args): Response
  {
    $this->request = $request;
    $this->response = $response;
    $this->args = $args;

    $resp = $this->weChatBase->getMaterial($this->resolveArg('media_id'), false);
    return $this->response
      ->withHeader('Content-Type', $resp['content_type'])
      ->withHeader('Content-Disposition', $resp['content_disposition'])
      ->withBody(Utils::streamFor($resp['body']));
  }

  /**
   * 视频
   * @throws Exception
   */
  #[Route(
    path: '/admin/wx/material/video/{media_id}',
    methods: ['GET'],
    description: '图片素材',
    name: 'wx.material.video',
    middleware: [AdminPermissionMiddlewareInterface::class]
  )]
  public function video(Request $request, Response $response, array $args): Response
  {
    $this->request = $request;
    $this->response = $response;
    $this->args = $args;

    $resp = $this->weChatBase->getMaterial($this->resolveArg('media_id'), false);
    if ($resp['down_url']) {
      $client = new Client([
        'timeout' => 10,
        'verify' => false, // 微信证书偶发问题
        'headers' => [
          'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)',
          'Referer' => 'https://mp.weixin.qq.com/',
          'Accept' => 'text/html,application/xhtml+xml',
        ],
      ]);

      try {
        $resp = $client->get($resp['down_url']);
      } catch (GuzzleException $e) {
        return $this->respondWithError($e->getCode() . ':' . $e->getMessage());
      }

      $html = (string)$resp->getBody();

      // 直接锁定 “流畅”
      if (preg_match("/video_quality_wording\s*:\s*'流畅'[\\s\\S]*?url\s*:\s*'([^']+)'/u", $html, $m)) {
        $url = $m[1];
        // 把 \x26 变成 &
        $url = preg_replace('/\\\\x26/', '&', $url);

        // 把 &amp; 变成 &
        $url = html_entity_decode($url);
        return $this->respondWithData(['url' => $url]);
      }
    }
    return $this->respondWithData($resp);
  }
}
