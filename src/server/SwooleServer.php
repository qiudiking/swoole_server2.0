<?php
/**
 * Created by PhpStorm.
 * User: htpc
 * Date: 2018/8/10
 * Time: 14:53
 */

namespace AtServer\server;




use Noodlehaus\Config;
use AtServer\Client\ClientParams;
use AtServer\Client\Pack;
use AtServer\Log\Log;
use Symfony\Component\Console\Style\SymfonyStyle;
use Yaf\Exception;

class SwooleServer {

	const SWOOLE_SERVER_TPC = 'TCP';
	const SWOOLE_SERVER_HTTP = 'HTTP';
	const SWOOLE_SERVER_WS   = 'WS';

	public $serverName;

	public $config;

	public $user;

	public $portManager;

	public $server;

	public static $serverInstance;

	/**
	 * SwooleServer constructor.
	 */
	public function __construct()
	{
		date_default_timezone_set( 'Asia/Shanghai' );
		$this->setConfig();
		$this->user = $this->config->get('server.set.user', '');
		set_error_handler([$this, 'displayErrorHandler'], E_ALL | E_STRICT);
		set_exception_handler('displayExceptionHandler');
		register_shutdown_function( array($this,'handleFatal') );
	}

	/**
	 *php代码执行过程中发生错误
	 */
	public  function handleFatal(){
		$error = handleFatal();
		if(isset($_SERVER['FD'])){
			Log::error($error);
			$result = ClientParams::instance();
			$result->setExceptionMessage($this->serverName.'server error',500);
			$sendData = Pack::sendEncode(Pack::encode(serialize($result)));
			$this->server->send(getArrVal('FD',$_SERVER),$sendData);
		}
	}


	/**
	 * @throws \Noodlehaus\Exception\EmptyDirectoryException
	 */
	protected function setConfig()
	{
		$this->config = new Config(getConfigPath());
	}

	public function displayErrorHandler($errno, $errstr, $errfile, $errline)
	{
			Log::error($errno.$errstr. $errfile. $errline);
	}


	/**
	 * 设置进程名
	 * @param string $postfix
	 *
	 * @return string
	 */
	public function set_process_name($postfix = '')
	{
		$process_name = isset($this->config['server'][$this->serverName]['process_name'])?$this->config['server'][$this->serverName]['process_name']:$this->serverName;
		$port = '[port:'.$this->config['ports'][$this->serverName]['socket_port'].']';
		return  $process_name.$postfix.$port;
	}

	/**
	 * 获取进程信息
	 * @return array|bool
	 */
	public function get_process_info()
	{
		exec( 'ps -ef', $res );
		$processName = $this->set_process_name( 'Main' );
		foreach ( $res as $val ) {
			if ( $val && $processName && strpos( $val, $processName ) !== false ) {
				$arrInfo = explode( ' ',
					preg_replace( '/\s+/', ' ', trim( $val ) ) );
				$name    = end( $arrInfo );
				if ( is_array( $arrInfo ) ) {
					if ( $processName == $name ) {
						$pid = $arrInfo[1];
						return [ 'pid' => $pid, 'name' => $name ];
					}
				}
			}
		}
		return false;
	}




	public function onConnect( $server,  $fd,  $reactorId)
	{
		Log::log('链接成功');
	}

	public function onReceive(\swoole_server $server,  $fd,  $reactor_id,  $data){
		$_SERVER['FD'] = $fd;
		$data = unserialize(Pack::decode(Pack::decodeData($data)));
		list( $class, $method ) = explode( '::', $data->method );
		$result = ClientParams::instance();
		try{
			if($class && $method ){
				if(class_exists($class)){
				    $instance = new $class;
				    if(method_exists($instance,'init')){
					    call_user_func(array($instance,'init'));
				    }
				    if(method_exists($instance,$method)){
					    $result->result = call_user_func_array( array( $instance, $method ), $data->callParams );
				    }else{
				    	throw new \Exception('方法不存在::'.$class.'->'.$method,7772);
				    }
				    unset($instance);
				}else{
					throw new \Exception('类不存在::'.$class,7771);
				}
			}else{
				throw	new \Exception('class无效或方法无效',7770);
			}
		}catch(\Exception $e){
			$result->setExceptionMessage($e->getMessage(),$e->getCode());
		}
		$result->request_id = $data->request_id;
		$result->isResponse = $data->isResponse;
		$sendData = Pack::sendEncode(Pack::encode(serialize($result)));
		$server->send($fd,$sendData);
	}

	public function onClose( $server,  $fd,  $reactorId)
	{
		Log::log( '客户端连接关闭' );
	}

	public function onPacket( $server,  $data, array $client_info)
	{
		Log::log('接收到UDP数据');
	}

	public function onBufferFull( $server,  $fd)
	{
		Log::log( '缓存区达到最高水位' );
	}

