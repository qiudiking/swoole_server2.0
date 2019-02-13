<?php
/**
 * Created by PhpStorm.
 * User: htpc
 * Date: 2018/8/7
 * Time: 16:20
 */

namespace AtServer\console;




use Noodlehaus\Config;
use AtServer\server\HttpServer;
use AtServer\server\SwooleServer;
use AtServer\server\WebSocket;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class StartCmd extends Command {
	protected  $config;

	/**
	 * StartCmd constructor.
	 *
	 * @param null $name
	 *
	 * @throws \Noodlehaus\Exception\EmptyDirectoryException
	 */
	public function __construct( $name = null )
	{
		parent::__construct( $name );
		//$this->config = new Config(getConfigPath());
	}

	public function configure()
	{
		$this->setName('start');
		$this->addArgument('serverName', InputArgument::REQUIRED , 'Who do you want serverName?');
	}

	public function execute( InputInterface $input, OutputInterface $output )
	{
		$extension = checkExtension();
		$oi = new SymfonyStyle($input,$output);
		if($extension !== true){
			$oi->error($extension);
			return false;
		}
		$server = $input->getArgument('serverName');

		switch ($server)
		{
			case SwooleServer::SWOOLE_SERVER_TPC:
				$swoole             = new SwooleServer();
				$swoole->serverName = SwooleServer::SWOOLE_SERVER_TPC;
				$swoole->start($oi);
            break;
			case SwooleServer::SWOOLE_SERVER_HTTP:
				$swoole             = new HttpServer();
				$swoole->serverName = SwooleServer::SWOOLE_SERVER_HTTP;
				$swoole->start($oi);
				break;
			case SwooleServer::SWOOLE_SERVER_WS:
				$swoole             = new WebSocket();
				$swoole->serverName = SwooleServer::SWOOLE_SERVER_WS;
				$swoole->start($oi);
				break;
			default:
				throw new \Exception('启动的服务错误');
		}
	}

}