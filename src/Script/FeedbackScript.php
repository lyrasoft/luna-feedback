<?php

declare(strict_types=1);

namespace Lyrasoft\Feedback\Script;

use Lyrasoft\Luna\User\UserService;
use Psr\Http\Message\UriInterface;
use Unicorn\Script\UnicornScript;
use Windwalker\Core\Asset\AbstractScript;
use Windwalker\Core\Router\Navigator;
use Windwalker\DI\Attributes\Service;

#[Service]
class FeedbackScript extends AbstractScript
{
    public function __construct(
        protected UnicornScript $unicornScript,
        protected Navigator $nav,
        protected UserService $userService,
    ) {
    }

    public function ratingButton(UriInterface|string|null $loginUri = null): void
    {
        if ($this->available()) {
            if ($this->userService->isLogin()) {
                $this->unicornScript->data(
                    'rating',
                    [
                        'isLogin' => true
                    ]
                );
            } else {
                $this->unicornScript->data(
                    'rating',
                    [
                        'isLogin' => false,
                        'loginUri' => (string) ($loginUri ?? $this->nav->to('front::login')
                            ->withReturn()
                            ->full())
                    ]
                );
            }

            $this->unicornScript->addRoute('@rating_ajax');

            $this->js('@feedback/rating-button.js');
        }
    }
}
