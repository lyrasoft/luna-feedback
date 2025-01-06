<?php

declare(strict_types=1);

namespace Lyrasoft\Feedback\Component;

use Closure;
use Lyrasoft\Feedback\Service\RatingService;
use Windwalker\Core\Edge\Attribute\EdgeComponent;
use Windwalker\Edge\Component\AbstractComponent;
use Windwalker\Edge\Component\ComponentAttributes;
use Windwalker\Utilities\Attributes\Prop;

#[EdgeComponent('rating-button')]
class RatingButtonComponent extends AbstractComponent
{
    #[Prop]
    public mixed $rated;

    #[Prop]
    public string $tag = 'a';

    #[Prop]
    public mixed $id;

    #[Prop]
    public string|\BackedEnum $type;

    #[Prop]
    public string $classActive = '';

    #[Prop]
    public string $classInactive = '';

    #[Prop]
    public string $iconActive = 'fa fa-thumbs-up';

    #[Prop]
    public string $iconInactive = 'far fa-thumbs-up';

    #[Prop]
    public string $titleActive = '';

    #[Prop]
    public string $titleInactive = '';

    public function __construct(protected RatingService $ratingService)
    {
    }

    public function render(): Closure|string
    {
        return 'components.rating-button';
    }

    public function data(): array
    {
        $this->rated ??= $this->ratingService->isRated($this->type, $this->id);

        $data = parent::data();

        /** @var ComponentAttributes $attributes */
        $attributes = $data['attributes'];

        if ($this->tag === 'a') {
            $attributes['href'] = 'javascript:void(0)';
        } elseif ($this->tag === 'button') {
            $attributes['type'] = 'button';
        }

        $attributes['uni-rating-button'] = true;
        $attributes['data-rated'] = $this->rated ? '1' : '0';
        $attributes['data-id'] = $this->id;
        $attributes['data-type'] = $this->type;
        $attributes['data-class-active'] = $this->classActive;
        $attributes['data-class-inactive'] = $this->classInactive;
        $attributes['data-icon-active'] = $this->iconActive;
        $attributes['data-icon-inactive'] = $this->iconInactive;
        $attributes['data-title-active'] = $this->titleActive;
        $attributes['data-title-inactive'] = $this->titleInactive;

        return $data;
    }
}
