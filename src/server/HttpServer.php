<?php
/**
 * Created by PhpStorm.
 * User: htpc
 * Date: 2018/8/11
 * Time: 10:58
 */

namespace AtServer\server;




use AtServer\Client\sendMessage;
use AtServer\Client\WsMessageBase;
use AtServer\CoroutineClient\CoroutineContent;
use AtServer\Http\Request;
use AtServer\Log\Log;
use AtServer\Client\Result;
use Noodlehaus\Config;
use Swoole\Coroutine;
use Symfony\Component\Console\Style\SymfonyStyle;

class HttpServer extends SwooleServer {
	/**
	 *php代码执行过程中发生错误
	 */
	public  function handleFatal(){
		$error = handleFatal();
		if(isset($_SERVER['CID'])){
			$response = self::$response[$_SERVER['CID']];
		}
		if(isset($response) && $response instanceof \swoole_http_response){
			Log::log($error);
			unset(self::$response[$_SERVER['CID']]);
			$response->status( 500 );
			$html = '';
			if(DEBUG){
				$json = Result::Instance();
				$json->setCodeMsg( 'server error', 500 );
				$html = $json->getJson();
			}
			$response->end( $html );
		}
	}

	/**
	 * @var \Yaf\Application
	 */
	public $app;

	public static $response = [];

	/**
	 * @var \swoole_http_server
	 */
	public static $serverInstance;
	/**
	 * @var
	 */
	public static $instance;

	public static function getInstance()
	{
		return self::$instance;
	}

	/**
	 * 保存response
	 * @param $response
	 */
	public function saveResponse( \swoole_http_response $response)
	{
		$cid = getRequestId();
		self::$response[$cid] = $response;
		$_SERVER['CID'] = $cid;
	}

	/**
	 * HTTP请求
	 * @param \swoole_http_request  $request
	 * @param \swoole_http_response $response
	 *
	 * @return bool
	 */
	public function onRequest(\Swoole\Http\Request $request ,\swoole_http_response $response)
	{
		Request::init();
		$_SERVER             = isset( $request->server ) ? $request->server : array();
		$header              = isset( $request->header ) ? $request->header : array();
		foreach ( $_SERVER as $key => $value ) {
			unset( $_SERVER[ $key ] );
			$_SERVER[ strtoupper( $key ) ] = $value;
		}
		foreach ( $header as $key => $value ) {
			unset( $_SERVER[ $key ] );
			$_SERVER[ strtoupper( $key ) ] = $value;
		}

		$_GET         = isset( $request->get ) ? $request->get : array();
		$_POST        = isset( $request->post ) ? $request->post : array();
		$_COOKIE      = isset( $request->cookie ) ? $request->cookie : array();
		$_FILES       = isset( $request->files ) ? $request->files : array();
		//清理环境
		//将请求的一些环境参数放入全局变量桶中
		$_SESSION     = array();

		$_SERVER['SWOOLE_WORKER_ID'] = $this->server->worker_id;
		if ( ! isset( $_SERVER['HTTP_HOST'] ) ) {
			$arr                  = explode( ':', $_SERVER['HOST'] );
			$_SERVER['HTTP_HOST'] = getArrVal( 0, $arr );
		}
		isset( $_SERVER['HTTP_REQUEST_ID'] ) || $_SERVER['HTTP_REQUEST_ID'] = getRandChar( 28 );
		CoroutineContent::put('response',$response);
		CoroutineContent::put('request',$request);
		$this->saveResponse($response);
		if($_SERVER['REQUEST_URI'] == '/favicon.ico'){
			$response->end('');
			return true;
		}
		ob_start();
		$response->header( 'Access-Control-Allow-Origin', '*' );
		$response->header( 'Access-Control-Allow-Credentials', 'true' );
		$response->header( 'Content-Type', 'text/html; charset=utf-8' );
		$result_i = Result::Instance();
		try {
			\Yaf\Dispatcher::getInstance()->autoRender(Config::load(getConfigPath())->get('common.autoRender',false));//自动渲染View
			\Yaf\Dispatcher::getInstance();
			$GLOBALS['HTTP_RAW_POST_DATA'] = $request->rawContent();
			$requestObj                    = new \Yaf\Request\Http( $_SERVER['REQUEST_URI'] );
			$this->app->bootstrap();
			$this->app->getDispatcher()->dispatch( $requestObj );
		} catch ( \AtServer\Exception\ActionSuccessException $actionErrorException ) {
			//成功处理控制器
			//echo 'success';
		} catch ( \AtServer\Exception\RedirectException $redirectException ) {
			//301控制器
			Log::warning('301重定向跳转');
			$result_i->set('url',$redirectException->getRedirect_url());
			$result_i->setCodeMsg($redirectException->getMessage(),$redirectException->getCode());
			echo $result_i;
			if(!isAjaxRequest()){
				CoroutineContent::put('IS_RESPONSE',1);
			}
			$this->httpSendMessage();
		} catch ( \Exception $e ) {
			Log::error('code=' . $e->getCode() . ' : ' . $e->getMessage() . $e->getTraceAsString() );
			$result_i->setCodeMsg($e->getMessage(),$e->getCode());
			if(!isAjaxRequest()){
				$response->status(500);
				DEBUG || $result_i = '';
			}
			echo $result_i;
		}

		$result = ob_get_contents();
		ob_end_clean();

		if( !CoroutineContent::get('IS_RESPONSE') ){
			$response->end($result);
			$this->httpSendMessage();
		}
		CoroutineContent::delete();
	}

