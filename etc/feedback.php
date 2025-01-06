<?php

declare(strict_types=1);

namespace App\Config;

use Lyrasoft\Feedback\FeedbackPackage;

return [
    'feedback' => [
        'providers' => [
            FeedbackPackage::class
        ]
    ]
];
