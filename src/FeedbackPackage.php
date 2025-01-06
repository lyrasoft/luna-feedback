<?php

declare(strict_types=1);

namespace Lyrasoft\Feedback;

use Lyrasoft\Feedback\Service\CommentService;
use Lyrasoft\Feedback\Service\RatingService;
use Windwalker\Core\Package\AbstractPackage;
use Windwalker\Core\Package\PackageInstaller;
use Windwalker\DI\Container;
use Windwalker\DI\ServiceProviderInterface;
use Windwalker\Utilities\StrNormalize;

class FeedbackPackage extends AbstractPackage implements ServiceProviderInterface
{
    public function register(Container $container): void
    {
        $container->share(static::class, $this);
        $container->prepareSharedObject(CommentService::class);
        $container->prepareSharedObject(RatingService::class);
    }

    public function install(PackageInstaller $installer): void
    {
        $installer->installConfig(static::path('etc/*.php'), 'config');
        $installer->installLanguages(static::path('resources/languages/**/*.ini'), 'lang');
        $installer->installMigrations(static::path('resources/migrations/**/*'), 'migrations');
        $installer->installSeeders(static::path('resources/seeders/**/*'), 'seeders');
        $installer->installRoutes(static::path('routes/**/*.php'), 'routes');
        $installer->installViews(static::path('views/**/*.blade.php'), 'views');

        $this->installModules($installer, 'comment', ['admin', 'model']);
    }

    protected function installModules(
        PackageInstaller $installer,
        string $name,
        array $modules = ['front', 'admin', 'model']
    ): void {
        $pascal = StrNormalize::toPascalCase($name);

        if (in_array('admin', $modules, true)) {
            $installer->installModules(
                [
                    static::path("src/Module/Admin/$pascal/**/*") => "@source/Module/Admin/$pascal",
                ],
                ['Lyrasoft\\Feedback\\Module\\Admin' => 'App\\Module\\Admin'],
                ['modules', $name . '_admin'],
            );
        }

        if (in_array('front', $modules, true)) {
            $installer->installModules(
                [
                    static::path("src/Module/Front/$pascal/**/*") => "@source/Module/Front/$pascal",
                ],
                ['Lyrasoft\\Feedback\\Module\\Front' => 'App\\Module\\Front'],
                ['modules', $name . '_front']
            );
        }

        if (in_array('model', $modules, true)) {
            $installer->installModules(
                [
                    static::path("src/Entity/$pascal.php") => '@source/Entity',
                    static::path("src/Repository/{$pascal}Repository.php") => '@source/Repository',
                ],
                [
                    'Lyrasoft\\Feedback\\Entity' => 'App\\Entity',
                    'Lyrasoft\\Feedback\\Repository' => 'App\\Repository',
                ],
                ['modules', $name . '_model']
            );
        }
    }
}
