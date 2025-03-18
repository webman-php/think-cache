<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006~2023 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: yunwuxin <448901948@qq.com>
// +----------------------------------------------------------------------
declare(strict_types=1);

namespace Webman\ThinkCache;

use InvalidArgumentException;
use ReflectionException;
use think\helper\Str;
use Throwable;
use Webman\Context;
use Workerman\Coroutine\Pool;
use think\Container;

abstract class Manager
{

    /**
     * @var Pool[]
     */
    protected static array $pools = [];

    /**
     * 驱动
     * @var array
     */
    protected array $drivers = [];

    /**
     * 驱动的命名空间
     * @var ?string
     */
    protected ?string $namespace = null;

    /**
     * 获取驱动实例
     * @param null|string $name
     * @return mixed
     * @throws ReflectionException
     */
    protected function driver(string $name = ''): mixed
    {
        $name = $name ?: $this->getDefaultDriver();

        if (is_null($name)) {
            throw new InvalidArgumentException(sprintf(
                'Unable to resolve NULL driver for [%s].',
                static::class
            ));
        }

        return $this->getDriver($name);
    }

    /**
     * 获取驱动实例
     * @param string $name
     * @return mixed
     * @throws ReflectionException
     */
    protected function getDriver(string $name): mixed
    {
        return $this->createDriver($name);
    }

    /**
     * 获取驱动类型
     * @param string $name
     * @return mixed
     */
    protected function resolveType(string $name): mixed
    {
        return $name;
    }

    /**
     * 获取驱动配置
     * @param string $name
     * @return mixed
     */
    protected function resolveConfig(string $name): mixed
    {
        return $name;
    }

    /**
     * 获取驱动类
     * @param string $type
     * @return string
     */
    protected function resolveClass(string $type): string
    {
        if ($this->namespace || str_contains($type, '\\')) {
            $class = str_contains($type, '\\') ? $type : $this->namespace . Str::studly($type);

            if (class_exists($class)) {
                return $class;
            }
        }

        throw new InvalidArgumentException("Driver [$type] not supported.");
    }

    /**
     * 获取驱动参数
     * @param $name
     * @return array
     */
    protected function resolveParams($name): array
    {
        $config = $this->resolveConfig($name);
        return [$config];
    }

    /**
     * 创建驱动
     *
     * @param string $name
     * @return mixed
     *
     * @throws ReflectionException|Throwable
     */
    protected function createDriver(string $name): mixed
    {
        $type = $this->resolveType($name);

        $method = 'create' . Str::studly($type) . 'Driver';

        $params = $this->resolveParams($name);

        if (method_exists($this, $method)) {
            return $this->$method(...$params);
        }

        $class = $this->resolveClass($type);

        if (strtolower($type) === 'redis') {
            $key = "think-cache.stores.$name";
            $connection = Context::get($key);
            if (!$connection) {
                if (!isset(static::$pools[$name])) {
                    $poolConfig = $params[0]['pool'] ?? [];
                    $pool = new Pool($poolConfig['max_connections'] ?? 10, $poolConfig);
                    $pool->setConnectionCreator(function () use ($class, $params) {
                        return Container::getInstance()->invokeClass($class, $params);
                    });
                    $pool->setConnectionCloser(function ($connection) {
                        $connection->close();
                    });
                    $pool->setHeartbeatChecker(function ($connection) {
                        $connection->get('PING');
                    });
                    static::$pools[$name] = $pool;
                }
                try {
                    $connection = static::$pools[$name]->get();
                    Context::set($key, $connection);
                } finally {
                    Context::onDestroy(function () use ($connection, $name) {
                        try {
                            $connection && static::$pools[$name]->put($connection);
                        } catch (Throwable) {
                            // ignore
                        }
                    });
                }
            }
        } else {
            $connection = Container::getInstance()->invokeClass($class, $params);
        }

        return $connection;
    }

    /**
     * 移除一个驱动实例
     *
     * @param array|string|null $name
     * @return $this
     */
    public function forgetDriver(array|string|null $name = ''): static
    {
        $name = $name ?: $this->getDefaultDriver();

        foreach ((array) $name as $cacheName) {
            if (isset($this->drivers[$cacheName])) {
                unset($this->drivers[$cacheName]);
            }
        }

        return $this;
    }

    /**
     * 默认驱动
     * @return string|null
     */
    abstract public function getDefaultDriver(): ?string;

    /**
     * 动态调用
     * @param string $method
     * @param array $parameters
     * @return mixed
     * @throws ReflectionException
     */
    public function __call(string $method, array $parameters)
    {
        return $this->driver()->$method(...$parameters);
    }
}
