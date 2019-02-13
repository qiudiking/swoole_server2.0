<?php
/**
 * Created by PhpStorm.
 * User: user
 * Date: 2018/12/21
 * Time: 14:39
 */

namespace AtServer\Client;


use AtServer\Log\Log;

class sendMessage extends WsMessageBase
{
	private static $message = [];


	/**
	 * HTTP获取需要发送的对象
	 * @param string                   $http_request_id
	 * @param \swoole_websocket_server $server
	 *
	 * @return bool|mixed
	 */
	public static function sendMessage(string  $http_request_id)
	{
		if( isset(self::$message[$http_request_id]) ){
			$wsMessage = self::$message[$http_request_id];
			unset(self::$message[$http_request_id]);
			if($wsMessage instanceof WsMessageBase){
				return $wsMessage;
			}else{
				Log::error('对象错误');
			}
		}
		return false;
	}

	/**
	 * 设置需要发送的消息
	 * @param string                         $http_request_id
	 * @param \AtServer\Client\WsMessageBase $wsMessageObj
	 */
	public static function setMessage( WsMessageBase $wsMessageObj)
	{
		self::$message[getRequestId()] = $wsMessageObj;
	}

	public function handshake( \swoole_http_request $request, \swoole_http_response $response ) {
		// TODO: Implement handshake() method.
	}

	public function open( \swoole_websocket_server $server, \Swoole\Http\Request $request ) {
		// TODO: Implement open() method.
	}

	public function message( \swoole_websocket_server $server, \Swoole\WebSocket\Frame $frame ) {
		// TODO: Implement message() method.
	}

	public function close( $server, $fd, $reactorId ) {
		// TODO: Implement close() method.
	}
}