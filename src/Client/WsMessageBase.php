<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/6/18
 * Time: 22:52
 */

namespace AtServer\Client;




abstract class WsMessageBase
{

	public $result =  [];
	/**
	 * 对多人
	 */
	const SEND_MESSAGE_PUBLIC = 'public';
	/**
	 * 对自己
	 */
	const SEND_MESSAGE_MY = 'my';
	/**
	 * 对其他
	 */
	const SEND_MESSAGE_OTHER = 'other';


	/**
	 * @param        $fds
	 * @param string $send_message
	 *
	 * @return \AtServer\Client\WsResult
	 */
	public function setResult( $fds, $send_message = self::SEND_MESSAGE_PUBLIC )
	{
		if(!isset($this->result[$send_message])){
			$result = \AtServer\Client\WsResult::Instance();
			$result->setFd( $fds );
			$this->result[$send_message] = $result;
		}
		return $this->result[$send_message];
	}

	abstract public function handshake( \swoole_http_request $request, \swoole_http_response $response );


	abstract public function open(\swoole_websocket_server $server, \Swoole\Http\Request $request);

	abstract public function message(\swoole_websocket_server $server, \Swoole\WebSocket\Frame $frame);

	abstract  function close($server, $fd, $reactorId);
}