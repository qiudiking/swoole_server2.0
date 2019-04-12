<?php
/**
 * Created by PhpStorm.
 * User: user
 * Date: 2019/4/11
 * Time: 16:09
 */

namespace AtServer\Http;


use AtServer\CoroutineClient\CoroutineContent;
use AtServer\Exception\ThrowException;

class Response
{
	/**
	 * @var null|\swoole_http_response
	 */
	protected  $response;

	/**
	 * Response constructor.
	 */
	public function __construct()
	{
		$response = CoroutineContent::get('response');
		if($response instanceof \swoole_http_response){
			$this->response = $response;
		}else{
			ThrowException::SystemException( 8833,'response对象错误' );
		}
	}

	public static function instance()
	{
		return new static();
	}

	/**
	 * HTTP跳转 重定向302
	 * @param        $url
	 * @param string $msg
	 *
	 * @throws \AtServer\Exception\RedirectException
	 */
	public  function redirect( $url, $msg = '' )
	{
		if(Request::instance()->isAjax() === false ){
			$this->response->redirect( $url );
		}
		$exception =   new \AtServer\Exception\RedirectException($msg);
		$exception->setRedirect_url($url);
		throw $exception;
	}

	/**
	 * 设置响应头
	 * @param  array | string   $name
	 * @param string $value
	 */
	public function setHeader( $name, $value = '' )
	{
		if(is_array($name)){
		    foreach ( $name as $key => $item ){
			    $this->response->header( $key, $item );
		    }
		}else{
			$this->response->header( $name, $value );
		}
	}

	/**
	 * 设置响应状体码
	 * @param $code
	 */
	public function setStatusCode( $code )
	{
		$this->response->status( $code );
	}

	/**
	 * 发送文件给浏览器
	 * @param $fileName
	 */
	public function sendFile( $fileName )
	{
		CoroutineContent::put('IS_RESPONSE',1);
		return $this->response->sendfile( $fileName );
	}

}