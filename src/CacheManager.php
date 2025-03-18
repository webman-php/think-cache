<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006~2023 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------
declare(strict_types = 1);

namespace Webman\ThinkCache;

use DateInterval;
use DateTimeInterface;
use Psr\SimpleCache\CacheInterface;
use Psr\SimpleCache\InvalidArgumentException;
use ReflectionException;
use think\helper\Arr;

/**
 * 缓存管理类
 * @mixin Driver
 */
class CacheManager extends Manager implements CacheInterface
{

    /**
     * @var string|null
     */
    protected ?string $namespace = '\\Webman\\ThinkCache\\driver\\';

    /**
     * 默认驱动
     * @return string|null
     */
    public function getDefaultDriver(): ?string
    {
        return $this->getConfig('default');
    }

    /**
     * 获取缓存配置
     * @access public
     * @param null|string $name    名称
     * @param mixed|null $default 默认值
     * @return mixed
     */
    public function getConfig(string $name = '', mixed $default = null): mixed
    {
        if ($name) {
            return config("think-cache.$name", $default);
        }

        return config('think-cache');
    }

    /**
     * 获取驱动配置
     * @param string $store
     * @param string|null $name
     * @param mixed|null $default
     * @return mixed
     */
    public function getStoreConfig(string $store, string $name = '', mixed $default = null): mixed
    {
        if ($config = $this->getConfig("stores.{$store}")) {
            return $name ? Arr::get($config, $name, $default) : $config;
        }

        throw new \InvalidArgumentException("Store [$store] not found.");
    }

    /**
     * @param string $name
     * @return mixed
     */
    protected function resolveType(string $name): mixed
    {
        return $this->getStoreConfig($name, 'type', 'file');
    }

    /**
     * @param string $name
     * @return mixed
     */
    protected function resolveConfig(string $name): mixed
    {
        return $this->getStoreConfig($name);
    }

    /**
     * 连接或者切换缓存
     * @access public
     * @param string|null $name 连接配置名
     * @return Driver
     * @throws ReflectionException
     */
    public function store(string $name = ''): Driver
    {
        return $this->driver($name);
    }

    /**
     * 清空缓冲池
     * @access public
     * @return bool
     * @throws ReflectionException
     */
    public function clear(): bool
    {
        return $this->store()->clear();
    }

    /**
     * 读取缓存
     * @access public
     * @param string $key 缓存变量名
     * @param mixed $default 默认值
     * @return mixed
     * @throws InvalidArgumentException|ReflectionException
     */
    public function get($key, mixed $default = null): mixed
    {
        return $this->store()->get($key, $default);
    }

    /**
     * 写入缓存
     * @access public
     * @param string $key 缓存变量名
     * @param mixed $value 存储数据
     * @param int|DateTimeInterface|DateInterval $ttl 有效时间 0为永久
     * @return bool
     * @throws InvalidArgumentException|ReflectionException
     */
    public function set($key, $value, $ttl = null): bool
    {
        return $this->store()->set($key, $value, $ttl);
    }

    /**
     * 删除缓存
     * @access public
     * @param string $key 缓存变量名
     * @return bool
     * @throws InvalidArgumentException|ReflectionException
     */
    public function delete($key): bool
    {
        return $this->store()->delete($key);
    }

    /**
     * 读取缓存
     * @access public
     * @param iterable $keys 缓存变量名
     * @param mixed $default 默认值
     * @return iterable
     * @throws ReflectionException
     */
    public function getMultiple($keys, $default = null): iterable
    {
        return $this->store()->getMultiple($keys, $default);
    }

    /**
     * 写入缓存
     * @access public
     * @param iterable $values 缓存数据
     * @param null|int|DateInterval $ttl 有效时间 0为永久
     * @return bool
     * @throws ReflectionException
     */
    public function setMultiple($values, $ttl = null): bool
    {
        return $this->store()->setMultiple($values, $ttl);
    }

    /**
     * 删除缓存
     * @access public
     * @param iterable $keys 缓存变量名
     * @return bool
     * @throws ReflectionException
     */
    public function deleteMultiple($keys): bool
    {
        return $this->store()->deleteMultiple($keys);
    }

    /**
     * 判断缓存是否存在
     * @access public
     * @param string $key 缓存变量名
     * @return bool
     * @throws InvalidArgumentException|ReflectionException
     */
    public function has($key): bool
    {
        return $this->store()->has($key);
    }

    /**
     * 缓存标签
     * @access public
     * @param array|string $name 标签名
     * @return TagSet
     * @throws ReflectionException
     */
    public function tag(array|string $name): TagSet
    {
        return $this->store()->tag($name);
    }

}
