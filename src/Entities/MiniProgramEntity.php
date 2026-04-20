<?php
/**
 * Created by PhpStorm.
 * User: 火子 QQ：284503866.
 * Date: 2020/12/16
 * Time: 10:40
 */

namespace WanPHP\Plugins\WeiXin\Entities;


use Doctrine\DBAL\Types\Types;
use OpenApi\Attributes as OA;
use WanPHP\Core\Attribute\Column;
use WanPHP\Core\Attribute\DataTable;
use WanPHP\Core\Traits\EntityArrayTrait;

#[DataTable(name: 'wx_official_account', required: ["openid", "unionid"])]
#[OA\Schema(title: "小程序关联", description: "用户小程序关联信息", required: ["openid", "unionid"])]
class MiniProgramEntity
{
  use EntityArrayTrait;

  #[Column(type: Types::STRING, length: 28, primary: true)]
  #[OA\Property(description: "公众号用户openid", type: "string")]
  private string $openid;
  #[Column(type: Types::STRING, length: 29, nullable: true, unique: true)]
  #[OA\Property(description: "微信开放平台unionid", type: "string")]
  private string $unionid;

  /**
   * @return string
   */
  public function getOpenid(): string
  {
    return $this->openid;
  }

  /**
   * @param string $openid
   * @return MiniProgramEntity
   */
  public function setOpenid(string $openid): self
  {
    $this->openid = $openid;
    return $this;
  }

  /**
   * @return string
   */
  public function getUnionid(): string
  {
    return $this->unionid;
  }

  /**
   * @param string $unionid
   * @return MiniProgramEntity
   */
  public function setUnionid(string $unionid): self
  {
    $this->unionid = $unionid;
    return $this;
  }
}
