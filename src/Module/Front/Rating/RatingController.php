<?php

declare(strict_types=1);

namespace Lyrasoft\Feedback\Module\Front\Rating;

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

#[Controller]
class RatingController
{
    use AjaxControllerTrait;

    #[Ajax]
    #[Method('POST')]
    public function add(
        AppContext $app,
        UserService $userService,
        FeedbackPackage $feedback,
        RatingService $ratingService
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

        $user = $userService->getUser();

        $item = $ratingService->addRatingIfNotRated(
            $type,
            $targetId,
            $user,
            extra: function (Rating $item) use ($rank) {
                $item->setRank((float) $rank);
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
}
