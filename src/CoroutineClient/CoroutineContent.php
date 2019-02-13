<?php
/**
 * 保存上下文
 * Created by PhpStorm.
 * User: htpc
 * Date: 2018/10/10
 * Time: 15:30
 */

namespace AtServer\CoroutineClient;


use Swoole\Coroutine;

class CoroutineContent
{
	protected static $pool = [];

	static function get($key)
	{
		$cid = Coroutine::getuid();
		if ($cid < 0)
		{
			return null;
		}
		if(isset(self::$pool[$cid][$key])){
			return self::$pool[$cid][$key];
		}
		return null;
	}

	static function put($key, $item)
	{
		$cid = Coroutine::getuid();
		if ($cid > 0)
		{
			self::$pool[$cid][$key] = $item;
		}

	}

	static function delete($key = null)
	{
		$cid = Coroutine::getuid();
		if ($cid > 0)
		{
			if($key){
				unset(self::$pool[$cid][$key]);
			}else{
				unset(self::$pool[$cid]);
			}
		}
	}


}
