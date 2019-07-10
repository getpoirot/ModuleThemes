<?php
namespace Module\Themes;

use Poirot\Application\Sapi\Event\EventHeapOfSapi;
use Poirot\Config\Config;
use Poirot\Config\Reader\Json;
use Poirot\Config\Reader\Yml;
use Poirot\Config\ResourceFactory;
use Poirot\Events\Interfaces\iCorrelatedEvent;
use Poirot\Events\Interfaces\iEvent;
use Poirot\Std\ErrorStack;
use Poirot\Std\Type\StdString;
use Poirot\Std\Type\StdTravers;
use Poirot\View\DecorateViewModel;
use Poirot\View\Interfaces\iViewModel;
use Poirot\View\ViewModelTemplate;

use Module\Themes\ThemeManager\ResolveStrategy\aThemeResolverStrategy;
use Module\Themes\ThemeManager\ApplyThemeOnDispatching;
use Module\HttpRenderer\RenderStrategy\RenderDefaultStrategy;


class ThemesManager
    implements iCorrelatedEvent
{
    /** @var aThemeResolverStrategy */
    protected $themeResolver;
    /** @var ApplyThemeOnDispatching */
    protected $applyTheme;

    protected $activeTheme;
    protected $themes = [];
    protected $themesConfigurationNormalizedNames = [];



    /**
     * ThemesManager
     *
     * @param aThemeResolverStrategy  $themeResolverStrategy
     * @param ApplyThemeOnDispatching $applyTheme
     */
    function __construct(aThemeResolverStrategy $themeResolverStrategy, ApplyThemeOnDispatching $applyTheme)
    {
        $this->themeResolver = $themeResolverStrategy;
        $this->applyTheme    = $applyTheme;
    }


    /**
     * Attach To Event
     *
     * @param EventHeapOfSapi|iEvent $event
     *
     * @return $this
     * @throws \Exception
     */
    function attachToEvent(iEvent $event)
    {
        if (! $event instanceof EventHeapOfSapi)
            throw new \RuntimeException(sprintf(
                'Theme Manager Can`t Correlate With (%s) Event.'
                , get_class($event)
            ));

        $event
            ->on(EventHeapOfSapi::EVENT_APP_BOOTSTRAP
                , function ($e) use ($event) {
                    $this->setAndInitializeActiveTheme(
                        $this->themeResolver->getResolvedThemeName()
                    );
                }, 1000
            )
            ->on(EventHeapOfSapi::EVENT_APP_RENDER
                , function ($result = null) {
                    $this->_ensureThemeAvailability();
                }, RenderDefaultStrategy::PRIORITY_DECORATE_VIEWMODEL_LAYOUT + 1000 // before this event
            )
            ->on(EventHeapOfSapi::EVENT_APP_RENDER
                , function ($result = null) {
                    $this->_ensureLayoutName($result);
                }, RenderDefaultStrategy::PRIORITY_DECORATE_VIEWMODEL_LAYOUT * 2 // after this event
            )
            ->on(EventHeapOfSapi::EVENT_APP_ERROR
                , function ($result = null, $exception = null) {
                    $this->_ensureErrorViewNames($result, $exception);
                }, RenderDefaultStrategy::APP_ERROR_HANDLE_RENDERER_PRIORITY * 2 // after this event
            )
        ;
    }


    /**
     * Set and Initialize Theme
     *
     * @param string $name
     *
     * @throws \Exception
     */
    function setAndInitializeActiveTheme($name)
    {
        $normalized = $this->_normalize($name);
        if (! isset($this->themesConfigurationNormalizedNames[$normalized]) )
            throw new \Exception(sprintf('Template (%s) Not Found.', $name));


        $this->activeTheme = $themeConfiguration = $this->themesConfigurationNormalizedNames[$normalized];
        $this->_ensureThemeAvailability();
    }

    /**
     * Get latest Active Theme
     *
     * @return Config
     */
    function getActiveTheme()
    {
        return $this->activeTheme;
    }

    /**
     * Set Themes Directory Path
     *
     * @param array $dirPaths
     *
     * @return $this
     * @throws \Exception
     */
    function setThemesByPath(array $dirPaths)
    {
        foreach ($dirPaths as $path)
            $this->addThemeByPath($path);

        return $this;
    }

    /**
     * Add Theme Directory Path
     *
     * @param string $dirPath Path to theme directory
     *
     * @return $this
     * @throws \Exception
     */
    function addThemeByPath($dirPath)
    {
        $settings = $this->_importTheme($dirPath);

        if (null == $name = $settings->get('Name'))
            // use directory name instead
            $name = basename($dirPath);


        $normalizedName = $this->_normalize($name);
        $this->themesConfigurationNormalizedNames[$normalizedName] = $settings;
        $this->themes[$name] = $dirPath;

        return $this;
    }

    /**
     * List Name Of Available Themes
     *
     * @return string[]
     */
    function listAvailableThemes()
    {
       return array_keys($this->themes);
    }


    // ..

    /**
     * Ensure Theme Availability By:
     * - prepend theme directory to view resolver stack
     *
     * @throws \Exception
     */
    protected function _ensureThemeAvailability()
    {
        ## Apply System Wide And Active Theme
        #
        $this->applyTheme->withThemeConfiguration( $this->getActiveTheme() )
            ->applyViewModelResolver()
            ->applyAssets()
        ;
    }

    /**
     * Ensure layout Name For Theme From Settings
     *
     * @param mixed $result Result from dispatch action
     */
    protected function _ensureLayoutName($result)
    {
        if (! $result instanceof iViewModel )
            return;

        $viewModel = $result;
        $settings  = $this->getActiveTheme();
        if ( $layout = $settings->get('Layout') )
            $viewModel->setTemplate($layout);
    }

    /**
     * Ensure layout Name For Theme From Settings
     *
     * @param mixed      $result Result from dispatch action
     * @param \Exception $exception
     */
    protected function _ensureErrorViewNames($result, \Exception $exception)
    {
        if (! $result instanceof iViewModel )
            return;

        $viewModel = $result;
        $settings  = $this->getActiveTheme();

        /** @var Config $errorScripts */
        $errorScripts = $settings->get('Exceptions');

        // exception template
        $exClass = new \ReflectionClass($exception);
        do {
            $errorShortName = $exClass->getShortName();
            $errorLongName  = $exClass->getName();


            if ( $errorViewScriptName = $errorScripts->get($errorLongName) )
                 break;
            elseif ( $errorViewScriptName = $errorScripts->get($errorShortName) )
                break;

        } while (false !== $exClass = $exClass->getParentClass());


        $layoutTemplate = null;
        if (is_string($errorViewScriptName) )
            $pageTemplate = $errorViewScriptName;
        else {
            list($pageTemplate, $layoutTemplate) = StdTravers::of($errorViewScriptName)->toArray();
        }

        /** @var DecorateViewModel|ViewModelTemplate $pageView */
        if ($pageView = $viewModel->getBindByTag('view_page_content'))
            $pageView->setTemplate($pageTemplate);

        if ($layoutTemplate)
            $viewModel->setTemplate($layoutTemplate);
    }


    // ..

    /**
     * Parse and Import Theme Configuration File From Theme Path
     *
     * @param string $dirPath
     *
     * @return Config
     * @throws \Exception
     */
    protected function _importTheme(string $dirPath)
    {
        $dirPath = realpath($dirPath);

        if (file_exists($themeSetting = $dirPath.'/Theme.yml')) {
            $parser = Yml::class;
        } elseif (file_exists($themeSetting = $dirPath.'/Theme.json')) {
            $parser = Json::class;
        } else {
            throw new \Exception(sprintf(
                'Path to Theme at (%s) is not contains setting file.'
                , $dirPath
            ));
        }

        $resource = ResourceFactory::createFromUri($themeSetting);
        $parser   = new $parser($resource);

        $settings = new Config(
            $this->_processSettings($parser, $dirPath) );

        $settings->set('DirPath', $dirPath); // keep dir path of this theme
        $settings->setImmutable();
        return $settings;
    }

    /**
     * Process Settings
     * - import php files for @compiled array property key
     *
     * @param array  $parser
     * @param string $dirPath
     *
     * @return array
     */
    protected function _processSettings($parser, $dirPath)
    {
        $data = [];
        foreach ($parser as $key => $value)
        {
            $data[$key] = $value;

            if (is_array($value))
                $data[$key] = $this->_processSettings($value, $dirPath);

            elseif ( trim($key) == '@compiled' ) {
                try {
                    $value   = StdString::of($value)->stripPrefix('./');
                    $include = (! $value->isStartWith('/') )
                        // relative path
                        ? $dirPath . '/' . $value
                        // absolute path
                        : $value;

                    ErrorStack::handleError(E_ALL);
                    $leafEntity = include $include;
                    if ($error = ErrorStack::handleDone())
                        throw $error;

                    unset($data[$key]);
                    $data = $leafEntity;

                } catch (\Exception $e) {
                    throw new \RuntimeException(sprintf(
                        'Can`t include "%s".', $include
                    ), null, $e);
                }
            }
        }


        return $data;
    }

    /**
     * Normalize Name
     *
     * @param string $name
     *
     * @return string
     */
    private function _normalize($name)
    {
        return strtolower($name);
    }
}
