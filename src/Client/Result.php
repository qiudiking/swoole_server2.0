<?php
/**
 * Created by PhpStorm.
 * User: htpc
 * Date: 2018/8/18
 * Time: 15:05
 */

namespace AtServer\Client;


class Result {
	protected $data;
	protected static $instance = null;
	protected $fds;

	public function __construct() {
		$this->init();
	}

	public function __clone()
	{
		$this->init();
	}

	/**
	 *
	 * @return $this
	 */
	public function init() {
		$this->data = array( 'code' => 0, 'msg' => '', 'data' => '','_time'=>time() ,'_request_id'=>getRequestId());
		return $this;
	}

	/**
	 * @param $fd
	 */
	public function setFds( $fd )
	{
		$this->fds = $fd;
	}

	/**
	 * @return mixed
	 */
	public function getFd()
	{
		return $this->fds;
	}

	/**
	 * @return string
	 */
	public function getData() {
		return $this->data;
	}

	/**
	 * @param array $data
	 */
	public function setData( $data ) {
		$this->data['data'] = $data;
	}

	public function setRequestId($id){
		$this->data['_request_id']=$id;
	}


	/**
	 * 设置所有数据，全新的data
	 *
	 * @param array $data
	 */
	public function setAllData( array $data ) {
		$this->data = $data;
		$this->data['_request_id'] = getRequestId();
	}

	/**
	 * 设置数据
	 * this->data[$key]=$val
	 *
	 * @param $key
	 * @param $val
	 */
	function set( $key, $val ) {
		$this->data[ $key ] = $val;
	}

	/**
	 * 设数据data数组下的数据
	 *
	 * $this->data['data'][$key] = $val;
	 *
	 * @param $key
	 * @param $val
	 */
	function setForData( $key, $val ) {
		$this->data['data'][ $key ] = $val;
	}

	function setCode( $code ) {
		$this->data['code'] = $code;
	}

	/**
	 * @version 2.1
	 *
	 * @param $msg
	 * @param $code
	 */
	public function setCodeMsg( $msg, $code ) {
		$this->setCode( $code );
		$this->setMsg( $msg );
	}

	/**
	 * 设置系统错误码
	 * @param  int $code 系统错误码
	 * @param  string $msg 错误信息，不传此值，默认为系统错误信息
	 */
	public function setSysErrorCode( $code ,$msg='')
	{
		$this->setCode( $code );
		$this->setMsg(  $msg);
	}
	/**
	 * @return int
	 */
	public function getErrCode() {
		return $this->data['code'];
	}

	/**
	 * @return string
	 */
	public function getMsg() {
		return $this->data['msg'];
	}

	/**
	 * @param string $msg
	 */
	public function setMsg( $msg ) {
		$this->data['msg'] = $msg;
	}

	/**
	 * 返回 json格式
	 *
	 * @return bool|string
	 */
	function getJson() {

		if ( is_array( $this->data ) ) {
			$this->data['_time'] = time();
			return json_encode( $this->data );
		}
		return false;
	}


	public function __toString() {
		return $this->getJson();
	}

	static function Instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}

		return clone self::$instance;
	}
}