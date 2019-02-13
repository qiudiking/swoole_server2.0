<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/8/5
 * Time: 17:55
 */

namespace AtServer\Client;




use AtServer\server\HttpServer;

class Response {
	static $responseList = [];
	/**
	 * 响应客户端
	 * @param $request_id
	 * @param $data
	 */
	public static function responseToHttp($request_id,Result $data){
		if($request_id && isset(self::$responseList[$request_id])){
			$data->setRequestId(getRequestId());
		    $str = (string) $data;
		    $_res = self::$responseList[ $request_id ];
		    $res = getArrVal( 'response', $_res );
		    if($res instanceof \swoole_http_response){
				HttpServer::sendContent($res,$str);
		    }
		    unset(self::$responseList[$request_id],$_res);
	    }
	}
}