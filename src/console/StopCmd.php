<?php
/**
 * Created by PhpStorm.
 * User: htpc
 * Date: 2018/8/23
 * Time: 17:47
 */

namespace AtServer\console;


use AtServer\server\HttpServer;
use AtServer\server\SwooleServer;
use AtServer\server\WebSocket;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class StopCmd extends Command
{
	/**
	 * StopCmd constructor.
	 *
	 * @param null $name
	 */
	public function __construct($name =null)
	{
		parent::__construct( $name );
	}
	public function configure()
	{
		$this->setName('stop');

		$this->addArgument('serverName', InputArgument::REQUIRED , 'Who do you want serverName?');
	}

	/**
	 * @param \Symfony\Component\Console\Input\InputInterface   $input
	 * @param \Symfony\Component\Console\Output\OutputInterface $output
	 *
	 * @return bool|int|null
	 * @throws \Exception
	 */
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
				$server = new SwooleServer();
				$server->serverName = SwooleServer::SWOOLE_SERVER_TPC;
				$server->stop( $oi);
				break;
			case SwooleServer::SWOOLE_SERVER_HTTP:
				$server = new HttpServer();
				$server->serverName = SwooleServer::SWOOLE_SERVER_HTTP;
				$server->stop($oi);
				break;
			case SwooleServer::SWOOLE_SERVER_WS:
				$server = new WebSocket();
				$server->serverName = SwooleServer::SWOOLE_SERVER_WS;
				$server->stop($oi);
				break;
			default:
				throw new \Exception('服务错误');
		}
	}
}