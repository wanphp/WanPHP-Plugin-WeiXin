<?php

namespace WanPHP\Plugins\WeiXin\Entities;


use Doctrine\DBAL\Types\Types;
use OpenApi\Attributes as OA;
use WanPHP\Core\Attribute\Column;
use WanPHP\Core\Attribute\DataTable;
use WanPHP\Core\Traits\EntityArrayTrait;

#[DataTable(name: 'wx_auto_reply', required: ["key", "msgType", "msgContent[JSON]"])]
#[OA\Schema(title: "自动回复", description: "公众号自动回复", required: ["key", "msgType", "msgContent"])]
class AutoReplyEntity
{
  use EntityArrayTrait;

  #[Column(type: Types::SMALLINT, autoIncrement: true, primary: true)]
  #[OA\Property(description: "ID")]
  private ?int $id;
  #[Column(type: Types::STRING, length: 50, unique: true)]
  #[OA\Property(description: "关键词")]
  private string $key;

  #[Column(type: Types::STRING, length: 10)]
  #[OA\Property(description: "接收信息类型")]
  private string $msgType;
  #[Column(type: Types::STRING, length: 10)]
  #[OA\Property(description: "回复类型")]
  private string $replyType;
  #[Column(type: Types::JSON)]
  #[OA\Property(description: "回复内容", type: "array", items: new OA\Items())]
  private array $msgContent;

  /**
   * @return array
   */
  public function getMsgContent(): array
  {
    return $this->msgContent;
  }

  /**
   * @param array $msgContent
   * @return AutoReplyEntity
   */
  public function setMsgContent(array $msgContent): self
  {
    $this->msgContent = $msgContent;
    return $this;
  }

  /**
   * @return string
   */
  public function getReplyType(): string
  {
    return $this->replyType;
  }

  /**
   * @param string $replyType
   * @return AutoReplyEntity
   */
  public function setReplyType(string $replyType): self
  {
    $this->replyType = $replyType;
    return $this;
  }

  /**
   * @return string
   */
  public function getMsgType(): string
  {
    return $this->msgType;
  }

  /**
   * @param string $msgType
   * @return AutoReplyEntity
   */
  public function setMsgType(string $msgType): self
  {
    $this->msgType = $msgType;
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
   * @return AutoReplyEntity
   */
  public function setKey(string $key): self
  {
    $this->key = $key;
    return $this;
  }

  /**
   * @return int|null
   */
  public function getId(): ?int
  {
    return $this->id;
  }

  /**
   * @param int|null $id
   * @return AutoReplyEntity
   */
  public function setId(?int $id): self
  {
    $this->id = $id;
    return $this;
  }


}
