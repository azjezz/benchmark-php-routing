<?php

namespace BenchmarkRouting\FastRoute;

use FastRoute\Cache\ApcuCache;
use FastRoute\DataGenerator;
use FastRoute\Dispatcher;
use PhpBench\Attributes as Bench;

#[Bench\Groups(['fast-route', 'cached'])]
final class FastRouteCompactMarkBasedApcuCached extends AbstractFastRoute
{
    protected string $dataGeneratorClass = DataGenerator\CompactMarkBased::class;
    protected string $dispatcherClass = Dispatcher\CompactMarkBased::class;

    public function __construct()
    {
        $this->cacheDriver = ApcuCache::class;
        $this->cacheKey = 'fast-route-compact-mark-based';

        $this->createDispatcher();
    }
}
