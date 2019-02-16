<?php
/**
 * Created by PhpStorm.
 * User: htpc
 * Date: 2018/8/25
 * Time: 14:26
 */

namespace AtServer\console;


use AtServer\Log\Log;
use Noodlehaus\Config;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class CrontabCmd extends Command {
	public function __construct( ?string $name = null ) {
		parent::__construct( $name );
	}

	public function configure() {
		$this->setName('crontab');
		$this->addArgument('action', InputArgument::REQUIRED , 'Who do you want action?');
	}

	/**
	 * @param \Symfony\Component\Console\Input\InputInterface   $input
	 * @param \Symfony\Component\Console\Output\OutputInterface $output
	 *
	 * @return bool|int|null
	 * @throws \Exception
	 */
	public function execute( InputInterface $input, OutputInterface $output ) {
		$extension = checkExtension();
		$logPath = Config::load(getConfigPath())['log']['path'];
		Log::setPath($logPath);
		$oi = new SymfonyStyle($input,$output);
		if($extension !== true){
			$oi->error($extension);
			return false;
		}
		$action = $input->getArgument('action');
		if(!$action){
			throw  new \Exception('请输入BD');
		}
		if(defined('CRONTAB') ===false){
		    throw new \Exception('为设置定时常量');
		};
		if(class_exists(CRONTAB)){
			$class = CRONTAB;
		    $instance = new $class;
		    if(method_exists( $instance ,$action )){
				$instance->$action();
		    }else{
			    throw new \Exception('定时类中没有此方法action');
		    }
		}else{
			throw new \Exception('定时类不存在');
		}
	}
}