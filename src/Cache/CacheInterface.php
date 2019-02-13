<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2015/9/18
 * Time: 15:57
 */

namespace AtServer\Cache;


interface CacheInterface
{
    function set($key,$value,$timeout=0);
    function get($key,$default=null);

    function del( $key );
}