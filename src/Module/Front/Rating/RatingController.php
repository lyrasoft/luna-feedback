<?php

declare(strict_types=1);

namespace Lyrasoft\Feedback\Module\Front\Rating;

use Lyrasoft\Feedback\Entity\Comment;
use Lyrasoft\Feedback\Entity\Rating;
use Lyrasoft\Feedback\FeedbackPackage;
use Lyrasoft\Feedback\Service\RatingService;
use Lyrasoft\Luna\User\UserService;
use Unicorn\Attributes\Ajax;
use Unicorn\Controller\AjaxControllerTrait;
use Windwalker\Core\Application\AppContext;
use Windwalker\Core\Attributes\Controller;
use Windwalker\Core\Attributes\Method;
use Windwalker\Core\Security\Exception\UnauthorizedException;
use Windwalker\ORM\ORM;

#[Controller]
class RatingController
{
    use AjaxControllerTrait;

    protected const MIN_RANK = 0;

    protected const MAX_RANK = 5;

    #[Ajax]
    #[Method('POST')]
    public function add(
        AppContext $app,
        UserService $userService,
        FeedbackPackage $feedback,
        RatingService $ratingService,
        ORM $orm
    ): Rating {
        if (!$userService->isLogin()) {
            throw new UnauthorizedException('Please login', 401);
        }

        $targetId = $app->input('targetId');
        $type = $app->input('type');
        $rank = (int) $app->input('rank');

        $protect = (bool) $feedback->config('rating.ajax_type_protect');

        if ($protect) {
            $allowTypes = (array) ($feedback->config('rating.ajax_allow_types') ?? []);
            $allowTypes = array_map(\Windwalker\unwrap_enum(...), $allowTypes);

            if (!in_array($type, $allowTypes, true)) {
                throw new UnauthorizedException('Invalid type', 401);
            }
        }

        if ($rank < self::MIN_RANK || $rank > self::MAX_RANK) {
            throw new \InvalidArgumentException('Invalid rank', 400);
        }

        $this->assertTargetExists($orm, $type, $targetId);

        $user = $userService->getUser();

        $item = $ratingService->addRatingIfNotRated(
            $type,
            $targetId,
            $user,
            extra: function (Rating $item) use ($rank) {
                $item->rank = (float) $rank;
            }
        );

        return $item;
    }

    #[Ajax]
    #[Method('POST', 'DELETE')]
    public function remove(
        AppContext $app,
        UserService $userService,
        FeedbackPackage $feedback,
        RatingService $ratingService
    ): true {
        if (!$userService->isLogin()) {
            throw new UnauthorizedException('Please login', 401);
        }

        $targetId = $app->input('targetId');
        $type = $app->input('type');

        $protect = (bool) $feedback->config('rating.ajax_type_protect');

        if ($protect) {
            $allowTypes = (array) ($feedback->config('rating.ajax_allow_types') ?? []);
            $allowTypes = array_map(\Windwalker\unwrap_enum(...), $allowTypes);

            if (!in_array($type, $allowTypes, true)) {
                throw new UnauthorizedException('Invalid type', 401);
            }
        }

        $user = $userService->getUser();

        $ratingService->removeRating(
            $type,
            $targetId,
            $user,
        );

        return true;
    }

    protected function assertTargetExists(ORM $orm, mixed $type, mixed $targetId): void
    {
        $entityClass = match ($type) {
            'comment' => Comment::class,
            default => null,
        };

        if ($entityClass === null || !$orm->findOne($entityClass, $targetId)) {
            throw new \InvalidArgumentException('Invalid target', 400);
        }
    }
}
