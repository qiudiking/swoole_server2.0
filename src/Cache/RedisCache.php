<?php
/**
 * Created by pantian.
 * User: pantian
 * Date: 2016/6/2
 * Time: 16:25
 */

namespace AtServer\Cache;

use Noodlehaus\Config;
use AtServer\Log\Log;


class RedisCache implements CacheInterface {
	private static $instance;
	/**
	 * @var\Redis
	 */
	private $redisDB;

	private $currentDB=0;

	protected $dbName=0;

	public $clientName = '';

	public $hashKey = '';

	public $clientList = [];

	/**
	 * redis 对象数组
	 *
	 * @var array
	 */
	public static $redis = [];

	/**
	 * @param  array $config 配置
	 *
	 * @return RedisCache
	 */
	public static function instance( $config = null ) {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}
		if(is_null(self::$instance->redisDB )){
			self::$instance->connect();
		}
		return self::$instance;
	}

	/**
	 * RedisCache constructor.
	 *
	 * @param null $config
	 */
	public function __construct( $config = null ) {
		$this->clientName = getRandChar( 12 );
		$this->connect( $config );
	}

	/**
	 * 链接,可传入自定配置
	 *
	 * @param null $host           可以是config 数组
	 * @param int  $port           端口
	 * @param null $password       密码
	 * @param int  $database_index 数据库
	 *
	 * @throws RedisConnect
	 * @return bool
	 */
	public function connect( $host = null, $port = 0, $password = null, $database_index = - 1 ) {
		try {
			if ( is_array( $host ) ) {
				$config = $host;
				$host   = null;
			} else {
				$config = Config::load(getConfigPath())->get('redis',[]);
			}
			if ( ! $config ) {
				return false;
			}
			$host || $host = getArrVal( 'host', $config );
			$port || $port = getArrVal( 'port', $config );
			$password || $password = getArrVal( 'password', $config );
			if ( empty( $host ) || empty( $port ) ) {
				Log::error( 'redis 配置错误' );

				return false;
			}

			$hasKey        = md5( $host . $port . $database_index );
			$this->hashKey = $hasKey;
			if ( isset( self::$redis[ $hasKey ] ) && self::$redis[ $hasKey ] instanceof \Redis ) {
				$this->redisDB = self::$redis[ $hasKey ];

				return true;
			}

			$conn = new \Redis();
			$conn->connect( $host, $port,3 );
			if ( $password ) {
				$res = $conn->auth( $password );
				if ( ! $res ) {
					throw new \Exception( 'redis auth fail' . PHP_EOL );
				}
			}

			$database_index < 0 && $database = (int) getArrVal( 'database_index', $config );
			$database_index < 0 && $database = 0;
			//$this->selectDatabase( $database );
			$this->redisDB          = $conn;
			$this->selectDatabase( $database );
			if($this->redisDB->client( 'setname', $this->clientName )){
				//$clientList=$this->redisDB->client('list');
			}
			self::$redis[ $hasKey ] = $this->redisDB;
			Log::log( 'redis 链接成功');

			return true;
		} catch ( \Exception $e ) {
			//不能用Log::error()方法，会有死循环
			\SeasLog::error( 'redis 认证失败:' . $e->getMessage() . PHP_EOL . $e->getTraceAsString() );
			$this->redisDB=null;
			//throw new RedisConnect( 'redis 链接失败', $e->getCode() );
		}
	}

	/**
	 * redis 客户端链接信息,key 返回某个字段的值
	 *
	 * @param null $key
	 *
	 * @return bool|mixed
	 */
	public function getClientInfo($key=null){
		$this->clientList=$this->getRedis()->client('list');
		if($this->clientList && is_array($this->clientList)){
			foreach ( $this->clientList as $client ) {
				if($client['name']==$this->clientName){
					if(!is_null($key)){
						return getArrVal($key,$client);
					}
					return $client;
				}
			}
		}

		return false;
	}

	public function get( $key, $default = null ) {
		try {
			/*if ( false === $this->getRedis()->ping() && $this->reConnect() ) {
				return $this->get( $key, $default );
			}*/
			if ( $this->getRedis()->exists( $key ) ) {

				$res = $this->getRedis()->get( $key );
				if ( $res === false ) {
					/*if ( false === $this->getRedis()->ping() && $this->reConnect() ) {
						return $this->get( $key, $default );
					}*/

					Log::warning( 'redis 出错: 获取 ' . $key . ' 返回 false' );
					if ( ! is_null( $default ) ) {
						return $default;
					}
				}

				return $res;
			} else {
				//Log::warning( 'redis key ' . $key . ' 不存在' );
				return $default;
			}

		} catch ( \Exception $e ) {
			Log::log( 'redis 错误：'.$e->getMessage() );
			if ( $this->reConnectByException( $e ) ) {
				return $this->get( $key, $default );
			}
		}

		return false;
	}


	/**
	 * 获取key 过期时间
	 * @param      $key
	 * @param null $default
	 *
	 * @return bool|int|null|string
	 */
	public function ttl( $key, $default = null ) {
		try {
			if ( $this->getRedis()->exists( $key ) ) {
				$res = $this->getRedis()->ttl( $key );
				if ( $res === false ) {
					Log::warning( 'redis 出错: 获取 ' . $key . ' 返回 false' );
					if ( ! is_null( $default ) ) {
						return $default;
					}
				}
				return $res;
			} else {
				return $default;
			}
		} catch ( \Exception $e ) {
			Log::log( 'redis 错误：'.$e->getMessage() );
			if ( $this->reConnectByException( $e ) ) {
				return $this->get( $key, $default );
			}
		}
		return false;
	}



	/**
	 * 获取所有数据库
	 *
	 * @return array|bool
	 */
	public function getAll() {
		try {
			$keys = $this->keys();
			$res  = $this->getRedis()->mget( $keys );

			if ( $res === false ) {
				if ( false === $this->getRedis()->ping() && $this->reConnect() ) {
					return $this->getAll();
				}
			}else if(is_array($res)){
				$list = [];
				foreach ($res as $key=>$val){
					$list[ $keys[ $key ] ] = $val;
				}

				return $list;
			}

			return $res;

		} catch ( \Exception $e ) {

			if ( $this->reConnectByException( $e ) ) {
				return $this->getAll();
			}
		}

		return false;
	}

	/**
	 * @param int $dbIndex
	 *
	 * @return bool
	 */
	public function selectDatabase( $dbIndex = 0 ) {

		if($this->currentDB!=$dbIndex){
			if($this->redisDB){
				if($this->redisDB->select($dbIndex)){
					$this->currentDB = $dbIndex;
					Log::log('select db='. $this->currentDB);
				}
			}
		}

	}

	/**
	 * 获取当前数据名 index
	 * @return int
	 */
	public function getCurrentDBIndex(){
		return $this->currentDB;
	}

	/**
	 * 数值自减,可以是整数或浮点数
	 * @param     $key
	 * @param int|float $step
	 *
	 * @return mixed
	 */
	public function decr( $key,$step=1.0) {
		try{
			if($key){
				if(is_int($step)){
					if($step==1){
						$res=$this->getRedis()->decr( $key );
					}else{
						$res=$this->getRedis()->decrBy($key,(int)$step);
					}
				}else if(is_float($step)){
					$res=$this->getRedis()->incrByFloat($key,$step*(-1));
				}
				if ( false === $res && false === $this->getRedis()->ping() && $this->reConnect() ) {
					return $this->decr($key,$step);
				}
			}
		}catch(\Exception $e){
			if ( $this->reConnectByException( $e ) ) {
				return $this->decr($key,$step);
			}
		}

	}


	/**
	 * 设置key 值
	 *
	 * @param        $key
	 * @param string $value
	 * @param null   $timeout
	 *
	 * @return bool
	 */
	public function set( $key, $value = '', $timeout = null ) {
		try {
			if(!is_null($timeout))$timeout=(int) $timeout;
			$res = $this->getRedis()->set( $key, $value, $timeout );
			if ( false === $res && false === $this->getRedis()->ping() && $this->reConnect() ) {
				return $this->set( $key, $value, $timeout );
			}

			return $res;

		} catch ( \Exception $e ) {
			if ( $this->reConnectByException( $e ) ) {
				return $this->set( $key, $value, $timeout );
			}
		}

		return false;
	}

	/**
	 * @return \Redis
	 * @throws \RedisException
	 */
	public function getRedis() {
		if(is_null($this->redisDB)){
			$this->connect();

			//throw new \RedisException( 'redis instance is invalid', 600 );

		}
		if ( is_null($this->redisDB ) || !$this->redisDB instanceof \Redis ) {
			throw new \RedisException( 'redis instance is invalid', 600 );
		}
		//$this->selectDatabase( $this->dbName );
		return $this->redisDB;
	}

	/**
	 * 删除key ,key 可以是一个key的数组
	 *
	 * @param $key
	 *
	 * @return int
	 */
	public function del( $key ) {
		try {
			$res = $this->getRedis()->del( $key );
			if ( false === $res && false === $this->getRedis()->ping() && $this->reConnect() ) {
				//return $this->del( $key );
			}

			return $res;
		} catch ( \Exception $e ) {
			if ( $this->reConnectByException( $e ) ) {
				//return $this->del( $key );
			}
		}

		return false;
	}

	/**
	 * 设置hash表的key value 对值
	 *
	 * @param $hash
	 * @param $key
	 * @param $value
	 *
	 * @return bool
	 */
	function hSet( $hash, $key, $value ) {

		try {

			if ( $hash && $key ) {
				$res = $this->getRedis()->hSet( $hash, $key, $value );
				if ( $res === false ) {
					if ( $this->getRedis()->ping() === false && $this->reConnect() ) {
						//return $this->hSet( $hash, $key, $value );
					}
				}

				return $res;
			}
		} catch ( \Exception $e ) {
			if ( $this->reConnectByException( $e ) ) {
				//return $this->hSet( $hash, $key, $value );
			}
		}

		return false;
	}

	/**
	 * 哈希表长
	 *
	 * @param $hash
	 *
	 * @return bool|int
	 */
	public function hLen( $hash ) {
		try {

			if ( $hash ) {
				$res = $this->getRedis()->hLen( $hash );
				if ( $res === false ) {
					if ( $this->getRedis()->ping() === false && $this->reConnect() ) {
						return $this->hLen( $hash );
					}
				}

				return $res;
			}
		} catch ( \Exception $e ) {
			if ( $this->reConnectByException( $e ) ) {
				return $this->hLen( $hash );
			}
		}

		return false;
	}

	/**
	 * 检测哈希表的key是否存在
	 *
	 * @param $hash
	 * @param $key
	 *
	 * @return bool
	 */
	public function hExists( $hash, $key ) {
		try {
			if ( $hash && $key ) {
				$res = $this->getRedis()->hExists( $hash, $key );
				if ( $res === false ) {
					if ( $this->getRedis()->ping() === false && $this->reConnect() ) {
						return $this->hExists( $hash, $key );
					}
				}

				return $res;
			}
		} catch ( \Exception $e ) {
			if ( $this->reConnectByException( $e ) ) {
				return $this->hExists( $hash, $key );
			}
		}

		return false;
	}

	/**
	 * 获取哈希表的全部数据
	 *
	 * @param $hash
	 *
	 * @return array|bool
	 */
	public function hGetAll( $hash ) {
		try {
			if ( $hash ) {
				$res = $this->getRedis()->hGetAll( $hash );
				if ( $res === false ) {
					if ( $this->getRedis()->ping() === false && $this->reConnect() ) {
						return $this->hGetAll( $hash );
					}
				}

				return $res;
			}
		} catch ( \Exception $e ) {
			if ( $this->reConnectByException( $e ) ) {
				return $this->hGetAll( $hash );
			}
		}

		return false;
	}

	/**
	 * 获取哈希表的字段
	 *
	 * @param $hash
	 *
	 * @return array|bool
	 */
	public function hKeys( $hash ) {
		try {
			if ( $hash ) {
				$res = $this->getRedis()->hkeys( $hash );
				if ( $res === false ) {
					if ( $this->getRedis()->ping() === false && $this->reConnect() ) {
						return $this->hkeys( $hash );
					}
				}

				return $res;
			}
		} catch ( \Exception $e ) {
			if ( $this->reConnectByException( $e ) ) {
				return $this->hkeys( $hash );
			}
		}

		return false;
	}

	/**
	 * 设置多个哈希表key
	 * @param       $hash
	 * @param array $array
	 *
	 * @return bool
	 */
	public function hMset( $hash, array $array ) {
		try {
			if ( $hash && $array ) {
				$res = $this->getRedis()->hMset( $hash, $array );
				if ( $res === false ) {
					if ( $this->getRedis()->ping() === false && $this->reConnect() ) {
						return $this->hMset( $hash, $array );
					}
				}

				return $res;
			}
		} catch ( \Exception $e ) {
			if ( $this->reConnectByException( $e ) ) {
				return $this->hMset( $hash, $array );
			}
		}

		return false;
	}

	/**
	 * 获取多个哈希表key的值
	 *
	 * @param       $hash
	 * @param array $array
	 *
	 * @return array|bool
	 */
	public function hMGet( $hash, array $array ) {
		try {
			if ( $hash && $array ) {
				$res = $this->getRedis()->hMGet( $hash, $array );
				if ( $res === false ) {
					if ( $this->getRedis()->ping() === false && $this->reConnect() ) {
						return $this->hMGet( $hash, $array );
					}
				}

				return $res;
			}
		} catch ( \Exception $e ) {
			if ( $this->reConnectByException( $e ) ) {
				return $this->hMGet( $hash, $array );
			}
		}

		return false;
	}

	/**
	 * 获取 hashmap 中的key值
	 *
	 * @param      $hash
	 * @param      $key
	 * @param null $default
	 *
	 * @return bool|null
	 */
	function hGet( $hash, $key, $default = null ) {
		try {
			if ( $hash && $key ) {
				$res = $this->getRedis()->hGet( $hash, $key );
				if ( $res === false ) {
					if ( $this->getRedis()->ping() === false && $this->reConnect() ) {
						return $this->hGet( $hash, $key, $default );
					}
					if ( ! is_null( $default ) ) {
						return $default;
					}
				}

				return $res;
			}
		} catch ( \Exception $e ) {
			if ( $this->reConnectByException( $e ) ) {
				return $this->hGet( $hash, $key, $default );
			}
		}

		return false;

	}

	/**
	 *整数或浮点数自增
	 *
	 * @param     $key
	 * @param int $step
	 *
	 * @return float|int
	 */
	public function incr($key,$step=1) {
		try{
			if($key){
				if(is_int($step)){
					if($step==1){
						$res=$this->getRedis()->incr( $key );
					}else{
						$res=$this->getRedis()->incrBy( $key,$step );
					}
				}elseif(is_float($step)){
					$res = $this->getRedis()->incrByFloat( $key, $step );
				}else{
					return false;
				}
				if ( false ===$res && $this->getRedis()->ping() === false && $this->reConnect() ) {
					return $this->Incr(  $key, $step );
				}

				return $res;
			}
		}catch(\Exception $e){
			if ( $this->reConnectByException( $e ) ) {
				return $this->Incr( $key, $step );
			}
		}
	}

	/**
	 * 哈希表key值自增
	 * @param     $hash
	 * @param     $key
	 * @param int $step
	 *
	 * @return bool|int
	 */
	function hIncrBy( $hash, $key, $step = 1 ) {
		try {
			if ( $hash && $key ) {
				$res = $this->getRedis()->hIncrBy( $hash, $key, $step );
				if ( $res === false && $this->getRedis()->ping() === false && $this->reConnect() ) {
					return $this->hIncrBy( $hash, $key, $step );
				}

				return $res;
			}
		} catch ( \Exception $e ) {
			if ( $this->reConnectByException( $e ) ) {
				return $this->hIncrBy( $hash, $key, $step );
			}
		}

		return false;
	}

	/**
	 * 浮点数自增
	 *
	 * @param       $hash
	 * @param       $key
	 * @param float $step
	 *
	 * @return bool|float|int
	 */
	function hIncrByFloat( $hash, $key, $step = 1.0 ) {
		try {
			if ( $hash && $key ) {
				$res = $this->getRedis()->hIncrByFloat( $hash, $key, floatval($step) );
				if ( $res === false && $this->getRedis()->ping() === false && $this->reConnect() ) {
					return $this->hIncrByFloat( $hash, $key, $step );
				}

				return $res;
			}
		} catch ( \Exception $e ) {
			if ( $this->reConnectByException( $e ) ) {
				return $this->hIncrByFloat( $hash, $key, $step );
			}
		}

		return false;
	}

	/**
	 * 通过正则表达式获取keys
	 *
	 * @param string $pattern
	 *
	 * @return array
	 */
	public function keys( $pattern = '*' ) {
		try {
			$res = $this->getRedis()->keys( $pattern );
			if ( $res === false && $this->getRedis()->ping() === false && $this->reConnect() ) {
				return $this->keys( $pattern );
			}

			return $res;

		} catch ( \Exception $e ) {
			if ( $this->reConnectByException( $e ) ) {
				return $this->keys( $pattern );
			}
		}
	}

	/**
	 * 清空当前库
	 *
	 * @return bool
	 */
	public function clean() {
		try {

			$res = $this->getRedis()->flushDB();
			if ( $res === false && $this->getRedis()->ping() === false && $this->reConnect() ) {
				return $this->clean();
			}

			return $res;

		} catch ( \Exception $e ) {
			if ( $this->reConnectByException( $e ) ) {
				return $this->clean();
			}
		}
	}


	/**
	 * 重复链接次数
	 *
	 * @var int
	 */
	private $reConnectNum = 0;

	/**
	 * 重复链接
	 */
	public function reConnect() {
		if ( $this->reConnectNum < 3 ) {
			unset( self::$redis[ $this->hashKey ] );
			$this->reConnectNum++;
			$res = $this->connect();
			if ( $res ) {
				$this->reConnectNum = 0;
			}

			return $res;
		}

		return false;
	}


	/**
	 * 删除hashmap中的key
	 *
	 * @param $hash
	 * @param $key
	 *
	 * @return bool
	 */
	function hDel( $hash, $key ) {
		try {

			if ( $hash && $key ) {
				$res = $this->getRedis()->hDel( $hash, $key );
				if ( $res === false && $this->getRedis()->ping() === false && $this->reConnect() ) {
					return $this->hDel( $hash, $key );
				}

				return $res;
			}
		} catch ( \Exception $e ) {
			Log::log( 'Redis 删除失败:' . $e->getMessage() );
			throw $e;
			/*if ( $this->reConnectByException( $e ) ) {
				return $this->hDel( $hash, $key );
			}*/
		}

		return false;
	}

	/**
	 * 插入队列
	 *
	 * @param $key
	 * @param $data
	 *
	 * @return int
	 */
	public function LPush( $key, $data ) {
		$data = serialize( $data );
		try {
			$res = $this->getRedis()->lPush( $key, $data );

			if ( false === $res && false === $this->getRedis()->ping() && $this->reConnect() ) {

				return $this->LPush( $key, $data );
			}

			return $res;
		} catch ( \Exception $e ) {
			if ( $this->reConnectByException( $e ) ) {
				return $this->LPush( $key, $data );
			}
		}

		return false;
	}

	public function checkConnect() {
		if(false === $this->getRedis()->ping()){
			$this->connect();
		}
	}

	/**
	 * 异常重链接
	 *
	 * @param \RedisException $redisException
	 *
	 * @return bool
	 */
	public function reConnectByException( \RedisException $redisException ) {

		if ( $redisException->getMessage() == 'Connection lost' ) {
			return $this->reConnect();
		}

		return false;
	}


	/**
	 * 获取队列数据
	 *
	 * @param     $key
	 * @param int $length 长度
	 *
	 * @return array|bool
	 */
	public function LPop( $key, $length = 1 ) {
		try {
			$list = [];
			for ( $i = 0; $i < $length; $i ++ ) {

				/*if ( $this->getRedis()->ping() === false && $this->reConnect() ) {

					return $this->LPop( $key, $length );
				}*/
				$data = $this->getRedis()->lPop( $key );
				if ( $data ) {
					$list[] = unserialize( $data );
				} else {
					break;
				}
			}

			return $list;
		}catch( \Exception $e ) {
			//$this->reConnectByException( $e ) ;
			Log::warning($e->getMessage().$e->getTraceAsString());
			/*if ( $this->reConnectByException( $e ) ) {
				return $this->LPop( $key, $length );
			}*/
		}

		return false;
	}

	public function ping() {
		try{
			$this->getRedis()->ping() === false && $this->reConnect();
		}catch(\Exception $e){
			$this->reConnectByException( $e );
		}
	}

	/**
	 * 队列长度
	 *
	 * @param $key
	 *
	 * @return bool|int
	 */
	public function LLen( $key ) {
		if ( ! $key ) {
			return false;
		}
		try {
			$res = $this->getRedis()->lLen( $key );
			if ( false === $res && false === $this->getRedis() && $this->reConnect() ) {
				return $this->LLen( $key );
			}

			return $res;
		} catch ( \Exception $e ) {
			if ( $this->reConnectByException( $e ) ) {
				return $this->LLen( $key );
			}
		}

		return false;
	}


	/**
	 * 有序集合 分数自增 (float/int)
	 * @param     $key
	 * @param     $member
	 * @param int $value
	 *
	 * @return float
	 */
	public function zIncrby( $key, $member,  $value = 1 )
	{
		try{
			if($key){
				$res=$this->getRedis()->zIncrBy( $key, $value, $member );
				if ( false ===$res && $this->getRedis()->ping() === false && $this->reConnect() ) {
					return $this->zIncrBy(  $key, $value, $member );
				}
				return $res;
			}
		}catch(\Exception $e){
			if ( $this->reConnectByException( $e ) ) {
				return $this->zIncrby( $key, $member,  $value = 1 );
			}
		}
	}

	/**
	 * Redis Zrevrange 命令返回有序集中，指定区间内的成员。
	 * 其中成员的位置按分数值递减(从大到小)来排列。
	 * 具有相同分数值的成员按字典序的逆序(reverse lexicographical order)排列。
	 * @param      $key
	 * @param int  $start
	 * @param int  $end
	 * @param null $default
	 *
	 * @return array|bool|null
	 */
	public function  zRevRange($key,$start = 0,$end = -1, $withscore = true, $default = null){
		try {
			if ( $this->getRedis()->exists( $key ) ) {
				$res = $this->getRedis()->zRevRange( $key, $start, $end, $withscore );
				if ( $res === false ) {
					Log::warning( 'redis 出错: 获取 ' . $key . ' 返回 false' );
					if ( ! is_null( $default ) ) {
						return $default;
					}
				}
				return $res;
			} else {
				return $default;
			}
		} catch ( \Exception $e ) {
			Log::log( 'redis 错误：'.$e->getMessage() );
			if ( $this->reConnectByException( $e ) ) {
				return $this->zRevRange( $key,$start,$end,$default );
			}
		}
		return false;
	}


	/**
	 * Redis Zscore 命令返回有序集中，成员的分数值。 如果成员元素不是有序集 key 的成员，或 key 不存在，返回 nil 。
	 * @param      $key
	 * @param      $member
	 * @param null $default
	 *
	 * @return array|bool|float|null
	 */
	public function zScore( $key, $member, $default = null){
		try {
			if ( $this->getRedis()->exists( $key ) ) {
				$res = $this->getRedis()->zScore( $key, $member );
				if ( $res === false ) {
					Log::warning( 'redis 出错: 获取 ' . $key . ' 返回 false' );
					if ( ! is_null( $default ) ) {
						return $default;
					}
				}
				return $res;
			} else {
				return $default;
			}
		} catch ( \Exception $e ) {
			Log::log( 'redis 错误：'.$e->getMessage() );
			if ( $this->reConnectByException( $e ) ) {
				return $this->zScore( $key, $member, $default);
			}
		}
		return false;
	}

	/**
	 * Redis Zcard 命令用于计算集合中元素的数量。
	 * @param      $key
	 * @param null $default
	 *
	 * @return array|bool|int|null
	 */
	public function zCard( $key, $default = null){
		try {
			if ( $this->getRedis()->exists( $key ) ) {
				$res = $this->getRedis()->zCard( $key );
				if ( $res === false ) {
					Log::warning( 'redis 出错: 获取 ' . $key . ' 返回 false' );
					if ( ! is_null( $default ) ) {
						return $default;
					}
				}
				return $res;
			} else {
				return $default;
			}
		} catch ( \Exception $e ) {
			Log::log( 'redis 错误：'.$e->getMessage() );
			if ( $this->reConnectByException( $e ) ) {
				return $this->zCard( $key, $default = null );
			}
		}
		return false;
	}
}
