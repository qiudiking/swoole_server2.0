<?php
/**
 * Created by PhpStorm.
 * User: htpc
 * Date: 2018/8/25
 * Time: 14:26
 */

namespace AtServer\console;


use Noodlehaus\Config;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class CreateMysqlCmd extends Command {
	public function __construct( ?string $name = null ) {
		parent::__construct( $name );
	}

	public function configure() {
		$this->setName('mysql');
		$this->addArgument('mysqlName', InputArgument::REQUIRED , 'Who do you want mysqlName?');
		$this->addArgument('mysqlDB', InputArgument::OPTIONAL , 'Who do you want mysqlDB?');
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
		$oi = new SymfonyStyle($input,$output);
		if($extension !== true){
			$oi->error($extension);
			return false;
		}
		$server = $input->getArgument('mysqlName');
		$mysqlDB = $input->getArgument('mysqlDB');
		if(!$mysqlDB){
			throw  new \Exception('请输入BD');
		}
		switch ($server)
		{
			case 'factory':
				print_r("factory\n");
				\AtServer\Generator\MysqlFactoryBuilder::buildingFactoryClass( $mysqlDB, '', '' );
				break;
			case 'entity':
				\AtServer\Generator\MysqlEntityBuilder::buildingEntityClass( $mysqlDB, '', '', '', '' );
				print_r("entity\n");
				break;
			default:
				$oi->error('错误');
		}
	}
}