	public function onBufferEmpty($server,  $fd)
	{
		Log::log( '缓存区低于最低水位线' );
	}

	public function onStart($server)
	{
		Log::log('启动在主进程的主线程');
		$process_name = $this->set_process_name('Main');
		cli_set_process_title($process_name);
	}
	public function onShutdown($server){
		Log::log('关闭服务==================>>>');
	}

	public function onWorkerStart($server,$worker_id)
	{

		if(!$server->taskworker){
			$res = 'Worker进程';
			$process_name = $this->set_process_name('Worker');
		}else{
			$res = 'Task进程';
			$process_name = $this->set_process_name('Task');
		}
		cli_set_process_title($process_name);
		Log::log($this->serverName.$res.'服务启动成功....');
	}

	public function onWorkerStop($server,  $worker_id)
	{
		Log::log('worker进程终止时发生');
	}

	public function onWorkerExit( $server,  $worker_id)
	{
		Log::log( 'Worker进程未退出' );
	}

	public function onWorkerError( $server,  $worker_id,  $worker_pid,  $exit_code,  $signal)
	{
		Log::log('当Worker/Task进程发生异常后会在Manager进程内回调此函数');
	}

	public function onTask( $server,  $task_id,  $src_worker_id,  $data)
	{
		
	}

	public function onFinish( $server,  $task_id,  $data)
	{
		Log::log( 'worker进程投递的任务在task_worker中完成' );
	}

	public function onManagerStart($server){
		$process_name = $this->set_process_name('Manager');
		cli_set_process_title($process_name);
	}

	public function onManagerStop($server){
		Log::log('当管理进程结束时调用它');
	}

	public function onPipeMessage( $server,  $src_worker_id,  $message)
	{
		Log::log('当工作进程收到由 sendMessage 发送的管道消息时会触发onPipeMessage事件');
	}

	/**
	 * 关闭服务
	 * @param \Symfony\Component\Console\Style\SymfonyStyle $oi
	 *
	 * @return bool
	 */
	public function stop(SymfonyStyle $oi)
	{
		$config = $this->config['ports'][$this->serverName];
		$processInfo = $this->get_process_info();
		if ( $processInfo ) {
			$name = $processInfo['name'];
			$pid  = $processInfo['pid'];
			if ( $pid ) {
				\swoole_process::kill( $pid );
				file_put_contents( APPLICATION_PATH.'/bin/pidfile/'.$this->serverName.$config['socket_port'].'.pid', $pid );
				$oi->success($this->serverName.'服务关闭成功 端口:'.$config['socket_port']);
			} else {
				$oi->error($this->serverName.'服务关闭失败 端口:'.$config['socket_port']);
			}
		} else {
			$oi->warning($this->serverName.'服务没有运行 端口:'.$config['socket_port']);
		}
	}

	/**
	 * 重启服务
	 * @param \Symfony\Component\Console\Style\SymfonyStyle $oi
	 */
	public function restart(SymfonyStyle $oi)
	{
		$this->stop($oi);
		sleep( 1 );
		$this->start($oi);
	}

	public function start( SymfonyStyle $oi)
	{
		$config = $this->config['ports'][$this->serverName];
		$set = $this->config['server'][$this->serverName];
		if($this->get_process_info()){
			$oi->warning($this->serverName.'服务已启动;端口:'.$config['socket_port']);
			return ;
		}
		$logPath = $this->config['log']['path'];
		Log::setPath($logPath);
		$this->server = new  \swoole_server($config['socket_host'],$config['socket_port'],SWOOLE_PROCESS,$config['socket_type']);
		self::$serverInstance = $this->server;
		$oi->success($this->serverName.'服务启动成功;端口:'.$config['socket_port']);
		Log::log($this->serverName.'服务启动');
		$this->server->set($set);
		$this->server->on('connect',array($this,'onConnect'));
		$this->server->on('receive',array($this,'onReceive'));
		$this->server->on('close',array($this,'onClose'));
		$this->server->on('packet',array($this,'onPacket'));
		$this->server->on('bufferFull',array($this,'onBufferFull'));
		$this->server->on('bufferEmpty',array($this,'onBufferEmpty'));
		$this->server->on('start',array($this,'onStart'));
		$this->server->on('shutdown',array($this,'onShutdown'));
		$this->server->on('workerStart',array($this,'onWorkerStart'));
		$this->server->on('workerStop',array($this,'onWorkerStop'));
		$this->server->on('workerExit',array($this,'onWorkerExit'));
		$this->server->on('workerError',array($this,'onWorkerError'));
		$this->server->on('task',array($this,'onTask'));
		$this->server->on('finish',array($this,'onFinish'));
		$this->server->on('managerStart',array($this,'onManagerStart'));
		$this->server->on('managerStop',array($this,'onManagerStop'));
		$this->server->on('pipeMessage',array($this,'onPipeMessage'));
		$this->server->start();
	}

}