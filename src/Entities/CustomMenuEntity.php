<?php
/**
 * Created by PhpStorm.
 * User: 火子 QQ：284503866.
 * Date: 2021/3/9
 * Time: 9:03
 */

namespace WanPHP\Plugins\WeiXin\Entities;


use Doctrine\DBAL\Types\Types;
use OpenApi\Attributes as OA;
use WanPHP\Core\Attribute\Column;
use WanPHP\Core\Attribute\DataTable;
use WanPHP\Core\Traits\EntityArrayTrait;

#[DataTable(name: 'wx_custom_menu', required: ["name", "type"])]
#[OA\Schema(schema: "newCustomMenu", title: "自定义菜单", description: "公众号自定义菜单", required: ["name", "type"])]
class CustomMenuEntity
{
  use EntityArrayTrait;

  #[Column(type: Types::SMALLINT, autoIncrement: true, primary: true)]
  #[OA\Property(description: "菜单ID")]
  private ?int $id;
  #[Column(type: Types::SMALLINT, default: '0', index: true)]
  #[OA\Property(description: "微信标签")]
  private int $tag_id;
  #[Column(type: Types::SMALLINT, default: '0', index: true)]
  #[OA\Property(description: "上级菜单ID")]
  private int $parent_id;
  #[Column(type: Types::STRING, length: 20)]
  #[OA\Property(description: "菜单名")]
  private string $name;
  #[Column(type: Types::STRING, length: 20)]
  #[OA\Property(description: "事件类型")]
  private string $type;
  #[Column(type: Types::STRING, length: 50)]
  #[OA\Property(description: "click等点击类型必须；菜单KEY值，用于消息接口推送")]
  private string $key;
  #[Column(type: Types::STRING, length: 100)]
  #[OA\Property(description: "view、miniprogram类型必须，网页 链接，用户点击菜单可打开链接，不超过1024字节。 type为miniprogram时，不支持小程序的老版本客户端将打开本url。")]
  private string $url;
  #[Column(type: Types::STRING, length: 20)]
  #[OA\Property(description: "miniprogram类型必须；小程序的appid（仅认证公众号可配置）")]
  private string $appid;
  #[Column(type: Types::STRING, length: 50)]
  #[OA\Property(description: "miniprogram类型必须；小程序的页面路径")]
  private string $pagepath;
  #[Column(type: Types::SMALLINT, default: 0, index: true)]
  #[OA\Property(description: "排序", default: 0)]
  private int $sortOrder = 0;

  /**
   * @return int|null
   */
  public function getId(): ?int
  {
    return $this->id;
  }

  /**
   * @param int|null $id
   * @return CustomMenuEntity
   */
  public function setId(?int $id): self
  {
    $this->id = $id;
    return $this;
  }

  /**
   * @return int
   */
  public function getTagId(): int
  {
    return $this->tag_id;
  }

  /**
   * @param int $tag_id
   * @return CustomMenuEntity
   */
  public function setTagId(int $tag_id): self
  {
    $this->tag_id = $tag_id;
    return $this;
  }

  /**
   * @return int
   */
  public function getParentId(): int
  {
    return $this->parent_id;
  }

  /**
   * @param int $parent_id
   * @return CustomMenuEntity
   */
  public function setParentId(int $parent_id): self
  {
    $this->parent_id = $parent_id;
    return $this;
  }

  /**
   * @return string
   */
  public function getName(): string
  {
    return $this->name;
  }

  /**
   * @param string $name
   * @return CustomMenuEntity
   */
  public function setName(string $name): self
  {
    $this->name = $name;
    return $this;
  }

  /**
   * @return string
   */
  public function getType(): string
  {
    return $this->type;
  }

  /**
   * @param string $type
   * @return CustomMenuEntity
   */
  public function setType(string $type): self
  {
    $this->type = $type;
    return $this;
  }

  /**
   * @return string
   */
  public function getKey(): string
  {
    return $this->key;
  }

  /**
   * @param string $key
   * @return CustomMenuEntity
   */
  public function setKey(string $key): self
  {
    $this->key = $key;
    return $this;
  }

  /**
   * @return string
   */
  public function getUrl(): string
  {
    return $this->url;
  }

  /**
   * @param string $url
   * @return CustomMenuEntity
   */
  public function setUrl(string $url): self
  {
    $this->url = $url;
    return $this;
  }

  /**
   * @return string
   */
  public function getAppid(): string
  {
    return $this->appid;
  }

  /**
   * @param string $appid
   * @return CustomMenuEntity
   */
  public function setAppid(string $appid): self
  {
    $this->appid = $appid;
    return $this;
  }

  /**
   * @return string
   */
  public function getPagepath(): string
  {
    return $this->pagepath;
  }

  /**
   * @param string $pagepath
   * @return CustomMenuEntity
   */
  public function setPagepath(string $pagepath): self
  {
    $this->pagepath = $pagepath;
    return $this;
  }

  /**
   * @return int
   */
  public function getSortOrder(): int
  {
    return $this->sortOrder;
  }

  /**
   * @param int $sortOrder
   * @return CustomMenuEntity
   */
  public function setSortOrder(int $sortOrder): self
  {
    $this->sortOrder = $sortOrder;
    return $this;
  }


}