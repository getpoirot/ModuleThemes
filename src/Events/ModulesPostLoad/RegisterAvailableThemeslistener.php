<?php
namespace Module\Themes\Events\ModulesPostLoad;

use Poirot\Application\aSapi;
use Poirot\Application\Sapi\ModuleManager;

use Module\Themes\Sapi\Feature\iFeatureModuleEnableThemes;
use Module\Themes\Services;
use Module\Themes\ThemesManager;


class RegisterAvailableThemeslistener
{
    /** @var ModuleManager */
    protected $moduleManager;
    protected $_themeManager;


    /**
     * Set Themes Provided By Modules Into Theme Manager
     *
     * @param ModuleManager $module_manager
     *
     * @return mixed
     * @throws \Exception
     */
    function __invoke($module_manager = null)
    {
        $this->moduleManager = $module_manager;
        foreach($module_manager->listLoadedModules() as $moduleName)
        {
            $module = $module_manager->byModule($moduleName);
            if (! $module instanceof iFeatureModuleEnableThemes )
                ## Nothing to do!!
                continue;


            $themes = $module->registerAvailableThemes();
            $this->_themeManager()->setThemesByPath($themes);
        }
    }


    // ..

    /**
     * Retrieve Theme Manager
     *
     * @return ThemesManager
     *
     * @throws \Exception
     */
    protected function _themeManager()
    {
        if ($this->_themeManager)
            return $this->_themeManager;


        if (! $this->moduleManager )
            throw new \Exception('ModuleManager Not Defined.');

        /** @var aSapi $application */
        $application   = $this->moduleManager->getTarget();
        if (! $application instanceof aSapi )
            throw new \Exception('Target inside module manager is unknown!');


        $serviceContainer = $application->services();
        $themeManager = $serviceContainer->from('/module/themes/services')
            ->get(Services::ThemesManager);

        return $this->_themeManager = $themeManager;
    }
}
