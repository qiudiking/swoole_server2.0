<?php
/**
 * SWOOLETable内存表
 * 此类必须表swoole_server启动之前创建内存表
 * Created by PhpStorm.
 * User: htpc
 * Date: 2018/10/25
 * Time: 18:15
 */

namespace AtServer\Cache\Swoole;



use AtServer\Log\Log;

class ConnceInfo
{
	const _request_uri = 100;
	const _cookie = 4096;
	const _header = 8024;
	const _server = 2048;
	const _get = 1024;

	protected static $field =[
		[
			'name'=>'request_uri',
			'type' =>\swoole_table::TYPE_STRING,
			'len'  => 100,
		],
		[
			'name'=>'get',
			'type'=>\swoole_table::TYPE_STRING,
			'len' =>1024,
		],
		[
			'name'=>'cookie',
			'type'=> \swoole_table::TYPE_STRING,
			'len' =>4096,
		],
		[
			'name'=>'header',
			'type'=>\swoole_table::TYPE_STRING,
			'len'=>8024,
		],
		[
			'name'=>'server',
			'type'=>\swoole_table::TYPE_STRING,
			'len' =>2048
		]
	];

	protected static $table;

	/**
	 * 创建swoole内存表
	 */
	public static function create()
	{
		exec('ulimit -n',$arr);
		$table = new \swoole_table( $arr[0] );
		foreach (self::$field as $item){
			$table->column($item['name'],$item['type'] , $item['len']);       //1,2,4,8
		}
		$res = $table->create();
		if($res){
			self::$table = $table;
		}else{
			throw new \Exception('swooleTable内存表失败',44432111);
		}
	}



	/**
	 * 设置行的数据，swoole_table使用key-value的方式来访问数据
	 * @param       $key
	 * @param array $value
	 */
	public static function set( $key,  array $value )
	{
		$table = self::$table;
		if($table instanceof \swoole_table){
			return $table->set($key,$value);
		}else{
			throw new \Exception('内存表不存在',42220222);
		}
	}

	/**
	 * 获取一行数据
	 * @param      $key
	 * @param null $default
	 *
	 * @return array|mixed
	 */
	public static function get( $key , $field  = null )
	{
		$table = self::$table;
		if($table instanceof \swoole_table){
			return $table->get( $key, $field );
		}else{
			throw new \Exception('内存表不存在',42220222);
		}
	}

	/**
	 * 删除数据
	 * @param $key
	 *
	 * @return bool|mixed
	 */
	public static function del( $key )
	{
		$table = self::$table;
		if($table instanceof \swoole_table){
			return $table->del( $key );
		}else{
			throw new \Exception('内存表不存在',42220222);
		}
	}

	/**
	 * 原子自增操作。
	 * @param string $key
	 * @param string $column
	 * @param int    $incrby
	 *
	 * @return mixed
	 */
	public static function incr(string $key, string $column, $incrby = 1)
	{
		$table = self::$table;
		if($table instanceof \swoole_table){
			return $table->incr( $key, $column, $incrby );
		}else{
			throw new \Exception('内存表不存在',42220222);
		}
	}

	/**
	 * 原子自减操作
	 * @param string $key
	 * @param string $column
	 * @param int    $decrby
	 *
	 * @return mixed
	 */
	public static function decr(string $key, string $column,  $decrby = 1)
	{
		$table = self::$table;
		if($table instanceof \swoole_table){
			return $table->decr( $key, $column, $decrby );
		}else{
			throw new \Exception('内存表不存在',42220222);
		}
	}

	/**
	 * 检查table中是否存在某一个key。
	 * @param string $key
	 *
	 * @return mixed
	 */
	public static function exist(string $key)
	{
		$table = self::$table;
		if($table instanceof \swoole_table){
			return $table->exist( $key );
		}else{
			throw new \Exception('内存表不存在',42220222);
		}
	}

	/**
	 * 返回table中存在的条目数
	 * @return mixed
	 */
	public static function count()
	{
		$table = self::$table;
		if($table instanceof \swoole_table){
			return $table->count();
		}else{
			throw new \Exception('内存表不存在',42220222);
		}
	}

	/**
	 * 遍历所有table的行并删除
	 * @return mixed
	 */
	public static function delAll()
	{
		$table = self::$table;
		foreach ( $table as $key =>$val ){
			Log::log( $key );
			self::del($key);
		}
	}
}