<?php

declare(strict_types=1);

namespace Lyrasoft\Feedback\Service;

use Lyrasoft\Feedback\Entity\Comment;
use Lyrasoft\Luna\User\UserEntityInterface;
use Lyrasoft\Luna\User\UserService;
use Windwalker\DI\Attributes\Service;
use Windwalker\ORM\ORM;
use Windwalker\ORM\SelectorQuery;
use Windwalker\Query\Query;

#[Service]
class CommentService
{
    public function __construct(protected ORM $orm, protected UserService $userService)
    {
    }

    public function createCommentItem(
        string|\BackedEnum $type,
        mixed $targetId,
        string $content,
        mixed $user = null,
    ): Comment {
        $user = $this->toUser($user);

        $item = $this->orm->createEntity(Comment::class);

        $item->type = $type;
        $item->targetId = $targetId;
        $item->userId = $user->id;
        $item->nickname = $user->name;

        if (method_exists($user, 'getEmail')) {
            $item->email = $user->email;
        }

        if (method_exists($user, 'getAvatar')) {
            $item->avatar = $user->avatar;
        }

        $item->content = $content;
        $item->state = 1;

        return $item;
    }

    public function addComment(
        string|\BackedEnum $type,
        mixed $targetId,
        string $content,
        mixed $user = null,
        \Closure|array|null $extra = null,
    ): Comment {
        $item = $this->createCommentItem(
            $type,
            $targetId,
            $content,
            $user,
        );

        $item = $this->handleExtraData($extra, $item);

        $this->orm->createOne($item);

        return $item;
    }

    public function addInstantReply(
        mixed $comment,
        string $replyContent,
        mixed $user = null,
        \DateTimeInterface|string $time = 'now',
    ) {
        if (!$comment instanceof Comment) {
            $comment = $this->orm->mustFindOne(Comment::class, $comment);
        }

        $user = $this->toUser($user);

        $comment->replyUserId = $user->id;
        $comment->reply = $replyContent;
        $comment->lastReplyAt = $time;

        $this->orm->updateOne($comment);

        return $comment;
    }

    public function addSubReply(
        mixed $parent,
        string $replyContent,
        mixed $user = null,
        \Closure|array|null $extra = null,
    ): Comment {
        if (!$parent instanceof Comment) {
            $parent = $this->orm->mustFindOne(Comment::class, $parent);
        }

        $user = $this->toUser($user);

        $reply = $this->createCommentItem(
            $parent->type,
            $parent->targetId,
            $replyContent,
            $user,
        );
        $reply->parentId = $parent->id;

        $reply = $this->handleExtraData($extra, $reply);

        /** @var Comment $reply */
        $reply = $this->orm->createOne($reply);

        // Update parent
        $parent->replyUserId = $user->id;
        $parent->lastReplyAt = $reply->created;
        $parent->lastReplyId = $reply->id;

        $this->orm->updateOne($parent);

        return $reply;
    }

    public function isCommented(
        string|\BackedEnum $type,
        mixed $targetId,
        mixed $user = null,
        bool $lock = false
    ): bool {
        return (bool) $this->getComment($type, $targetId, $user, $lock);
    }

    public function getComment(
        string|\BackedEnum $type,
        mixed $targetId,
        mixed $user = null,
        bool $lock = false
    ): ?Comment {
        $userId = $this->toUserId($user);

        /** @var ?Comment $item */
        $item = $this->createCommentQuery($type, $targetId)
            ->where('user_id', $userId)
            ->tapIf(
                $lock,
                fn(Query $query) => $query->forUpdate()
            )
            ->get(Comment::class);

        return $item;
    }

    public function removeComment(
        string|\BackedEnum $type,
        mixed $targetId,
        mixed $user = null,
    ): void {
        $userId = $this->toUserId($user);

        $this->orm->deleteBatch(
            Comment::class,
            [
                'type' => $type,
                'target_id' => $targetId,
                'user_id' => $userId,
            ]
        );
    }

    protected function toUser(mixed $user): UserEntityInterface
    {
        if ($user instanceof UserEntityInterface) {
            return $user;
        }

        return $this->userService->getUser($user);
    }

    protected function handleExtraData(array|\Closure|null $extra, Comment $item): Comment
    {
        if ($extra instanceof \Closure) {
            $item = $extra($item) ?? $item;
        } elseif ($extra) {
            $item = $this->orm->hydrateEntity($extra, $item);
        }

        return $item;
    }

    public function countWith(Comment $comment, bool $lock = false): int
    {
        return $this->countComments(
            $comment->type,
            $comment->targetId,
            $comment->parentId,
            $lock,
        );
    }

    public function countComments(
        string|\BackedEnum $type,
        mixed $targetId,
        mixed $parentId = null,
        bool $lock = false
    ): int {
        return (int) $this->createCommentQuery($type, $targetId, $parentId)
            ->selectRaw('COUNT(*) AS count')
            ->tapIf(
                $lock,
                fn(Query $query) => $query->forUpdate()
            )
            ->result();
    }

    public function calcAvgRatingWith(
        Comment $rating
    ): float {
        return $this->calcAvgRating($rating->type, $rating->targetId);
    }

    public function calcAvgRating(
        string|\BackedEnum $type,
        mixed $targetId,
    ): float {
        return (float) $this->createCommentQuery($type, $targetId)
            ->selectRaw('IFNULL(AVG(%n), 0) AS %n', 'rating', 'rating')
            ->group('type', 'target_id')
            ->result();
    }

    public function reorderWith(Comment $comment, ?int $parentId = null): void
    {
        $this->reorderComments(
            $comment->type,
            $comment->targetId,
            $parentId ?? $comment->parentId,
        );
    }

    public function reorderComments(
        string|\BackedEnum $type,
        mixed $targetId,
        mixed $parentId = null,
    ): void {
        $comments = $this->createCommentQuery($type, $targetId, $parentId)
            ->order('ordering', 'ASC')
            ->all(Comment::class);

        $id = 0;
        $ordering = 0;

        $query = $this->orm->update(Comment::class)
            ->whereRaw('id = :id')
            ->setRaw('ordering = :ordering')
            ->bindParam('id', $id)
            ->bindParam('ordering', $ordering);

        /** @var Comment $comment */
        foreach ($comments as $i => $comment) {
            $id = $comment->id;
            $ordering = $i + 1;

            $query->execute();
        }
    }

    protected function createCommentQuery(
        \BackedEnum|string $type,
        mixed $targetId,
        mixed $parentId = null
    ): SelectorQuery {
        return $this->orm->from(Comment::class)
            ->where('type', $type)
            ->tapIf(
                $parentId !== null,
                fn(Query $query) => $query->where('parent_id', $parentId),
                fn(Query $query) => $query->whereRaw(
                    'IFNULL(parent_id, 0) IN (0, \'\')'
                )
            )
            ->where('target_id', $targetId);
    }

    protected function toUserId(mixed $user): mixed
    {
        $user ??= $this->userService->getCurrentUser();

        if ($user instanceof UserEntityInterface) {
            $userId = $user->id;
        } else {
            $userId = $user;
        }

        return $userId;
    }
}
