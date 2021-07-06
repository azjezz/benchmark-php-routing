<?php

namespace BenchmarkRouting\FastRoute;

use FastRoute\Cache\FileCache;
use FastRoute\DataGenerator;
use FastRoute\Dispatcher;
use PhpBench\Attributes as Bench;

#[Bench\Groups(['fast-route', 'cached'])]
final class FastRouteCompactMarkBasedFilesCached extends AbstractFastRoute
{
    protected string $dataGeneratorClass = DataGenerator\CompactMarkBased::class;
    protected string $dispatcherClass = Dispatcher\CompactMarkBased::class;

    public function __construct()
    {
        $this->cacheDriver = FileCache::class;
        $this->cacheKey = __DIR__ . '/../../cache/fast-route-compact-mark-based.php';

        $this->createDispatcher();
    }
}
