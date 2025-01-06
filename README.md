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

There are 2 example seeders auto installed, add `comment-seeder.php` and `rating-seeder.php` to `resources/seeders/main.php`

```php
return [
    // ...

    __DIR__ . '/comment-seeder.php',
    __DIR__ . '/rating-seeder.php',

    // ...
];
```

If you don't need example seeders, write your own seeder by services:

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
```

## Comments

Add a comment to a type:

```php
/** @var \Lyrasoft\Feedback\Service\CommentService $commentService */
$commentService->addComment(
    'flower', // Type
    $targetId, // Target ID
    'Comment Text...', // Content
    $user->getId(), // User ID
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
    $user->getId(), // User ID
    
    // The extra can be callback or array
    extra: function (Comment $comment) {
        $comment->setRating(5); // If user mark as 5 star
        $comment->setNickname('Another nickname');
    }
);
```

Comments ordering:

```php
/** @var \Lyrasoft\Feedback\Service\CommentService $commentService */
$commentService->addComment(
    'flower', // Type
    $targetId, // Target ID
    'Comment Text...', // Content
    $user->getId(), // User ID or User entity
    extra: function (Comment $comment) {
        // This optional if you want to set ordering to one comment
        $comment->setOrdering($comment->count() + 1);
    }
);

// Or reorder all comments of one target item.
$commentService->reorderComments(
    'flower', // Type
    $targetId, // Target ID
);
```

### Comment Reply

There are 2 ways to add reply, one is just write reply content to comment, every comment contains only 1 reply:

```php
/** @var \Lyrasoft\Feedback\Service\CommentService $commentService */

$commentService->addInstantReply(
    $comment, // Can be ID or entity
    'Reply text...',
    $user->getId(), // User ID or User entity
);
```

The other way is to create sub comments:

```php
/** @var \Lyrasoft\Feedback\Service\CommentService $commentService */

$childComment = $commentService->addSubReply(
    $parentComment, // Can be ID or entity
    'Reply text...',
    $user->getId(), // User ID or User entity
    extra: function (Comment $comment) {
        // Configure comment entity before save
    }
);

// Optional: if you want to reorder it.
$commentService->reorderComments(
    $parentComment->getType(), // Type
    $parentComment->getTargetId(), // Target ID
    $parentComment->getId(), // Parent ID
);
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

Add a rating to a type:

```php
/** @var \Lyrasoft\Feedback\Service\RatingService $ratingService */
$ratingService->addRating(
    'flower', // Type
    $targetId, // Target ID
    $user->getId(), // User ID
);
```

Add rating if not rated, and configure Comment entity:

```php
use Lyrasoft\Feedback\Entity\Rating;

/** @var \Lyrasoft\Feedback\Service\RatingService $ratingService */
$ratingService->addRatingIfNotRated(
    'flower', // Type
    $targetId, // Target ID
    $user->getId(), // User ID
    
    // The extra can be callback or array
    extra: function (Rating $rating) {
        $rating->setRank(4.5); // If user mark as 4.5 star
    }
);
```

Rating ordering:

```php
/** @var \Lyrasoft\Feedback\Service\RatingService $ratingService */
$ratingService->addRating(
    'flower', // Type
    $targetId, // Target ID
    $user->getId(), // User ID or User entity
    extra: function (Rating $rating) {
        // This optional if you want to set ordering to one comment
        $rating->setOrdering($rating->count() + 1);
    }
);

// Or reorder all comments of one target item.
$ratingService->reorderRatings(
    'flower', // Type
    $targetId, // Target ID
);
```

### Other Methods

```php
/** @var \Lyrasoft\Feedback\Service\RatingService $ratingService */

// Calc average rank
$avg = $ratingService->calcAvgRank($type, $targetId);

// Get rating item or check is rated
$item = $ratingService->getRating($type, $targetId);
$bool = $ratingService->isRated($type, $targetId);
```

## Rating AJAX Button

You can add button component in blade templates:

```bladehtml
<div class="card c-item-card">
    <x-rating-button
        type="item"
        :id="$item->getId()"
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

You can configre allowed types in config file:

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
document.addEventListener('rated', (e) => {
  if (e.detail.type === 'comment') {
    if (e.detail.favorited) {
      u.notify('已按讚', 'success');
    } else {
      u.notify('已收回讚', 'success');
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
                $user->getId(),
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
        :id="$item->getId()"
        :rated="$item->rated"
        class="..."
    ></x-rating-button>
    ...
</div>
@endforeach
```
