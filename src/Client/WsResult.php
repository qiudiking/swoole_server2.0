<?php
/**
 * Created by PhpStorm.
 * User: user
 * Date: 2019/1/25
 * Time: 16:52
 */

namespace AtServer\Client;


class WsResult
{
	private $fd;

	private static  $instance = null;

	public $method = '';

	public $type = '';

	public $data = [];

	public $time = 0;

	public function __clone()
	{
		$this->method = '';
		$this->type = '';
		$this->data = [];
		$this->time = 0;
	}

	public function setFd( $fd )
	{
		$this->fd = $fd;
	}

	public function getFd()
	{
		return $this->fd;
	}

	public function __toString()
	{
		return json_encode(['method'=>$this->method,'type'=>$this->type,'data'=>$this->data,'time'=>time()]);
	}

	public static function Instance()
	{
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}
		return clone self::$instance;
	}
}