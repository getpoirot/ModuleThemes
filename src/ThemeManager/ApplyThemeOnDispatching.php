<?php
namespace Module\Themes\ThemeManager;

use function Poirot\Std\flatten;
use Poirot\Config\Config;
use Poirot\Loader\LoaderNamespaceStack;

use Module\Foundation\View\ViewModelResolver;
use Module\AssetManager\Resolvers\AggregateResolver;
use Module\AssetManager\Interfaces\iAssetsResolver;
use Module\AssetManager\Resolvers\PathPrefixResolver;


class ApplyThemeOnDispatching
{
    /** @var ViewModelResolver */
    protected $viewModelResolver;
    /** @var AggregateResolver  */
    protected $assetResolver;
    /** @var Config */
    private $themeConfiguration;


    /**
     * Construct.
     *
     * @param ViewModelResolver $viewModelResolver @IoC /ViewModelResolver
     * @param AggregateResolver $assetResolver     @IoC /module/assetManager/services/AssetResolver
     */
    function __construct(ViewModelResolver $viewModelResolver, AggregateResolver $assetResolver)
    {
        $this->viewModelResolver = $viewModelResolver;
        $this->assetResolver     = $assetResolver;
    }


    /**
     * Theme Configuration
     *
     * @param Config $configuration
     *
     * @return ApplyThemeOnDispatching
     */
    function withThemeConfiguration($configuration)
    {
        $self = clone $this;
        $self->themeConfiguration = $configuration;
        return $self;
    }


    /**
     * Apply Theme Path To Works With ViewResolver
     *
     * @return $this
     * @throws \Exception
     */
    function applyViewModelResolver()
    {
        if (! $this->themeConfiguration )
            return $this;


        $themeDirectory = $this->themeConfiguration->get('DirPath');
        /** @var LoaderNamespaceStack $loader */
        $loader = $this->viewModelResolver->loader(LoaderNamespaceStack::class);
        $loader->prependResource('**', $themeDirectory);

        return $this;
    }

    /**
     * Apply Assets Provided By Theme
     *
     * @return $this
     */
    function applyAssets()
    {
        if (! $this->themeConfiguration )
            return $this;

        if (null === $assets = $this->themeConfiguration->get('Assets'))
            return $this;


        $resolver = $assets['resolver'];
        if (! $resolver instanceof iAssetsResolver )
            throw new \InvalidArgumentException(sprintf(
                'Theme Configuration On "Assets.resolver" should provide value instance of %s; given "%s".'
                , iAssetsResolver::class , flatten($resolver)
            ));

        if ( isset($assets['path']) ) {
            $path = '/' . ltrim($assets['path'], '/');
            $resolver = (new PathPrefixResolver($resolver))
                ->setPath($path);
        }


        $this->assetResolver->attach($resolver);
    }
}
