<?php

namespace WanPHP\Plugins\WeiXin\Service;

use Exception;
use WanPHP\Core\Database\EntityMetadata;
use WanPHP\Core\Factory\EntityMetadataFactory;
use WanPHP\Core\Repositories\Repository;
use WanPHP\Core\Service\Service;
use WanPHP\Plugins\WeiXin\Entities\CustomMenuEntity;

class CustomMenuService extends Service
{
  /**
   * @throws Exception
   */
  public function checkMenu(array $where): bool
  {
    return !empty($this->repo()->get('id', $where));
  }

  protected function repo(): Repository
  {
    return $this->em->getRepository(CustomMenuEntity::class);
  }

  protected function meta(): EntityMetadata
  {
    return EntityMetadataFactory::from(CustomMenuEntity::class);
  }
}