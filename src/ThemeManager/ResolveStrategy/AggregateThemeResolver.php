<?php
namespace Module\Themes\ThemeManager\ResolveStrategy;

use Countable;
use IteratorAggregate;

use Poirot\Std\Struct\PriorityObjectCollection;


class AggregateThemeResolver
    extends aThemeResolverStrategy
    implements Countable,
	IteratorAggregate
{
	/** @var PriorityObjectCollection */
	protected $queue;
	
	/** @var aThemeResolverStrategy */
	protected $lastStrategyFound;


    /**
	 * Resolve To Theme Name based on strategy found in class
	 * 
	 * @return string|false
	 */
    function getResolvedThemeName()
	{
        /** @var $detector aThemeResolverStrategy */
        foreach ($this->_getPriorityQueue() as $detector)
        {
			$name = $detector->getResolvedThemeName();
			if (empty($name) && $name !== '0')
				// No resource found; try next resolver
				continue;

			// Resource found; return it
			$this->lastStrategyFound = $detector;

			return $name;
		}

		return false;
	}

    /**
     * Attach a name resolver strategy
     *
     * @param aThemeResolverStrategy $resolver
     * @param int                    $priority
     *
     * @return AggregateThemeResolver
     */
    function attach(aThemeResolverStrategy $resolver, $priority = null)
    {
        $this->_getPriorityQueue()->insert($resolver, [], $priority);
        return $this;
    }

    /**
     * Detach Resolver From Aggregate
     *
     * @param aThemeResolverStrategy $detector
     *
     * @return $this
     */
    function detach(aThemeResolverStrategy $detector)
    {
        $this->_getPriorityQueue()->del($detector);
        return $this;
    }

    /**
     * Detach Whole Strategies
     *
     * @return $this
     */
    function detachAll()
    {
        foreach($this->_getPriorityQueue() as $detector)
            $this->detach($detector);

        return $this;
    }

    /**
     * Last Found Strategy
     *
     * @return aThemeResolverStrategy
     */
    function getLastStrategyFound()
    {
        return $this->lastStrategyFound;
    }


	// Implement Countable:

	/**
	 * Return count of attached resolvers
	 *
	 * @return int
	 */
	function count()
	{
		return $this->_getPriorityQueue()->count();
	}


	// Implement Aggregate:

	/**
	 * @inheritdoc
	 */
	function getIterator()
	{
		return $this->queue;
	}

	// ..

    /**
     * internal priority queue
     *
     * @return PriorityObjectCollection
     */
    protected function _getPriorityQueue()
    {
        if (!$this->queue)
            $this->queue = new PriorityObjectCollection;

        return $this->queue;
    }
}
