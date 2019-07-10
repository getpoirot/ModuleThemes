<?php
namespace Module\Themes\Services;

use Poirot\Ioc\Container\Service\aServiceContainer;

use Module\Themes\Module;
use Module\Themes\ThemeManager\ResolveStrategy\AggregateThemeResolver;


class AggregateResolverService
    extends aServiceContainer
{
    /**
     * @inheritdoc
     * @return AggregateThemeResolver
     * @throws \Exception
     */
    function newService()
    {
        $aggrThemeResolver = new AggregateThemeResolver;

        if ($conf = \Poirot\config(Module::class, AggregateResolverService::class)) {
            foreach ($conf['resolvers'] as $resolverConf) {
                $priority         = $resolverConf['priority'] ?? null;
                $resolverInstance = $resolverConf['instance'];


                $aggrThemeResolver->attach($resolverInstance, $priority);
            }
        }


        return $aggrThemeResolver;
    }
}