	/**
	 * http 发送webSocket消息
	 */
	public function httpSendMessage()
	{
		if($this->server instanceof \swoole_websocket_server){
			$WsMessageObj = sendMessage::sendMessage(getRequestId(),$this->server);
			if($WsMessageObj instanceof WsMessageBase){
				$this->sendMessage($this->server, $WsMessageObj->result);
			}
		}
	}




	/**
	 * 异步任务
	 * @param $server
	 * @param $task_id
	 * @param $worker_id
	 * @param $data
	 */
	public function onTask( $server, $task_id, $worker_id, $data)
	{
		$data = unserialize( $data );
		if(isset($data['action'])){
			list( $class, $method ) = explode( '::', $data['action'] );
			unset( $data['action'] );
			try{
				if($class && $method ){
					if(class_exists($class)){
						$instance = new $class;
						if(method_exists($instance,'init')){
							call_user_func(array($instance,'init'));
						}
						if(method_exists($instance,$method)){
							call_user_func_array( array( $instance, $method ), $data );
						}else{
							throw new \Exception('方法不存在::'.$class.'->'.$method,8772);
						}
						unset($instance);
					}else{
						throw new \Exception('类不存在::'.$class,8771);
					}
				}else{
					throw	new \Exception('class无效或方法无效',8770);
				}
			}catch(\Exception $e){
				\AtServer\Log\Log::error('code:;'.$e->getCode(). 'msg::'.$e->getMessage() );
			}
		}else{
			\AtServer\Log\Log::error('action不存在');
		}
	}

	/**
	 * @param \swoole_http_server $server
	 * @param                     $worker_id
	 */
	public function onWorkerStart( $server, $worker_id)
	{
		$this->app = new \Yaf\Application( APPLICATION_PATH . "/conf/application.ini" );
		$objSamplePlugin = new \SamplePlugin();
		$this->app->getDispatcher()->registerPlugin( $objSamplePlugin );
		if(!$server->taskworker){
			$process_name = $this->set_process_name('Worker');
		}else{
			$process_name = $this->set_process_name('Task');
		}
		cli_set_process_title($process_name);
		Log::log($this->serverName.'服务启动SUCCESS....');
	}


	public static function sendContent(\swoole_http_response $response ,$content)
	{
		$response->header('Keep-Alive', 300 );
		$response->end($content);
	}


	/**
	 * @param \Symfony\Component\Console\Style\SymfonyStyle $oi
	 *
	 * @throws \Exception
	 */
	public  function start(SymfonyStyle $oi)
	{
		$config = $this->config['ports'][$this->serverName];
		$set = $this->config['server'][$this->serverName];
		self::$instance = $this;
		if($this->get_process_info()){
			$oi->warning($this->serverName.'服务已启动;端口:'.$config['socket_port']);
			return ;
		}
		$logPath = $this->config['log']['path'];
		Log::setPath($logPath);
		$this->server = new  \swoole_http_server($config['socket_host'],$config['socket_port']);
		self::$serverInstance = $this->server;
		$oi->success($this->serverName.'服务启动成功;端口:'.$config['socket_port']);
		Log::log($this->serverName.'服务启动');
		$this->server->set($set);
		$this->server->on( 'connect', array( $this, 'onConnect' ) );
		$this->server->on( 'workerStart', array( $this, 'onWorkerStart' ) );
		$this->server->on( 'Shutdown', array( $this, 'onShutdown' ) );
		$this->server->on( 'workerStop', array( $this, 'onWorkerStop' ) );
		$this->server->on( 'start', array( $this, 'onStart' ) );
		$this->server->on( 'workerError', array( $this, 'onWorkerError' ) );
		$this->server->on( 'ManagerStart', array( $this, 'onManagerStart' ) );
		$this->server->on( 'task', array( $this, 'onTask' ) );
		$this->server->on( 'finish', array( $this, 'onFinish' ) );
		$this->server->on( 'close', array( $this, 'onClose' ) );
		$this->server->on( 'request', array( $this, 'onRequest' ) );
		$this->server->on( 'pipeMessage', array( $this, 'onPipeMessage' ) );
		$this->server->on('packet',array($this,'onPacket'));
		$this->server->on('bufferFull',array($this,'onBufferFull'));
		$this->server->on('bufferEmpty',array($this,'onBufferEmpty'));
		$this->server->on('workerExit',array($this,'onWorkerExit'));
		$this->server->on('managerStart',array($this,'onManagerStart'));
		$this->server->on('managerStop',array($this,'onManagerStop'));

		$this->server->start();
	}

}