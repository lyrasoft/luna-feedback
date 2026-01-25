<?php

declare(strict_types=1);

namespace Lyrasoft\Feedback\Entity;

use Lyrasoft\Feedback\Service\RatingService;
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
use Windwalker\ORM\Event\EnergizeEvent;
use Windwalker\ORM\Metadata\EntityMetadata;

// phpcs:disable
// todo: remove this when phpcs supports 8.4
#[Table('ratings', 'rating')]
#[\AllowDynamicProperties]
class Rating implements EntityInterface
{
    use EntityTrait;

    #[Column('id'), PK, AutoIncrement]
    public ?int $id = null;

    #[Column('target_id')]
    public int|string $targetId = 0;

    #[Column('user_id')]
    public int $userId = 0;

    #[Column('type')]
    public string $type = '';

    #[Column('rank')]
    public float $rank = 0.0;

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
            'rating.service',
            fn(RatingService $ratingService) => $ratingService
        );
    }

    public function count(): int
    {
        /** @var RatingService $ratingService */
        $ratingService = $this->retrieveMeta('rating.service')();

        return $ratingService->countWith($this);
    }
}
