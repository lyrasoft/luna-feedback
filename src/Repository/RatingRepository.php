<?php

declare(strict_types=1);

namespace Lyrasoft\Feedback\Repository;

use Lyrasoft\Feedback\Entity\Rating;
use Unicorn\Attributes\ConfigureAction;
use Unicorn\Attributes\Repository;
use Unicorn\Repository\Actions\BatchAction;
use Unicorn\Repository\Actions\DeleteAction;
use Unicorn\Repository\Actions\ReorderAction;
use Unicorn\Repository\Actions\SaveAction;
use Unicorn\Repository\ListRepositoryInterface;
use Unicorn\Repository\ListRepositoryTrait;
use Unicorn\Repository\ManageRepositoryInterface;
use Unicorn\Repository\ManageRepositoryTrait;
use Unicorn\Selector\ListSelector;
use Windwalker\ORM\SelectorQuery;
use Windwalker\Query\Query;

use function Windwalker\Query\val;

#[Repository(entityClass: Rating::class)]
class RatingRepository implements ManageRepositoryInterface, ListRepositoryInterface
{
    use ManageRepositoryTrait;
    use ListRepositoryTrait;

    public function getListSelector(): ListSelector
    {
        $selector = $this->createSelector();

        $selector->from(Rating::class);

        return $selector;
    }

    public static function joinRating(
        Query|ListSelector $query,
        string|\BackedEnum $type,
        mixed $userId,
        string $idField
    ): Query|ListSelector {
        $query->selectRaw('IF(rating.id IS NOT NULL, 1, 0) AS rated');
        $query->leftJoin(
            Rating::class,
            'rating',
            [
                ['rating.target_id', $idField],
                ['rating.user_id', val($userId)],
                ['rating.type', val($type)],
            ]
        );

        return $query;
    }

    #[ConfigureAction(SaveAction::class)]
    protected function configureSaveAction(SaveAction $action): void
    {
        //
    }

    #[ConfigureAction(ReorderAction::class)]
    protected function configureReorderAction(ReorderAction $action): void
    {
        //
    }

    #[ConfigureAction(BatchAction::class)]
    protected function configureBatchAction(BatchAction $action): void
    {
        //
    }

    #[ConfigureAction(DeleteAction::class)]
    protected function configureDeleteAction(DeleteAction $action): void
    {
        //
    }
}
