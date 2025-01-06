<?php

declare(strict_types=1);

namespace Lyrasoft\Feedback\Service;

use Lyrasoft\Feedback\Entity\Rating;
use Lyrasoft\Luna\User\UserEntityInterface;
use Lyrasoft\Luna\User\UserService;
use Windwalker\DI\Attributes\Service;
use Windwalker\ORM\ORM;
use Windwalker\ORM\SelectorQuery;
use Windwalker\Query\Query;

#[Service]
class RatingService
{
    public function __construct(protected ORM $orm, protected UserService $userService)
    {
    }

    public function createRatingItem(
        string|\BackedEnum $type,
        mixed $targetId,
        mixed $user = null,
    ): Rating {
        $item = $this->orm->createEntity(Rating::class);

        $userId = $this->toUserId($user);

        $item->setType($type);
        $item->setTargetId($targetId);
        $item->setUserId($userId);

        return $item;
    }

    public function addRatingIfNotRated(
        string|\BackedEnum $type,
        mixed $targetId,
        mixed $user = null,
        \Closure|array|null $extra = null,
    ): Rating {
        return $this->orm->transaction(
            function () use ($type, $targetId, $user, $extra) {
                $userId = $this->toUserId($user);

                if ($item = $this->getRating($type, $targetId, $userId, true)) {
                    return $item;
                }

                return $this->addRating($type, $targetId, $userId, $extra);
            }
        );
    }

    public function addRating(
        string|\BackedEnum $type,
        mixed $targetId,
        mixed $user = null,
        \Closure|array|null $extra = null,
    ): Rating {
        $item = $this->createRatingItem($type, $targetId, $user);

        $item = $this->handleExtraData($extra, $item);

        return $this->orm->createOne($item);
    }

    public function isRated(
        string|\BackedEnum $type,
        mixed $targetId,
        mixed $user = null,
        bool $lock = false
    ): bool {
        return (bool) $this->getRating($type, $targetId, $user, $lock);
    }

    public function getRating(
        string|\BackedEnum $type,
        mixed $targetId,
        mixed $user = null,
        bool $lock = false
    ): ?Rating {
        $userId = $this->toUserId($user);

        /** @var ?Rating $item */
        $item = $this->createRatingQuery($type, $targetId)
            ->where('user_id', $userId)
            ->tapIf(
                $lock,
                fn(Query $query) => $query->forUpdate()
            )
            ->get(Rating::class);

        return $item;
    }

    public function removeRating(
        string|\BackedEnum $type,
        mixed $targetId,
        mixed $user = null,
    ): void {
        $userId = $this->toUserId($user);

        $this->orm->deleteWhere(
            Rating::class,
            [
                'type' => $type,
                'target_id' => $targetId,
                'user_id' => $userId,
            ]
        );
    }

    public function countRatings(
        string|\BackedEnum $type,
        mixed $targetId,
    ): int {
        return (int) $this->createRatingQuery($type, $targetId)
            ->selectRaw('COUNT(*) AS count')
            ->result();
    }

    public function calcAvgRank(
        string|\BackedEnum $type,
        mixed $targetId,
    ): float {
        return (float) $this->createRatingQuery($type, $targetId)
            ->selectRaw('IFNULL(AVG(%n), 0) AS %n', 'rank', 'rank')
            ->group('type', 'target_id')
            ->result();
    }

    public function reorderRatings(
        string|\BackedEnum $type,
        mixed $targetId,
    ): void {
        $ratings = $this->createRatingQuery($type, $targetId)
            ->order('ordering', 'ASC')
            ->all(Rating::class);

        $id = 0;
        $ordering = 0;

        $query = $this->orm->update(Rating::class)
            ->whereRaw('id = :id')
            ->setRaw('ordering = :ordering')
            ->bindParam('id', $id)
            ->bindParam('ordering', $ordering);

        /** @var Rating $rating */
        foreach ($ratings as $i => $rating) {
            $id = $rating->getId();
            $ordering = $i + 1;

            $query->execute();
        }
    }

    public function createRatingQuery(
        string|\BackedEnum $type,
        mixed $targetId,
    ): SelectorQuery {
        return $this->orm->from(Rating::class)
            ->where('type', $type)
            ->where('target_id', $targetId);
    }

    protected function handleExtraData(array|\Closure|null $extra, Rating $item): Rating
    {
        if ($extra instanceof \Closure) {
            $item = $extra($item) ?? $item;
        } elseif ($extra) {
            $item = $this->orm->hydrateEntity($extra, $item);
        }

        return $item;
    }

    protected function toUserId(mixed $user): mixed
    {
        $user ??= $this->userService->getCurrentUser();

        if ($user instanceof UserEntityInterface) {
            $userId = $user->getId();
        } else {
            $userId = $user;
        }

        return $userId;
    }
}
