# Lyrasoft Feedback Package

![](https://github.com/user-attachments/assets/f0058432-5dc6-448e-b5f2-2d8233119df2)

Lyrasoft Feedback package, contains comments and rating functions.

<!-- TOC -->
* [Lyrasoft Feedback Package](#lyrasoft-feedback-package)
  * [Installation](#installation)
    * [Language Files](#language-files)
    * [Seeders](#seeders)
  * [Register Admin Menu](#register-admin-menu)
  * [Comments](#comments)
    * [Comment Reply](#comment-reply)
    * [Other Methods](#other-methods)
  * [Rating](#rating)
    * [Other Methods](#other-methods-1)
  * [Rating AJAX Button](#rating-ajax-button)
    * [AJAX Type Protect](#ajax-type-protect)
    * [AJAX Events](#ajax-events)
    * [Add Button to Vue App](#add-button-to-vue-app)
  * [Use `RatingRepository`](#use-ratingrepository)
    * [Join to List](#join-to-list)
<!-- TOC -->

## Installation

Install from composer

```shell
composer require lyrasoft/feedback
```

Then copy files to project

```shell
php windwalker pkg:install lyrasoft/feedback -t routes -t migrations -t seeders
```

### Language Files

Add this line to admin & front middleware if you don't want to override languages:

```php
$this->lang->loadAllFromVendor('lyrasoft/feedback', 'ini');

// OR

$this->lang->loadAllFromVendor(\Lyrasoft\Feedback\FeedbackPackage::class, 'ini');
```

Or run this command to copy languages files:

```
php windwalker pkg:install lyrasoft/feedback -t lang
```

### Seeders

There are 2 example seeders auto installed, add `comment.seeder.php` and `rating.seeder.php` to
`resources/seeders/main.php`

```php
return [
    // ...

    __DIR__ . '/comment.seeder.php',
    __DIR__ . '/rating.seeder.php',

    // ...
];
```

If you don't need example seeders, write your own seeder by adding comments through service:

```php
foreach ($articleIds as $articleId) {
    foreach (range(1, random_int(2, 5)) as $i) {
        $userId = $faker->randomElement($userIds);

        $item = $commentService->addComment(
            $type,
            $articleId,
            $faker->paragraph(4),
            $userId,
            extra: function (Comment $item) use ($faker) {
                $item->setTitle($faker->sentence(2));
                $item->setCreated($faker->dateTimeThisYear());
                $item->setOrdering($item->count() + 1);
            }
        );
    }
}
```

## Register Admin Menu

Edit `resources/menu/admin/sidemenu.menu.php`

You must add `type` to route, every comment should contains type.

```php
// Comment: Article
$menu->link('評論管理: 文章')
    ->to($nav->to('comment_list')->var('type', 'article'))
    ->icon('fal fa-comments');

// Or use translations

$menu->link($lang('feedback.comment.list.title', title: $lang('luna.article.title')))
    ->to($nav->to('comment_list')->var('type', 'article'))
    ->icon('fal fa-comments');
```

## Comments

Add a comment to a type:

```php
/** @var \Lyrasoft\Feedback\Service\CommentService $commentService */
$commentService->addComment(
    'flower', // Type
    $targetId, // Target ID
    'Comment Text...', // Content
    $user->id, // User ID
);
```

Add a comment and configure Comment entity:

```php
use Lyrasoft\Feedback\Entity\Comment;

/** @var \Lyrasoft\Feedback\Service\CommentService $commentService */
$commentService->addComment(
    'flower', // Type
    $targetId, // Target ID
    'Comment Text...', // Content
    $user->id, // User ID
    
    // The extra can be callback or array
    extra: function (Comment $comment) {
        $comment->rating = 5; // If user mark as 5 star
        $comment->nickname = 'Another nickname';
    }
);
```

Comments ordering:

```php
/** @var \Lyrasoft\Feedback\Service\CommentService $commentService */
$newComment = $commentService->addComment(
    'flower', // Type
    $targetId, // Target ID
    'Comment Text...', // Content
    $user->id, // User ID or User entity
    extra: function (Comment $comment) use ($commentService) {
        $count = $commentService->countWith($comment);
        // OR
        $count = $comment->count();
    
        // This optional if you want to set ordering to one comment
        $comment->ordering = $count + 1;
    }
);

// Or reorder all comments of one target item.
$commentService->reorderComments(
    'flower', // Type
    $targetId, // Target ID
);

// OR

$commentService->reorderWith($newComment);
```

### Comment Reply

There are 2 ways to add reply, one is just write reply content to comment, every comment contains only 1 reply:

```php
/** @var \Lyrasoft\Feedback\Service\CommentService $commentService */

$commentService->addInstantReply(
    $comment, // Can be ID or entity
    'Reply text...',
    $user->id, // User ID or User entity
);
```

The other way is to create sub comments:

```php
/** @var \Lyrasoft\Feedback\Service\CommentService $commentService */

$childComment = $commentService->addSubReply(
    $parentComment, // Can be ID or entity
    'Reply text...',
    $user->id, // User ID or User entity
    extra: function (Comment $comment) {
        // Configure comment entity before save
    }
);

// Optional: if you want to reorder it.
$commentService->reorderComments(
    $parentComment->type, // Type
    $parentComment->targetId, // Target ID
    $parentComment->id, // Parent ID
);

// OR

$commentService->reorderWith($childComment);
```

### Other Methods

```php
/** @var \Lyrasoft\Feedback\Service\CommentService $commentService */

// Create Comment Item
$comment = $commentService->createCommentItem($type, $targetId, 'text...', $user);

// count Comments
$count = $commentService->countComments($type, $targetId);

// Reorder 
$commentService->reorderComments($type, $targetId);
```

-----

## Rating

You can use Rating for the following purposes:

1. **Like system**: Users can like content to show their supports. In this case, the Rating's `rank` field can always
   be set to `1` as one LIKE. Afterwards, simply use `counr()` to count the total likes.
2. **Star rating system**: Users can rate content with stars, e.g., from 1 to 5. In this case, the Rating's `rank` field 
   can be set to the numeric star chosen by the user, and you can use `calcAvgRank()` to calculate the average.

Add a rating to a type:

```php
/** @var \Lyrasoft\Feedback\Service\RatingService $ratingService */
$ratingService->addRating(
    'flower', // Type
    $targetId, // Target ID
    $user->id, // User ID
);
```

Add rating if not rated, and configure Rating entity:

```php
use Lyrasoft\Feedback\Entity\Rating;

/** @var \Lyrasoft\Feedback\Service\RatingService $ratingService */
$ratingService->addRatingIfNotRated(
    'flower', // Type
    $targetId, // Target ID
    $user->id, // User ID
    
    // The extra can be callback or array
    extra: function (Rating $rating) {
        $rating->rank = 4.5; // If user mark as 4.5 star
    }
);
```

Rating ordering:

```php
/** @var \Lyrasoft\Feedback\Service\RatingService $ratingService */
$newRating = $ratingService->addRating(
    'flower', // Type
    $targetId, // Target ID
    $user->id, // User ID or User entity
    extra: function (Rating $rating) use ($ratingService, $targetId) {
        $count = $ratingService->countWith($rating);
        // OR
        $count = $rating->count();
    
        // This optional if you want to set ordering to one comment
        $rating->ordering = $count + 1;
        
        // Update origin rated item
        $orm->updateBatch(
            Target::class,
            ['rating' => $ratingService->calcAvgWith($rating)],
            $targetId,
        );
    }
);

// Or reorder all ratings of one target item.
$ratingService->reorderRatings(
    'flower', // Type
    $targetId, // Target ID
);
// OR
$ratingService->reorderWith($newRating);
```

### Other Methods

```php
/** @var \Lyrasoft\Feedback\Service\RatingService $ratingService */

// Calc average rank
$avg = $ratingService->calcAvgRank($type, $targetId);
$avg = $ratingService->calcAvgWith($rating);

// Get rating item or check is rated
$item = $ratingService->getRating($type, $targetId);
$bool = $ratingService->isRated($type, $targetId);
```

## Rating AJAX Button

Add `useRatingButtons()` to front `main.ts` file:

```ts
import { useRatingButtons } from '@lyrasoft/feedback';

// ...

useRatingButtons();
```

Then uou can add button components everywhere in blade template:

```bladehtml

<div class="card c-item-card">
    <x-rating-button
        type="item"
        :id="$item->id"
        :rated="$item->rated"
        class="..."
    ></x-rating-button>

    <div class="card-body">
        ...
    </div>
</div>
```

Available params:

| Name             | Type           | Description                                                                        |
|------------------|----------------|------------------------------------------------------------------------------------|
| `type`           | string or enum | The rating type                                                                    |
| `id`             | string or int  | The item ID                                                                        |
| `rated`          | bool or int    | Is this item rated by current user, will auto load from DB if without this params. |
| `class-active`   | string         | The button class if active.                                                        |
| `class-inactive` | string         | The button class if inactive.                                                      |
| `icon-active`    | string         | The icon class if active.                                                          |
| `icon-inactive`  | string         | The button class if inactive.                                                      |
| `title-active`   | string         | The tooltip title if active.                                                       |
| `title-inactive` | string         | The tooltip title if inactive.                                                     |
| `tag`            | string         | The button HTML tag.                                                               |

### AJAX Type Protect

By default, favorite package will not allow any types sent from browser.

You can configure allowed types in config file:

```php
return [
    'feedback' => [
        // ...

        'rating' => [
            'ajax_type_protect' => true,
            'ajax_allow_types' => [
                'article',
                '...' // <-- Add your new types here
            ]
        ],
    ]
];
```

You can also set the `ajax_type_protect` to `FALSE` but we don't recommend to do this.

### AJAX Events

You can listen events after rated actions:

```ts
// Select all favorite buttons, you can use your own class to select it.
const buttons = document.querySelectorAll('[uni-rating-button]');

for (const button of buttons) {
    button.addEventListener('rated', (e) => {
        u.notify(e.detail.message, 'success');

        // Available details
        e.detail.rated;
        e.detail.type;
        e.detail.task;
        e.detail.message;
    });
}
```

Or listen globally:

```ts
import { simpleNotify } from '@windwalker-io/unicorn-next';

document.addEventListener('rated', (e) => {
    if (e.detail.type === 'comment') {
        if (e.detail.favorited) {
            simpleNotify('已按讚', 'success');
        } else {
            simpleNotify('已收回讚', 'success');
        }
    }
});
```

### Add Button to Vue App

Use `uni-rating-button` directive to auto enable button in Vue app.

```html
<a href="javascript://"
    uni-favorite-button
    :data-rated="rated"
    :data-type="type"
    data-class-active=""
    data-class-inactive=""
    data-icon-active="fas fa-heart"
    data-icon-inactive="far fa-heart"
    data-title-active="..."
    data-title-inactive="..."
>
    <i></i>
</a>
```

## Use `RatingRepository`

### Join to List

```php
use Lyrasoft\Feedback\Repository\RatingRepository;

    // In any repository

    public function getFrontListSelector(?User $user = null): ListSelector
    {
        $selector = $this->getListSelector();

        if ($user && $user->isLogin()) {
            RatingRepository::joinRating(
                $selector,
                'item',
                $user->id,
                'item.id'
            );
        }
        
        // ...
```

In blade:

```html
@foreach ($items of $item)
<div>
    ...
    <x-rating-button
        type="item"
        :id="$item->id"
        :rated="$item->rated"
        class="..."
    ></x-rating-button>
    ...
</div>
@endforeach
```
