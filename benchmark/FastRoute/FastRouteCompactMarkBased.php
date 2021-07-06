<?php

namespace BenchmarkRouting\FastRoute;

use FastRoute\DataGenerator;
use FastRoute\Dispatcher;
use PhpBench\Attributes as Bench;

#[Bench\Groups(['fast-route', 'raw'])]
final class FastRouteCompactMarkBased extends AbstractFastRoute
{
    protected string $dataGeneratorClass = DataGenerator\CompactMarkBased::class;
    protected string $dispatcherClass = Dispatcher\CompactMarkBased::class;
}
