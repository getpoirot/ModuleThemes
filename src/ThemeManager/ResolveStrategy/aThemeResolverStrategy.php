<?php
namespace Module\Themes\ThemeManager\ResolveStrategy;


abstract class aThemeResolverStrategy
{
    /**
     * Resolve To Theme Name based on strategy found in class
     *
     * @return string|false
     */
    abstract function getResolvedThemeName();
}
