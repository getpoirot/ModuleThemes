<?php
namespace Module\Themes
{
    use Module\Themes\ThemeManager\ResolveStrategy\AggregateThemeResolver;


    /**
     * @method static ThemesManager ThemesManager()
     * @method static AggregateThemeResolver ThemesResolver()
     */
    class Services extends \IOC
    {
        const ThemesManager = 'ThemesManager';
        const ThemeResolver = 'ThemeResolver';
        const ApplyTheme = 'ApplyTheme';
    }
}
