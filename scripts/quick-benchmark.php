<?php

namespace BenchmarkRouting;

/* Quick and dirty script to calculate routes matched per second */

use Psl\Shell;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Output\ConsoleOutput;
use Throwable;

require __DIR__ . '/../vendor/autoload.php';


final class QuickBenchmark
{
    public const BENCHMARKS = [
        'raw' => [
            // uncached, router instance is created in each iteration from scratch.
            'symfony' => Symfony\Symfony::class,
            'hack-routing' => HackRouting\HackRouting::class,
            'fast-route(mark)' => FastRoute\FastRouteMarkBased::class,
            'fast-route(compact-mark)' => FastRoute\FastRouteCompactMarkBased::class,
        ],

        'cached' => [
            // router instance is created in each iteration using cache.
            'symfony:cached(file)' => Symfony\SymfonyCached::class,
            'hack-routing:cached(file)' => HackRouting\HackRoutingFilesCached::class,
            'hack-routing:cached(apcu)' => HackRouting\HackRoutingApcuCached::class,
            'fast-route(mark):cached(file)' => FastRoute\FastRouteMarkBasedFilesCached::class,
            'fast-route(mark):cached(apcu)' => FastRoute\FastRouteMarkBasedApcuCached::class,
            'fast-route(compact-mark):cached(file)' => FastRoute\FastRouteCompactMarkBasedFilesCached::class,
            'fast-route(compact-mark):cached(apcu)' => FastRoute\FastRouteCompactMarkBasedApcuCached::class,
        ],

        'instance' => [
            // router instance is only created once in the constructor, and kept in the memory.
            'symfony:instance' => Symfony\SymfonyInstance::class,
            'hack-routing:instance' => HackRouting\HackRoutingInstance::class,
            'fast-route(mark):instance' => FastRoute\FastRouteMarkBasedInstance::class,
            'fast-route(compact-mark):instance' => FastRoute\FastRouteCompactMarkBasedInstance::class,
        ]
    ];

    public const REPEATS = 300;

    public const scenario = [
        'benchAll' => [182, 2], /* total number of routes */
        'benchLongest' => [1, self::REPEATS],
        'benchLast' => [1, self::REPEATS],
        'benchInvalidMethod' => [1, self::REPEATS],
        'benchInvalidRoute' => [1, self::REPEATS],
    ];

    public static function main($arguments): void
    {
        $group = $arguments['group'] ?? null;
        $case = $arguments['case'] ?? null;
        $scenario = $arguments['scenario'] ?? null;

        $benchmark = new self();

        (null === $case)
            ? $benchmark->all($group)
            : $benchmark->run($group, $case, $scenario);
    }

    public function all(?string $group = null): void
    {
        $output = new ConsoleOutput();
        $progressBar = new ProgressBar($output);

        $results = [];
        foreach (self::BENCHMARKS as $benchmark_group => $benchmark) {
            if (null !== $group && $benchmark_group !== $group) {
                continue;
            }

            foreach ($benchmark as $case => $class) {
                foreach (self::scenario as $scenario => $revs) {
                    $time = Shell\execute("php", ["-dopcache.jit=1235", "-dopcache.enable_cli=1", "-dopcache.enable=1", "-dapc.enable=1", "-dapc.enable_cli=1", __FILE__, '--case=' . $case, '--scenario=' . $scenario, '--group=' . $benchmark_group]);
                    $progressBar->advance();

                    $results[$benchmark_group][] = array(
                        'case' => $case,
                        'scenario' => $scenario,
                        'time' => $time,
                        'repeats' => $revs[0] * $revs[1],
                        'per_second' => $revs[0] * $revs[1] / $time,
                    );
                }
            }
        }

        $progressBar->finish();
        $output->writeln('');

        foreach ($results as $group => $result) {
            usort($result, static function ($a, $b) {
                return $b['per_second'] <=> $a['per_second'];
            });

            $table = new Table($output);
            $table->setHeaderTitle('Benchmark results for group ' . $group . '.');
            $table->setHeaders(['Case', 'Scenario', 'Routes', 'Time', 'Per Second']);

            foreach ($result as $data) {
                $table->addRow([
                    $data['case'],
                    $data['scenario'],
                    $data['repeats'],
                    sprintf('%0.6f seconds', $data['time']),
                    $data['per_second']
                ]);
            }
            $table->render();
        }
    }

    public function run(?string $group, ?string $case, ?string $scenario): void
    {
        $routers = [];
        foreach (self::BENCHMARKS as $benchmark_group => $benchmarks) {
            if ($group && $benchmark_group !== $group) {
                continue;
            }

            foreach ($benchmarks as $benchmark_case => $benchmark_scenario) {
                if ($case && $benchmark_case !== $case) {
                    continue;
                }

                foreach (self::scenario as $scenario_name => [$_, $repeats]) {
                    if ($scenario && $scenario_name !== $scenario) {
                        continue;
                    }

                    $class = $benchmarks[$case];
                    $bench = new $class();

                    for ($i = 0; $i < $repeats; $i++) {
                        $routers[] = fn() => $this->$scenario_name($bench);
                    }

                }
            }
        }

        $start = microtime(true);
        foreach ($routers as $router) {
            $router();
        }

        echo microtime(true) - $start;
    }

    public function benchAll(Benchmark $bench): void
    {
        $bench->benchAll();
    }

    public function benchLongest(Benchmark $bench): void
    {
        $bench->runRouting($bench->getLongestRoute()[0]['route']);
    }

    public function benchLast(Benchmark $bench): void
    {
        $bench->runRouting($bench->getLastRoute()[0]['route']);
    }

    public function benchInvalidMethod(Benchmark $bench): void
    {
        try {
            $bench->runRouting($bench->getLastRoute()[0]['route'], 'DELETE');
        } catch (Throwable $e) {
            // ignore
        }
    }

    public function benchInvalidRoute(Benchmark $bench): void
    {
        try {
            $bench->runRouting('/a-route-that-does-not-exist-anywhere-and-will/never-be-matched');
        } catch (Throwable $e) {
            // ignore
        }
    }
}

QuickBenchmark::main(getopt('', ['case::', 'scenario::', 'group::']));
