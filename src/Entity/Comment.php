<?php

declare(strict_types=1);

namespace Lyrasoft\Feedback\Entity;

use Lyrasoft\Feedback\Service\CommentService;
use Lyrasoft\Luna\Attributes\Author;
use Lyrasoft\Luna\Attributes\Modifier;
use Unicorn\Enum\BasicState;
use Windwalker\Core\DateTime\Chronos;
use Windwalker\Core\DateTime\ServerTimeCast;
use Windwalker\ORM\Attributes\AutoIncrement;
use Windwalker\ORM\Attributes\Cast;
use Windwalker\ORM\Attributes\CastNullable;
use Windwalker\ORM\Attributes\Column;
use Windwalker\ORM\Attributes\CreatedTime;
use Windwalker\ORM\Attributes\CurrentTime;
use Windwalker\ORM\Attributes\EntitySetup;
use Windwalker\ORM\Attributes\PK;
use Windwalker\ORM\Attributes\Table;
use Windwalker\ORM\Cast\JsonCast;
use Windwalker\ORM\EntityInterface;
use Windwalker\ORM\EntityTrait;
use Windwalker\ORM\Event\AfterDeleteEvent;
use Windwalker\ORM\Event\EnergizeEvent;
use Windwalker\ORM\Metadata\EntityMetadata;

use function Windwalker\unwrap_enum;

// phpcs:disable
// todo: remove this when phpcs supports 8.4
#[Table('comments', 'comment')]
#[\AllowDynamicProperties]
class Comment implements EntityInterface
{
    use EntityTrait;

    #[Column('id'), PK, AutoIncrement]
    public ?int $id = null;

    #[Column('parent_id')]
    public int|string $parentId = 0;

    #[Column('target_id')]
    public int|string $targetId = 0;

    #[Column('user_id')]
    public int $userId = 0;

    #[Column('type')]
    public string $type = '' {
        set(string|\BackedEnum $value) => unwrap_enum($value);
    }

    #[Column('title')]
    public string $title = '';

    #[Column('content')]
    public string $content = '';

    #[Column('avatar')]
    public string $avatar = '';

    #[Column('nickname')]
    public string $nickname = '';

    #[Column('email')]
    public string $email = '';

    #[Column('reply')]
    public string $reply = '';

    #[Column('reply_user_id')]
    public int $replyUserId = 0;

    #[Column('last_reply_at')]
    #[CastNullable(ServerTimeCast::class)]
    public ?Chronos $lastReplyAt = null {
        set(\DateTimeInterface|string|null $value) => $this->lastReplyAt = Chronos::tryWrap($value);
    }

    #[Column('last_reply_id')]
    public int $lastReplyId = 0;

    #[Column('rating')]
    public float $rating = 0.0;

    #[Column('state')]
    #[Cast('int')]
    #[Cast(BasicState::class)]
    public BasicState $state {
        set(BasicState|int $value) => $this->state = BasicState::wrap($value);
    }

    #[Column('ordering')]
    public int $ordering = 0;

    #[Column('created')]
    #[CastNullable(ServerTimeCast::class)]
    #[CreatedTime]
    public ?Chronos $created = null {
        set(\DateTimeInterface|string|null $value) => $this->created = Chronos::tryWrap($value);
    }

    #[Column('modified')]
    #[CastNullable(ServerTimeCast::class)]
    #[CurrentTime]
    public ?Chronos $modified = null {
        set(\DateTimeInterface|string|null $value) => $this->modified = Chronos::tryWrap($value);
    }

    #[Column('created_by')]
    #[Author]
    public int $createdBy = 0;

    #[Column('modified_by')]
    #[Modifier]
    public int $modifiedBy = 0;

    #[Column('params')]
    #[Cast(JsonCast::class)]
    public array $params = [];

    #[EntitySetup]
    public static function setup(EntityMetadata $metadata): void
    {
        //
    }

    #[EnergizeEvent]
    public static function energize(EnergizeEvent $event): void
    {
        $event->storeCallback(
            'comment.service',
            fn(CommentService $commentService) => $commentService
        );
    }

    #[AfterDeleteEvent]
    public static function afterDelete(AfterDeleteEvent $event): void
    {
        $orm = $event->orm;
        /** @var static $item */
        $item = $event->entity;

        $orm->deleteBatch(static::class, ['parent_id' => $item->id]);
        $orm->deleteBatch(Rating::class, ['type' => 'comment', 'target_id' => $item->id]);
    }

    public function count(): int
    {
        /** @var CommentService $commentService */
        $commentService = $this->retrieveMeta('comment.service')();

        return $commentService->countWith($this);
    }
}
