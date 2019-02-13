<?php
/**
 * Created by PhpStorm.
 * User: htpc
 * Date: 2018/8/10
 * Time: 19:22
 */

namespace AtServer\Client;



use Noodlehaus\Config;
use AtServer\CoroutineClient\CoroutineContent;
use AtServer\Exception\ClientException;
use AtServer\Log\Log;
use AtServer\server\HttpServer;

class Client {
	const SWOOLE_SOCK_TCP = SWOOLE_SOCK_TCP;
	const SWOOLE_SOCK_UDP = SWOOLE_SOCK_UDP;


	public static $instance;
	public $config;
	public static $client = [];
	protected function __construct()
	{
		$this->getConfig();
	}

	public function getConfig()
	{
		$this->config = new Config(getConfigPath());
	}

	public static function instance()
	{
		if(!self::$instance){
		    self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * 异步发送数据
	 * @param      $params
	 * @param bool $isResponse
	 * @param null $callback
	 *
	 * @return bool
	 */
	public function invokeAsyncTcp($params,$isResponse = false,$callback =null)
	{
		$host = $this->config['TCPserver']['server.host'];
		$port = $this->config['TCPserver']['server.port'];
		$clientParam             = ClientParams::instance();
		if(!isset($params[0])){
			return false;
		}

		$clientParam->method     = $params[0];
		unset($params[0]);
		$clientParam->callParams = $params;
		$clientParam->isResponse = $isResponse;
		$clientParam->request_id = getRequestId();
		$sendData           = Pack::sendEncode( Pack::encode( serialize($clientParam) ) );

		if(isset(self::$client[md5('Async',$host.$port)])){
			$client = self::$client[md5('Async',$host.$port)];
		    if($client->isConnected()){
		        $client->send($sendData);
		        return true;
		    }else{

		    	$this->connect($client,$host,$port);
		    }
		}else{
			$client = new \swoole_client(SWOOLE_SOCK_TCP,SWOOLE_SOCK_ASYNC);
			$config = [
				'open_length_check'     => true,
				'package_length_type'   => 'N',
				'package_max_length'    => 1024*1024*4,//10M
				'package_body_offset'   => 4,
				'package_length_offset' => 0,
			];
			$client->set( $config );

			$client->on('connect',function (\swoole_client $client)use ($sendData,$host,$port){
				self::$client[md5('Async',$host.$port)] = $client;
				$client->send($sendData);
			});
			$client->on('receive',function (\swoole_client $client,string $data)use ($callback){
				$data = unserialize( Pack::decode( Pack::decodeData( $data ) ) );
				if($data->isResponse){
					if($data->result instanceof Result){
						$data->result->setRequestId( $data->request_id );
					}else{
						$data->result=Result::Instance();
						$data->result->setRequestId( $data->request_id );
						$data->exception_code && $data->result->setCodeMsg($data->exception_message,$data->exception_code);
					}
					Response::responseToHttp($data->request_id,$data->result);
				}else{
					if(! is_null($callback) && is_callable($callback)){
						if ( $data instanceof ClientParams ) {
							if($data->exception_code !=0){
								$callback( $data );
							}else{
								$callback( $data->result );
							}
						} else {
							$callback( $data );
						}
					}
				}
			});
			$client->on( "error", function ( \swoole_client $cli ) use ($host,$port,$clientParam) {
				unset(self::$client[md5('Async',$host.$port)]);
				if($clientParam->isResponse){
					$result=Result::Instance();
					$result->setRequestId( $clientParam->request_id );
					$result->setCodeMsg('异步客户端错误',$cli->errCode);
					Response::responseToHttp($clientParam->request_id,$result);
				}
				Log::error('异步客户端错误,code='.$cli->errCode);
			} );
			$client->on( "close", function ( \swoole_client $cli )use ($host,$port) {
				unset(self::$client[md5('Async',$host.$port)]);
				Log::warning( "异步TCP连接关闭" );
			} );
			$this->connect($client,$host,$port);
		}
	}

	/**
	 * 异步请求TCP服务 并自动http响应
	 * @param $params
	 */
	public function  invokeAsyncResponse($params){
		//$params  = func_get_args();
		CoroutineContent::put('IS_RESPONSE',true);
		$request_id = getRequestId();
		if($request_id){
			$data = [];
			$data['response']= CoroutineContent::get('response');
			$data['request_id'] = $request_id;
			Response::$responseList[$request_id] = $data;
		}
		$this->invokeAsyncTcp($params,true);
	}

	/**
	 * 异步请求TCP服务 最后一个参数是函数的，将做异步回调
	 */
	public function invokeAsync($params)
	{
		//$params  = func_get_args();
		if($params){
			$index=count($params)-1;
			$callback = $params[$index];
			if($index == 0 || (!is_callable($callback))){
			    $callback = null;
			}else{
				unset($params[$index]);
			}
		    $this->invokeAsyncTcp($params, false ,$callback);
		}
	}

	/**
	 * 同步请求TCP服务
	 * @param $params
	 *
	 * @return bool|null
	 * @throws \AtServer\Exception\ClientException
	 */
	public function invokeTcp($params)
	{
		//$params=func_get_args();
		$host = $this->config['TCPserver']['server.host'];
		$port = $this->config['TCPserver']['server.port'];
		if(isset(self::$client[md5('sync',$host.$port)])){
		    $client = self::$client[md5('sync',$host.$port)];
		    if(!$client ->isConnected()){
				$this->connect($client,$host,$port);
		    }
		}else{
			$client = new \swoole_client(SWOOLE_SOCK_TCP | SWOOLE_KEEP);
			$config = [
				'open_length_check'     => true,
				'package_length_type'   => 'N',
				'package_max_length'    => 1024*1024*4,//10M
				'package_body_offset'   => 4,
				'package_length_offset' => 0,
			];
			$client->set( $config );
			if (!$this->connect($client,$host, $port)) {

			}
			self::$client[md5('sync',$host.$port)] = $client;
		}
		$clientParam             = ClientParams::instance();
		if(!isset($params[0])){
			return false;
		}
		$clientParam->method     = $params[0];
		$clientParam->callParams = $params;
		$clientParam->isResponse = true;
		$sendData           = Pack::sendEncode( Pack::encode( serialize($clientParam) ) );

		//向服务器发送数据
		if (!$client->send($sendData)) {
			unset(self::$client[md5('sync',$host.$port)]);
			Log::warning('发送数失败');
			return $this->invokeTcp($params);
		}
		//从服务器接收数据
		$res = $client->recv();
		if(!$res){
			unset(self::$client[md5('sync',$host.$port)]);
			Log::warning('接收数据失败');
			//throw new ClientException('接收数据错误',$client->errCode);
			return $this->invokeTcp($params);
		}
		$data = unserialize( Pack::decode( Pack::decodeData( $res ) ) );
		if($data instanceof  ClientParams){
			if ( ! is_null( $data->exception_message ) ) {
				throw new ClientException( $data->exception_message, $data->exception_code );
			}
			unset( $res );
			return $data->result;
		}
	}

	/**
	 * @param \swoole_client $client
	 * @param                $host
	 * @param                $port
	 *
	 * @return bool
	 * @throws \AtServer\Exception\ClientException
	 */
	public function connect(\swoole_client $client , $host, $port )
	{
		if ( ! $client->connect( $host, $port ) ) {
			throw new ClientException('链接服务器错误',$client->errCode);
		}
		return true;
	}
}