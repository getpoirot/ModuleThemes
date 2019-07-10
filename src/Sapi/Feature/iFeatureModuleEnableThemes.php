<?php
namespace Module\Themes\Sapi\Feature;

use Poirot\Application\Interfaces\Sapi\iFeatureSapiModule;


interface iFeatureModuleEnableThemes
    extends iFeatureSapiModule
{
    /**
     * Return Available Theme Directories
     *
     * @return array Path to theme directory
     */
    function registerAvailableThemes();
}
