<?php
use Module\AssetManager\Resolvers\AggregateResolver;
use Module\AssetManager\Resolvers\DirectoryResolver;
use Module\AssetManager\Resolvers\GlobFileResolver;
use Module\AssetManager\Resolvers\PathPrefixResolver;

$globCssResolver = (new GlobFileResolver)
    ->setBaseDir(__DIR__)
    ->setGlobs([
        __DIR__ . '/css/*.css',
    ]);


// instead of setting base directory
$globJsResolver = (new PathPrefixResolver(
    (new GlobFileResolver)
        ->setGlobs([
            __DIR__ . '/js/*.js',
        ])
))->setPath('/js');


$dirResolver = (new DirectoryResolver())
    ->setDir(__DIR__)
    ->setExcludePaths(__DIR__.'/css', __DIR__.'/js');


$resolver = (new AggregateResolver)
    ->setResolvers($globCssResolver, $globJsResolver, $dirResolver);


return $resolver;
