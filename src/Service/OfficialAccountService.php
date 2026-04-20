<?php

namespace WanPHP\Plugins\WeiXin\Service;

use Exception;
use WanPHP\Core\Database\EntityMetadata;
use WanPHP\Core\Database\EntityManager;
use WanPHP\Core\Factory\EntityMetadataFactory;
use WanPHP\Core\Repositories\Repository;
use WanPHP\Core\Repositories\WeiXin\WeChatBase;
use WanPHP\Core\Service\Service;
use WanPHP\Plugins\WeiXin\Entities\OfficialAccountEntity;

class OfficialAccountService extends Service
{
  public function __construct(EntityManager $em, private readonly WeChatBase $wechatBase)
  {
    parent::__construct($em);
  }

  protected function repo(): Repository
  {
    return $this->em->getRepository(OfficialAccountEntity::class);
  }

  protected function meta(): EntityMetadata
  {
    return EntityMetadataFactory::from(OfficialAccountEntity::class);
  }

  /**
   * @throws Exception
   */
  public function getTagId(string $openid): array
  {
    return $this->repo()->get('tagid_list[JSON]', ['openid' => $openid]);
  }

  /**
   * @throws Exception
   */
  public function isSubscribe(string $openid): bool
  {
    return $this->repo()->get('subscribe', ['openid' => $openid]);
  }

  /**
   * @throws Exception
   */
  public function membersTagging(string $openid, int $tagId): array
  {
    if ($this->isSubscribe($openid)) {
      $result = $this->wechatBase->membersTagging($tagId, [$openid]);
      if ($result['errcode'] == 0) {
        $tag_id_list = $this->repo()->get('tag_id_list[JSON]', ['openid' => $openid]);
        $tag_id_list[] = $tagId;
        $this->repo()->update(['tag_id_list' => array_unique($tag_id_list)], ['openid' => $openid]);
      }
      return $result;
    } else {
      return ['errcode' => 1, 'errmsg' => '用户未关注'];
    }
  }

  /**
   * @throws Exception
   */
  public function membersUnTagging(string $openid, int $tagId): array
  {
    if ($this->isSubscribe($openid)) {
      $result = $this->wechatBase->membersUnTagging($tagId, [$openid]);
      if ($result['errcode'] == 0) {
        $tag_id_list = $this->repo()->get('tag_id_list[JSON]', ['openid' => $openid]);
        $tag_id_list = array_values(array_diff($tag_id_list, [$tagId]));
        $this->repo()->update(['tag_id_list' => $tag_id_list], ['openid' => $openid]);
      }
      return $result;
    } else {
      return ['errcode' => 1, 'errmsg' => '用户未关注'];
    }
  }
}