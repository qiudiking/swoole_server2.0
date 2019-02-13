<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/6/11
 * Time: 15:01
 */

namespace AtServer\Client;


class ClientParams {
	/**
	 * 保存未定义的属性
	 * @var array
	 */
	private $_property = [];

	private function __construct() {
		$this->init();
	}

	function __clone() {
		$this->init();
	}

	public function init(){
		$this->method = '';
		$this->callParams = [];
		$this->result=null;
		$this->exception_message = null;
		$this->exception_code = 0;
	}

	function __set( $name, $value ) {
		$this->_property[ $name ] = $value;
	}

	function __get( $name ) {
		if(isset($this->_property[$name])){
			return $this->_property[ $name ];
		}

		return null;
	}

	/**
	 * 获取所有自定义的属性值
	 * @return array
	 */
	public function getPropertyValues() {
		return $this->_property;
	}

	/**
	 * 异常代码
	 * @var int
	 */
	public $exception_code=0;
	/**
	 * 异常信息
	 * @var string
	 */
	public $exception_message = null;

	/**
	 * 调用服务的方法名
	 *
	 * @var string
	 */
	public $method = '';
	/**
	 * 调用方法的参数
	 * @var array
	 */
	public $callParams = [];
	/**
	 * 返回结果
	 * @var null
	 */
	public $result;
	/**
	 * 自动响应
	 * @var bool
	 */
	public $isResponse=false;
	public $request_id = '';

	private static $instance;

	public function setExceptionMessage( string $message='', $code =0) {
		$this->exception_code = (int)$code;
		$this->exception_message = $message;
	}

	public static function instance() {
		if(!self::$instance){
		    self::$instance=new self();
		}
		return clone self::$instance;
	}
}