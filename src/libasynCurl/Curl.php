<?php

declare(strict_types=1);


namespace libasynCurl;


use Closure;
use InvalidArgumentException;
use libasynCurl\thread\CurlDeleteTask;
use libasynCurl\thread\CurlGetTask;
use libasynCurl\thread\CurlPostTask;
use libasynCurl\thread\CurlThreadPool;
use pocketmine\plugin\PluginBase;
use pocketmine\scheduler\ClosureTask;

class Curl
{
    /** @var bool */
    private static bool $registered = false;
    /** @var CurlThreadPool */
    private static CurlThreadPool $threadPool;
    private static array $defaultHeaders;

    public static function register(PluginBase $plugin, array $defaultHeaders = []): void
    {
        if (self::isRegistered()) {
            throw new InvalidArgumentException("{$plugin->getName()} attempted to register " . self::class . " twice.");
        }

        $server = $plugin->getServer();
        self::$threadPool = new CurlThreadPool(CurlThreadPool::POOL_SIZE, CurlThreadPool::MEMORY_LIMIT, $server->getLoader(), $server->getLogger(), $server->getTickSleeper());

        $plugin->getScheduler()->scheduleRepeatingTask(new ClosureTask(function (): void {
            self::$threadPool->collectTasks();
        }), CurlThreadPool::COLLECT_INTERVAL);
        $plugin->getScheduler()->scheduleRepeatingTask(new ClosureTask(function (): void {
            self::$threadPool->triggerGarbageCollector();
        }), CurlThreadPool::GARBAGE_COLLECT_INTERVAL);

        self::$registered = true;
        self::$defaultHeaders = $defaultHeaders;
    }

    public static function isRegistered(): bool
    {
        return self::$registered;
    }

    protected static function buildHeaders(array $headers): array
    {
        return array_merge($headers, self::$defaultHeaders);
    }

    public static function postRequest(string $page, array|string $args, Closure $closure = null, array $headers = [], int $timeout = 10): void
    {
        self::$threadPool->submitTask(new CurlPostTask($page, $args, $timeout, self::buildHeaders($headers), $closure));
    }

    public static function deleteRequest(string $page, array|string $args, Closure $closure = null, array $headers = [], int $timeout = 10): void
    {
        self::$threadPool->submitTask(new CurlDeleteTask($page, $args, $timeout, self::buildHeaders($headers), $closure));
    }

    public static function getRequest(string $page, array|string $args, Closure $closure = null, array $headers = [], int $timeout = 10): void
    {
        self::$threadPool->submitTask(new CurlGetTask($page, $timeout, self::buildHeaders($headers), $closure));
    }
}