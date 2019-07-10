<?php
use Module\Themes\Services;
use Module\Themes\Services\AggregateResolverService;
use Module\Themes\ThemeManager\ApplyThemeOnDispatching;
use Module\Themes\ThemesManager;
use Module\Themes\ThemeManager\ResolveStrategy\aThemeResolverStrategy;

return [
    'implementations' => [
        Services::ThemesManager => ThemesManager::class,
        Services::ThemeResolver => aThemeResolverStrategy::class,
        Services::ApplyTheme    => ApplyThemeOnDispatching::class,
    ],
    'services' => [
        Services::ThemesManager => ThemesManager::class,
        Services::ThemeResolver => AggregateResolverService::class,
        Services::ApplyTheme    => ApplyThemeOnDispatching::class,
    ],
];
