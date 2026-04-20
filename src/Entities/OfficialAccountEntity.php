<?php
/**
 * 公众号
 * Created by PhpStorm.
 * User: 火子 QQ：284503866.
 * Date: 2020/12/16
 * Time: 10:19
 */

namespace WanPHP\Plugins\WeiXin\Entities;


use Doctrine\DBAL\Types\Types;
use OpenApi\Attributes as OA;
use WanPHP\Core\Attribute\Column;
use WanPHP\Core\Attribute\DataTable;
use WanPHP\Core\Traits\EntityArrayTrait;

#[DataTable(name: 'wx_official_account', required: ["openid"])]
#[OA\Schema(title: "公众号用户信息", description: "用户公众号关联信息", required: ["openid"])]
class OfficialAccountEntity
{
  use EntityArrayTrait;

  #[Column(type: Types::STRING, length: 28, primary: true)]
  #[OA\Property(description: "公众号用户openid", type: "string")]
  private string $openid;
  #[Column(type: Types::STRING, length: 29, nullable: true, unique: true)]
  #[OA\Property(description: "微信开放平台unionid", type: "string")]
  private string $unionid;
  #[Column(type: Types::JSON)]
  #[OA\Property(description: "粉丝标签", type: "array", items: new OA\Items(), default: [])]
  private array $tag_id_list;
  #[Column(type: Types::BOOLEAN)]
  #[OA\Property(description: "是否关注公众号", type: "bool")]
  private bool $subscribe;
  #[Column(type: Types::STRING, length: 10)]
  #[OA\Property(description: "关注公众号时间", type: "string")]
  private string $subscribe_time;
  #[Column(type: Types::STRING, length: 10)]
  #[OA\Property(description: "取消关注公众号时间", type: "string")]
  private string $unsubscribe_time;
  #[Column(type: Types::STRING, length: 30)]
  #[OA\Property(description: "用户关注公众号的渠道来源", type: "string")]
  private string $subscribe_scene;
  #[Column(type: Types::STRING, length: 10)]
  #[OA\Property(description: "最后来访时间，若在48小时内可以发客服信息", type: "string")]
  private string $last_visit_time;

  /**
   * @return string
   */
  public function getOpenid(): string
  {
    return $this->openid;
  }

  /**
   * @param string $openid
   * @return OfficialAccountEntity
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
   * @return OfficialAccountEntity
   */
  public function setUnionid(string $unionid): self
  {
    $this->unionid = $unionid;
    return $this;
  }

  /**
   * @return array
   */
  public function getTagIdList(): array
  {
    return $this->tag_id_list;
  }

  /**
   * @param array $tag_id_list
   * @return OfficialAccountEntity
   */
  public function setTagIdList(array $tag_id_list): self
  {
    $this->tag_id_list = $tag_id_list;
    return $this;
  }

  /**
   * @return bool
   */
  public function isSubscribe(): bool
  {
    return $this->subscribe;
  }

  /**
   * @param bool $subscribe
   * @return OfficialAccountEntity
   */
  public function setSubscribe(bool $subscribe): self
  {
    $this->subscribe = $subscribe;
    return $this;
  }

  /**
   * @return string
   */
  public function getSubscribeTime(): string
  {
    return $this->subscribe_time;
  }

  /**
   * @param string $subscribe_time
   * @return OfficialAccountEntity
   */
  public function setSubscribeTime(string $subscribe_time): self
  {
    $this->subscribe_time = $subscribe_time;
    return $this;
  }

  /**
   * @return string
   */
  public function getUnsubscribeTime(): string
  {
    return $this->unsubscribe_time;
  }

  /**
   * @param string $unsubscribe_time
   * @return OfficialAccountEntity
   */
  public function setUnsubscribeTime(string $unsubscribe_time): self
  {
    $this->unsubscribe_time = $unsubscribe_time;
    return $this;
  }

  /**
   * @return string
   */
  public function getSubscribeScene(): string
  {
    return $this->subscribe_scene;
  }

  /**
   * @param string $subscribe_scene
   * @return OfficialAccountEntity
   */
  public function setSubscribeScene(string $subscribe_scene): self
  {
    $this->subscribe_scene = $subscribe_scene;
    return $this;
  }

  /**
   * @return string
   */
  public function getLastVisitTime(): string
  {
    return $this->last_visit_time;
  }

  /**
   * @param string $last_visit_time
   * @return OfficialAccountEntity
   */
  public function setLastVisitTime(string $last_visit_time): self
  {
    $this->last_visit_time = $last_visit_time;
    return $this;
  }


}
