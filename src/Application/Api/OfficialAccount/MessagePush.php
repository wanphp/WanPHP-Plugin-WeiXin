<?php
/**
 * Created by PhpStorm.
 * User: 火子 QQ：284503866.
 * Date: 2020/12/29
 * Time: 14:08
 */

namespace WanPHP\Plugins\WeiXin\Application\Api\OfficialAccount;


use Defuse\Crypto\Crypto;
use Defuse\Crypto\Exception\BadFormatException;
use Defuse\Crypto\Exception\EnvironmentIsBrokenException;
use Defuse\Crypto\Key;
use Exception;
use OpenApi\Attributes as OA;
use Psr\SimpleCache\CacheInterface;
use WanPHP\Core\Action;
use WanPHP\Core\Attribute\Route;
use WanPHP\Core\Repositories\WeiXin\WeChatBase;
use Psr\Http\Message\ResponseInterface as Response;
use WanPHP\Core\Service\UserService;
use WanPHP\Plugins\WeiXin\Service\AutoReplyService;
use WanPHP\Plugins\WeiXin\Service\OfficialAccountService;

abstract class MessagePush extends Action
{
  protected Key $encryptionKey;

  /**
   * @throws EnvironmentIsBrokenException
   * @throws BadFormatException
   */
  public function __construct(
    protected readonly WeChatBase             $weChatBase,
    protected readonly UserService            $user,
    protected readonly OfficialAccountService $officialAccountService,
    private readonly CacheInterface           $cache,
    protected readonly AutoReplyService       $autoReply)
  {
    $this->encryptionKey = Key::loadFromAsciiSafeString(getenv('APP_ENCRYPTION_KEY'));
  }

