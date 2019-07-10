<?php
namespace Module\Themes\ThemeManager\ResolveStrategy;

use function Poirot\config;


class MergedConfigThemeResolver
    extends aThemeResolverStrategy
{
    /**
     * Resolve To Theme Name based on strategy found in class
     *
     * @return string|false
     */
    function getResolvedThemeName()
	{
	    return $this->getFromConfig();
	}


	// ..

    protected function getFromConfig()
    {
        $conf = config(\Module\Themes\Module::class, MergedConfigThemeResolver::class);
        return $conf['default_theme'] ?? false;
    }
}
