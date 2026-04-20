<?php

namespace WanPHP\Plugins\WeiXin\Service;

use Exception;
use WanPHP\Core\Database\EntityMetadata;
use WanPHP\Core\Factory\EntityMetadataFactory;
use WanPHP\Core\Repositories\Repository;
use WanPHP\Core\Service\Service;
use WanPHP\Plugins\WeiXin\Entities\AutoReplyEntity;

class AutoReplyService extends Service
{

  protected function repo(): Repository
  {
    return $this->em->getRepository(AutoReplyEntity::class);
  }

  protected function meta(): EntityMetadata
  {
    return EntityMetadataFactory::from(AutoReplyEntity::class);
  }

  /**
   * @throws Exception
   */
  public function checkKey(string $key, int $id = 0): bool
  {
    $where = ['key' => $key];
    if ($id > 0) $where['id[!]'] = $id;
    return !empty($this->repo()->get('id', $where));
  }

  /**
   * @throws Exception
   */
  public function getReply(int $id): array
  {
    return $this->repo()->get('id,key,msgType,replyType,msgContent[JSON]', ['id' => $id]);
  }

  /**
   * @throws Exception
   */
  public function getReplyData(string $key): array
  {
    return $this->repo()->get('msgContent[JSON],replyType', ['key' => $key]);
  }
}