<?php
/**
 * Created by PhpStorm.
 * User: pantian
 * Date: 2014/12/27
 * Time: 0:26
 */

namespace AtServer\DB\Connect;

use AtServer\Exception\ErrorHandler;
use AtServer\Exception\DBException;
use AtServer\Log\Log;


class PDOConnect {
	static $instance = null;
	/**
	 * 数据库名
	 *
	 * @var null
	 */
	private $dbName = null;
	/**
	 * 数据库密码
	 *
	 * @var null
	 */
	private $password = null;

	/**
	 * 数据库服务器地址
	 *
	 * @var null
	 */
	private $host = null;
	/**
	 * 数据库服务端口
	 *
	 * @var null
	 */
	private $port = null;
	/**
	 * 数据库用户名
	 *
	 * @var null
	 */
	private $user = null;
	/**
	 * 表前缀
	 *
	 * @var null
	 */
	private $prefix = null;
	/**
	 * 数据库信息
	 *
	 * @var array
	 */
	public $DBConfig = [];

	public $currentDbName = '';


	private $db = null;
	/**
	 * 开始链接时间
	 *
	 * @var int
	 */
	static $connect_start_time = 0;
	/**
	 * mysql会话超时时间
	 *
	 * @var int
	 */
	static $session_wait_timeout = 0;

	function __construct( $dbName = null ) {
		$dbName || $this->dbName = $dbName;
	}


	function connect( $host = null, $port = null, $user = null, $password = '', $dbName = '', $prefix = '', $timeout = 0 ) {
		if ( $this->db ) {
			return true;
		}
		if ( is_array( $host ) ) {
			$config   = $host;
			$host     = getArrVal( 'host', $config, 'localhost' );
			$user     = getArrVal( 'user', $config, 'root' );
			$port     = getArrVal( 'port', $config, '3306' );
			$password = getArrVal( 'password', $config, '123456' );
			$dbName   = getArrVal( 'db_name', $config, 'mysql' );
			$prefix   = getArrVal( 'prefix', $config );
			$timeout  = getArrVal( 'timeout', $config );
			$charset  = getArrVal( 'charset', $config );
			$charset || $charset = 'utf8';
		}
		$this->prefix = $prefix;
		$dsn          = "mysql:host={$host};port={$port}";
		\AtServer\Log\Log::log($this->dbName);

		if ( $this->dbName ) {
			$dbName = $this->dbName;
		}
		if ( $dbName ) {
			$dsn .= ';dbname=' . $dbName;
		}
		try {
			if ( ! $password ) {
				\AtServer\Log\Log::error( '数据库密码为空：' );
				trigger_error( '数据库密码为空', E_USER_ERROR );
			}
		//	Log::log( '数据库编码:' . 'SET NAMES ' . $charset );
			$this->db                 = new \PDO( $dsn, $user, $password, array(
				\PDO::ATTR_PERSISTENT         => true,
				\PDO::ATTR_ERRMODE            => \PDO::ERRMODE_EXCEPTION,
				\PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES '. $charset,
			) );
			$this->currentDbName      = $dbName;
			self::$connect_start_time = time();
			\AtServer\Log\Log::debug( '数据库链接　OK' );

			return true;
		} catch ( \PDOException $e ) {
		//	Log::error( 'mysql 数据库链接失败：' . $e->getMessage() . ' .   链接 dsn=' . $dsn . PHP_EOL . '配置：' . print_r( $config, true ) );
			throw new DBException( ErrorHandler::DB_CONNECT_FAIL, $e->getMessage() );
		}
	}

	/**
	 * 检测链接
	 */
	function chkConnect() {
		if ( self::$session_wait_timeout > 0 && ( time() - self::$connect_start_time ) > ( self::$session_wait_timeout ) ) {
			unset( $this->db );
			$this->connect();
		}
	}

	/**
	 * @return null|PDOConnect
	 */
	static function instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * @return null|\PDO
	 */
	function getPDO() {
		return $this->db;
	}

	/**
	 * @return null
	 */
	public function getDbName() {
		return $this->dbName;
	}

	/**
	 * @return null
	 */
	public function getPassword() {
		return $this->password;
	}

	/**
	 * @param null $password
	 */
	public function setPassword( $password ) {
		$this->password = $password;
	}

	/**
	 * @return null
	 */
	public function getHost() {
		return $this->host;
	}

	/**
	 * @param null $host
	 */
	public function setHost( $host ) {
		$this->host = $host;
	}

	/**
	 * @return null
	 */
	public function getPort() {
		return $this->port;
	}

	/**
	 * @return null
	 */
	public function getUser() {
		return $this->user;
	}

	/**
	 * @return null
	 */
	public function getPrefix() {
		return $this->prefix;
	}

	/**
	 * @return null
	 */
	public function getDb() {
		return $this->db;
	}

	/**
	 * 设置数据对象
	 *
	 * @param $db
	 */
	public function setDb( $db ) {
		$this->db            = $db;
		$this->currentDbName = '';
	}
}