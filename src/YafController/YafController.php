<?php
/**
 * Created by PhpStorm.
 * User: htpc
 * Date: 2018/10/9
 * Time: 15:57
 */

namespace AtServer\YafController;



use AtServer\Client\Result;
use AtServer\CoroutineClient\CoroutineContent;
use AtServer\Exception\ThrowException;
use AtServer\server\HttpServer;
use AtServer\Sign\Sign;

class YafController extends \Yaf\Controller_Abstract
{

	/**
	 * @var \AtServer\Client\Result
	 */

	protected $result;
	
	protected $sign = false;

	private function result()
	{
		$this->result = Result::Instance();
	}

	/**
	 * @throws \AtServer\Exception\SignException
	 */
	protected function init()
	{
		if(isAjaxRequest()){
		    $this->result();
		}
		if($this->sign){
			Sign::Sign();
		}
	}

	/**
	 * 301重定向 url跳转
	 * @param string $url
	 *
	 * @return bool|void
	 * @throws \Exception
	 */
	public function redirect( $url ,$msg = '' ) {
		if(!isAjaxRequest()){
			$response = CoroutineContent::get('response');
			if($response instanceof \swoole_http_response){
				$response->redirect($url);
			}
		}
		$exception =   new \AtServer\Exception\RedirectException($msg);
		$exception->setRedirect_url($url);
		throw $exception;
	}

	/**
	 * 同步调用
	 * @return bool|null
	 * @throws \AtServer\Exception\ClientException
	 */
	protected function invoke()
	{

		$params = func_get_args();
		if( $params ){
			return \AtServer\Client\Client::instance()->invokeTcp($params);
		}
	}

	/**
	 * 异步调用  最后一个参数是函数的，将做异步回调
	 */
	protected function invokeAsync()
	{
		$params = func_get_args();
		if($params){
			\AtServer\Client\Client::instance()->invokeAsync($params);
		}
	}

	/**
	 * 异步请求TCP服务 并自动http响应
	 */
	protected function invokeAsyncResponse()
	{
		$params = func_get_args();
		if($params){
			\AtServer\Client\Client::instance()->invokeAsyncResponse($params);
		}

	}

	/**
	 * 异步投递任务
	 * @param $method
	 * @param $data
	 */
	protected function asyncTask()
	{
		$params = func_get_args();
		if( $params ){
			$params['action'] = $params[0];
			unset( $params[0] );
			$server = HttpServer::$serverInstance;
			if( $server instanceof \swoole_server ){
				$server->task( serialize($params) );
			}else{
				ThrowException::SystemException(77732,'Task异步任务投递失败');
			}
		}else{
			ThrowException::SystemException( 884767,'最少有一个参数' );
		}
	}

	/**
	 * 协程调用 暂时不能用
	 * @return null
	 * @throws \AtServer\Exception\ClientException
	 */
	private function invokeCoroutine()
	{
		$params = func_get_args();
		if($params){
			\AtServer\CoroutineClient\CoroutineClient::instance()->putGlobal();
			return  \AtServer\CoroutineClient\CoroutineClient::instance()->send($params);
		}
	}




}