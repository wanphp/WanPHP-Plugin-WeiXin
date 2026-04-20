<?php

namespace WanPHP\Plugins\WeiXin\Application\Admin\AutoReply;

use Exception;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use WanPHP\Core\Action;
use WanPHP\Core\Attribute\Route;
use WanPHP\Core\Middleware\AdminPermissionMiddlewareInterface;
use WanPHP\Plugins\WeiXin\Service\AutoReplyService;
use WanPHP\Plugins\WeiXin\Service\CustomMenuService;

class AutoReply extends Action
{

  /**
   * @param AutoReplyService $autoReply
   * @param CustomMenuService $customMenu
   */
  public function __construct(private readonly AutoReplyService $autoReply, private readonly CustomMenuService $customMenu)
  {
  }

  #[Route(
    path: '/admin/wx/auto_reply',
    methods: ['GET'],
    description: '公众号自动回复',
    name: 'wx.auto_reply',
    isNav: true,
    middleware: [AdminPermissionMiddlewareInterface::class]
  )]
  protected function action(): Response
  {
    if ($this->isAjax()) {
      $params = $this->request->getQueryParams();
      $where = [];
      if (!empty($params['search']['value'])) {
        $keyword = trim($params['search']['value']);
        $keyword = addcslashes($keyword, '*%_');
        $where['key[~]'] = $keyword;
      }

      $recordsFiltered = $this->autoReply->count($where);
      $order = $this->getOrder();
      if ($order) $where['ORDER'] = $order;
      $limit = $this->getLimit();
      if ($limit) $where['LIMIT'] = $limit;

      $auto_reply = [];
      foreach ($this->autoReply->select('id,key,msgType,replyType,msgContent[JSON]', $where) as $item) {
        $data = $item['msgContent'];
        if (!empty($data['Content'])) $item['view'] = $data['Content'];
        if (!empty($data['Image'])) {
          $item['view'] = '<img class="object-fit-cover border rounded image" height="48" width="101"
          src="' . $this->urlFor('wx.material.image', ['media_id' => $data['Image']['MediaId']]) . '">';
        }
        if (!empty($data['Voice'])) {
          $item['view'] = '<audio 
          src="' . $this->urlFor('wx.material.voice', ['media_id' => $data['Voice']['MediaId']]) . '" 
          controls></audio>';
        }
        if (!empty($data['Video'])) {
          $item['view'] = '<div class="position-relative d-inline-block" role="button" style="width: 101px;"> 
  <img class="object-fit-cover border rounded ratio ratio-16x9" height="48" alt="" src="' . $data['Cover'] . '">
  <div class="position-absolute top-50 start-50 translate-middle text-danger text-danger video" 
  data-title="' . $data['Video']['Title'] . '" data-url="' . $this->urlFor('wx.material.video', ['media_id' => $data['Video']['MediaId']]) . '">
    <i class="fa-brands fa-2x fa-youtube"></i>
  </div>
</div>';
        }
        if (!empty($data['Music'])) {
          $cover = $this->urlFor('wx.material.image', ['media_id' => $data['Music']['ThumbMediaId']]);
          $item['view'] = '<div class="position-relative d-inline-block" role="button" style="width: 101px;"> 
  <img class="object-fit-cover border rounded ratio ratio-16x9" alt="" height="48" src="' . $cover . '">
  <div class="position-absolute top-50 start-50 translate-middle text-danger text-danger music" 
  data-name="' . $data['Music']['Title'] . '" data-url="' . $data['Music']['MusicUrl'] . '">
    <i class="fa-solid fa-2x fa-circle-play"></i>
  </div>
</div>';
        }
        if (!empty($data['Articles'])) {
          $item['view'] = '<div class="list-unstyled">';
          foreach ($data['Articles'] as $article) {
            $item['view'] .= '<div class="d-flex mb-2">
  <a class="d-flex" href="' . $article['Url'] . '" target="_blank">
      <img src="' . $article['PicUrl'] . '" class="rounded-1 shadow-sm object-fit-cover me-1" alt="" style="width: 48px;height: 48px">
  </a>
  <div class="justify-content-center flex-column d-flex px-0">
    <span class="fw-bold text-limit-1">' . $article['Title'] . '</span>
    <span class="text-body-tertiary text-limit-1">' . $article['Description'] . '</span>
  </div>
</div>';
          }
          $item['view'] .= '</div>';
        }
        $item['edit'] = [
          'path' => $this->urlFor('wx.auto_reply.edit', ['id' => $item['id']]),
          'modal' => ['name' => 'wx.auto_reply.modal', 'size' => 'lg'],
        ];
        $item['delete'] = [
          'path' => $this->urlFor('wx.auto_reply.delete', ['id' => $item['id']]),
          'message' => '是否确认要删除此回复？'
        ];
        $auto_reply[] = $item;
      }

      $data = [
        "draw" => $params['draw'] ?? '',
        "recordsTotal" => $this->autoReply->count(),
        "recordsFiltered" => $recordsFiltered,
        'data' => $auto_reply
      ];

      return $this->respondWithData($data);
    } else {
      $data = [
        'title' => '自动回复管理'
      ];

      return $this->respondView('@weixin/auto-reply/index.twig', $data);
    }
  }

  /**
   * 取菜单自定义菜单事件
   * @throws Exception
   */
  #[Route(
    path: '/admin/wx/customMenu/{event:click|view}',
    methods: ['GET'],
    description: '取菜单自定义菜单事件',
    name: 'wx.customMenu.event',
    middleware: [AdminPermissionMiddlewareInterface::class]
  )]
  public function getEvent(Request $request, Response $response, array $args): Response
  {
    $this->request = $request;
    $this->response = $response;
    $this->args = $args;

    $type = $this->resolveArg('event');
    if ($type == 'click') return $this->respondWithData($this->customMenu->select('name,key', ['type' => $type, 'key[!]' => '']));
    if ($type == 'view') return $this->respondWithData($this->customMenu->select('name,url(key)', ['type' => $type, 'url[!]' => '']));
    return $this->respondWithError('不支持事件');
  }
}
