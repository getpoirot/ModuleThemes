<?php
namespace Module\Themes
{
    use Poirot\Application\Interfaces\Sapi;
    use Poirot\Application\Interfaces\Sapi\iSapiModule;
    use Poirot\Application\ModuleManager\EventHeapOfModuleManager;
    use Poirot\Application\ModuleManager\Interfaces\iModuleManager;
    use Poirot\Application\Sapi\Event\EventHeapOfSapi;
    use Poirot\Application\Sapi\ModuleManager;
    use Poirot\Ioc\Container;
    use Poirot\Ioc\Container\BuildContainer;
    use Poirot\Loader\Autoloader\LoaderAutoloadAggregate;
    use Poirot\Loader\Autoloader\LoaderAutoloadNamespace;
    use Poirot\Std\Interfaces\Struct\iDataEntity;

    use Module\Themes\Events\ModulesPostLoad\RegisterAvailableThemeslistener;
    use Module\Themes\Sapi\Feature\iFeatureModuleEnableThemes;


    class Module implements iSapiModule
        , Sapi\Module\Feature\iFeatureModuleInitSapi
        , Sapi\Module\Feature\iFeatureModuleAutoload
        , Sapi\Module\Feature\iFeatureModuleInitModuleManager
        , Sapi\Module\Feature\iFeatureModuleMergeConfig
        , Sapi\Module\Feature\iFeatureModuleNestServices
        , Sapi\Module\Feature\iFeatureModuleInitSapiEvents
        , iFeatureModuleEnableThemes
    {
        /**
         * @inheritdoc
         */
        function initialize($sapi)
        {
            if ( \Poirot\isCommandLine( $sapi->getSapiName() ) )
                // Sapi Is Not HTTP. SKIP Module Load!!
                return false;
        }

        /**
         * @inheritdoc
         */
        function initAutoload(LoaderAutoloadAggregate $baseAutoloader)
        {
            /** @var LoaderAutoloadNamespace $nameSpaceLoader */
            $nameSpaceLoader = $baseAutoloader->loader(LoaderAutoloadNamespace::class);
            $nameSpaceLoader->addResource(__NAMESPACE__, __DIR__);
        }

        /**
         * @inheritdoc
         */
        function initModuleManager(iModuleManager $moduleManager)
        {
            if (! $moduleManager instanceof ModuleManager)
                throw new \Exception('Theme manager is not compatible current Module Manager.');


            ## Add Ability To Get Themes From Modules
            #
            $moduleManager->event()->on(
                EventHeapOfModuleManager::EVENT_MODULES_POSTLOAD
                , new RegisterAvailableThemeslistener
                , -2000
            );

            ## Load Required Modules
            #
            if (! $moduleManager->hasLoaded('HttpRenderer') )
                $moduleManager->loadModule('HttpRenderer');

            if (! $moduleManager->hasLoaded('AssetManager') )
                $moduleManager->loadModule('AssetManager');
        }

        /**
         * @inheritdoc
         */
        function initConfig(iDataEntity $config)
        {
            return \Poirot\Config\load(__DIR__ . '/../config/cor-themes');
        }

        /**
         * @inheritdoc
         */
        function getServices(Container $moduleContainer = null)
        {
            $conf    = include __DIR__ . '/../config/cor-themes.services.conf.php';
            $builder = new BuildContainer;
            $builder->with($builder::parseWith($conf));
            return $builder;
        }

        /**
         * @inheritdoc
         */
        function initSapiEvents(EventHeapOfSapi $events)
        {
            Services::ThemesManager()->attachToEvent($events);
        }


        // Implement iFeatureModuleEnableThemes:

        /**
         * Return Available Theme Directories
         *
         * @return array Path to theme directory
         */
        function registerAvailableThemes()
        {
            return [
                __DIR__.'/../theme'
            ];
        }
    }
}
