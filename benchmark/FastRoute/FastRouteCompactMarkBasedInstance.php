<?php

namespace BenchmarkRouting\FastRoute;

use FastRoute\DataGenerator;
use FastRoute\Dispatcher;
use PhpBench\Attributes as Bench;

#[Bench\Groups(['fast-route', 'instance'])]
final class FastRouteCompactMarkBasedInstance extends AbstractFastRouteInstance
{
    protected string $dataGeneratorClass = DataGenerator\CompactMarkBased::class;
    protected string $dispatcherClass = Dispatcher\CompactMarkBased::class;
}