  #[Route(path: '/wx/message/push', methods: ['GET', 'POST'], description: '微信服务地址', name: 'wx.message.push')]
  #[OA\Post(
    path: "/wx/message/push",
    summary: "接收微信推送消息（事件/文本/图片等）",
    security: [],
    requestBody: new OA\RequestBody(
      description: "微信推送的 XML 数据",
      required: true,
      content: new OA\MediaType(
        mediaType: "text/xml",
        schema: new OA\Schema(type: "object")
      )
    ),
    tags: ["officialAccount"],
    responses: [
      new OA\Response(
        response: 200,
        description: "成功接收，返回 success 或回复 XML",
        content: new OA\MediaType(mediaType: "text/xml")
      )
    ]
  )]
  protected function action(): Response
  {
    if ($this->weChatBase->valid($this->request->getQueryParams()) === true) {
      $openid = $this->weChatBase->getRev()->getRevFrom();//获取每个微信用户的openid
      $time = $this->weChatBase->getRev()->getRevCtime();//获取消息发送时间
      $type = $this->weChatBase->getRev()->getRevType();//获取消息类型

      $body = '';
      switch ($type) {
        case 'event':
          // 处理事件推送
          $eventArr = $this->weChatBase->getRev()->getRevEvent();
          $event = $eventArr['event'] ?? '';//获得事件类型
          switch ($event) {
            case 'subscribe':
              $this->updateUser();
              //关注自动回复文本信息
              $body = $this->subscribe();
              break;
            case 'unsubscribe':
              $this->officialAccountService->update($openid, ['subscribe' => 0, 'unsubscribe_time' => $time, 'last_visit_time' => 0]);
              break;
            case 'SCAN':
              // 扫码
              if ($eventArr['key']) {
                $body = $this->userScan($eventArr['key'], $openid);
              }
              break;
            default:
              if ($event == 'CLICK' && !$this->weChatBase->webAuthorization && $eventArr['key'] == '授权') {
                $body = $this->getAuthorizationLink();
              } else {
                $body = $this->clickevent();
              }
          }
          break;
        case 'text':
          $this->lastVisitTime($openid);
          if (!$this->weChatBase->webAuthorization && $this->weChatBase->getRev()->getRevContent() == '授权') {
            $body = $this->getAuthorizationLink();
          } else {
            // 处理关键词回复
            $body = $this->text();
          }
          break;
        case 'image':
          $this->lastVisitTime($openid);
          // 接收图片
          $body = $this->image();
          break;
        case 'voice':
          $this->lastVisitTime($openid);
          // 接收语音
          $body = $this->voice();
          break;
        case 'video':
          $this->lastVisitTime($openid);
          // 接收视频
          $body = $this->video();
          break;
        case 'shortvideo':
          $this->lastVisitTime($openid);
          // 接收短视频
          $body = $this->shortvideo();
          break;
        default:
          $body = $this->weChatBase->Message('text', ['Content' => '收到']);
      }
      $this->response->getBody()->write($body);
      return $this->response->withHeader('Content-Type', 'text/xml')->withStatus(200);
    } else {
      $this->response->getBody()->write($this->weChatBase->valid($this->request->getQueryParams()));
      return $this->response->withHeader('Content-Type', 'text/plain')->withStatus(200);
    }
  }

  /**
   * @return string
   * @throws Exception
   */
  protected function updateUser(): string
  {
    $openid = $this->weChatBase->getRev()->getRevFrom();//获取每个微信用户的openid
    $time = time();
    if ((int)$this->cache->get($openid . '_last_visit_time') > ($time - 172800)) return $openid; // 两天内已更新过用户信息
    $info = $this->officialAccountService->get(['openid' => $openid]);

    //保存用户信息
    try {
      $userinfo = $this->weChatBase->getUserInfo($openid);
      //本地存储用户
      $data = [
        'subscribe' => 1,
        'tag_id_list' => $userinfo['tagid_list'],
        'subscribe_time' => $userinfo['subscribe_time'],
        'subscribe_scene' => $userinfo['subscribe_scene'],
        'last_visit_time' => $time
      ];
      if (isset($info['openid'])) {//二次关注
        $openid = $info['openid'];
        //更新公众号信息
        $data['unsubscribe_time'] = 0;
        $this->officialAccountService->updateEntityToArray($data,['openid'=>$openid]);
      } else {
        $data['openid'] = $openid;
        $this->officialAccountService->save($data);
        $user = ['openid' => $openid, 'share' => $userinfo['qr_scene'], 'createdAt' => $time];
        $this->user->save($user);
      }
    } catch (Exception) {
      if (isset($info['openid'])) {//二次关注
        $openid = $info['openid'];
        //更新公众号信息
        $this->officialAccountService->updateEntityToArray(['subscribe' => 1, 'unsubscribe_time' => 0],['openid'=>$openid]);
      } else {
        //本地存储用户
        $data = [
          'openid' => $openid,
          'subscribe' => 1,
          'subscribe_time' => $time,
          'last_visit_time' => $time
        ];
        $this->officialAccountService->save($data);
        $this->user->save(['openid' => $openid, 'createdAt' => $time]);
      }
    }
    $this->cache->set($openid . '_last_visit_time', $time, 172800);
    return $openid;
  }

  /**
   * 接收文本消息
   * @return string
   * @throws Exception
   */
  protected function text(): string
  {
    $text = $this->weChatBase->getRev()->getRevContent();//获取消息内容
    return $this->reply(trim($text));
  }

  /**
   * 接收图片
   * @return string
   * @throws Exception
   */
  protected function image(): string
  {
    return $this->reply('image');
  }

  /**
   * 接收语音
   * @return string
   * @throws Exception
   */
  protected function voice(): string
  {
    return $this->reply('voice');
  }

  /**
   * 接收视频
   * @return string
   * @throws Exception
   */
  protected function video(): string
  {
    return $this->reply('video');
  }

  /**
   * 接收短视频
   * @return string
   * @throws Exception
   */
  protected function shortVideo(): string
  {
    return $this->reply('shortvideo');
  }

  /**
   * 用户关注自动回复
   * @return string
   * @throws Exception
   */
  protected function subscribe(): string
  {
    return $this->reply('subscribe');
  }

  protected function reply(string $key): string
  {
    try {
      $msgData = $this->autoReply->getReplyData($key);
    } catch (Exception) {
      return '';
    }
    if ($msgData) {
      if ($msgData['replyType'] == 'music') {
        $msgData['msgContent']['Music']['MusicUrl'] = $this->httpHost() . $msgData['msgContent']['Music']['MusicUrl'];
        if (!isset($msgData['msgContent']['Music']['HQMusicUrl'])) {
          $msgData['msgContent']['Music']['HQMusicUrl'] = $msgData['msgContent']['Music']['MusicUrl'];
        }
      }
      if ($msgData['replyType'] == 'video' && isset($msgData['msgContent']['Video']['Cover'])) {
        unset($msgData['msgContent']['Video']['Cover']);
      }
      try {
        return $this->weChatBase->Message($msgData['replyType'], $msgData['msgContent']);
      } catch (Exception $e) {
        error_log($e->getMessage());
      }
    }
    return '';
  }

  /**
   * 用户点击自定义菜单
   * @return string
   * @throws Exception
   */
  protected function clickEvent(): string
  {
    $event = $this->weChatBase->getRevEvent();
    if (!$event) return '';
    $msgData = $this->autoReply->getReplyData($event['key']);
    if ($msgData) return $this->weChatBase->Message($msgData['replyType'], $msgData['msgContent']);
    return '';
  }

  /**
   * 记录用户最后发送信息的时间，用断断是否可发服消息
   * @param $openid
   * @return void
   * @throws Exception
   */
  protected function lastVisitTime($openid): void
  {
    $this->officialAccountService->update($openid, ['last_visit_time' => time()]);
  }

  /**
   * 扫码执行操作
   * @param string $scan_key
   * @param string $openid
   * @return string
   * @throws Exception
   */
  abstract function userScan(string $scan_key, string $openid): string;

  /**
   * 没有网页授权的公众号，通过自定义授权链接授权
   * @return array|string
   * @throws Exception
   */
  private function getAuthorizationLink(): string|array
  {
    $openid = $this->updateUser();
    if ($openid) {
      $code = Crypto::encrypt($openid, $this->encryptionKey);
      $body = $this->weChatBase->Message('text', ['Content' => '<a href="' . $this->httpHost() . '/auth/authorize?code=' . $code . '&state=code">点击确认授权</a>']);
    }
    return $body ?? '';
  }
}
