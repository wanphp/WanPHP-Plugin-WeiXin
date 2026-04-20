<?php

namespace WanPHP\Plugins\WeiXin\Service;

use WanPHP\Core\Database\EntityMetadata;
use WanPHP\Core\Factory\EntityMetadataFactory;
use WanPHP\Core\Repositories\Repository;
use WanPHP\Core\Service\Service;
use WanPHP\Plugins\WeiXin\Entities\MiniProgramEntity;

class MiniProgramService extends Service
{
  protected function repo(): Repository
  {
    return $this->em->getRepository(MiniProgramEntity::class);
  }

  protected function meta(): EntityMetadata
  {
    return EntityMetadataFactory::from(MiniProgramEntity::class);
  }
}