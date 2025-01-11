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

namespace support\think;

use think\Facade;
use Webman\ThinkCache\CacheManager;

/**
 * @see CacheManager
 * @mixin CacheManager
 */
class Cache extends Facade
{
    /**
     * 获取当前Facade对应类名（或者已经绑定的容器对象标识）.
     *
     * @return string
     */
    protected static function getFacadeClass()
    {
        return CacheManager::class;
    }
}
