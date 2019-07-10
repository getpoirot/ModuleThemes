<?php
use Module\Themes\Services\AggregateResolverService;
use Module\Themes\ThemeManager\ResolveStrategy\MergedConfigThemeResolver;

return [
    AggregateResolverService::class => [
        'resolvers' => [
            [
                // instance will resolved after module post load event when we initialize instance mered config
                'instance' => new \Poirot\Ioc\instance(MergedConfigThemeResolver::class),
                'priority' => -100,
            ]
        ],
    ],

    MergedConfigThemeResolver::class => [
        'default_theme' => 'default',
    ],
];
