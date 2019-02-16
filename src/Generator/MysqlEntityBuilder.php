<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/2/13
 * Time: 22:44
 */
namespace AtServer\Generator;

use AtServer\DB\BaseM;
use AtServer\DocParse\ClassDocInfo;
use Noodlehaus\Config;

class MysqlEntityBuilder {
	/**
	 * @param string $dbName
	 * @param string $tableName
	 * @param string $savePath
	 * @param string $prefix
	 * @param string $postfix
	 */
	public static function buildingEntityClass( $dbName, $tableName, $savePath = '', $prefix = '', $postfix = '' ) {
		if ( $tableName ) {
			if ( $file = self::createFile( $dbName, $tableName, $savePath, $prefix, $postfix ) ) {
				echo '已完成: ' . $file . PHP_EOL;
			}
		} else {
			$m = new BaseM();
			$dbName && $m->setDBName( $dbName );
			$tables = $m->getTables();
			if ( $tables ) {
				$n = 0;
				foreach ( $tables as $table ) {
					if ( $file = self::createFile( $dbName, $table, $savePath, $prefix, $postfix ) ) {
						$n ++;
						echo '已完成: ' . $file . PHP_EOL;
					}
				}
				echo '生成: ' . $n . '个实体类 ' . PHP_EOL;
			} else {
				echo '数据库 ' . $dbName . ' 下没有数据表' . PHP_EOL;
			}
		}
	}

	/**
	 * 创建文件
	 * @param string $dbName
	 * @param string $tableName
	 * @param string $savePath
	 * @param string $prefix
	 * @param string $postfix
	 *
	 * @return bool|string
	 */
	public static function createFile($dbName,$tableName,$savePath='',$prefix='',$postfix=''){
		$m = new BaseM();
		$m->ignoreTablePrefix();
		$postfix || $postfix = 'Entity';
		$dbName && $m->setDBName( $dbName );
		$tableName && $m->setTable( $tableName );

		$field = $m->PDO->getFields();
		if($field){
			$nameSpace = 'Library\\Entity\\'.self::getClassName($dbName);
			$className = $prefix.self::getClassName( $tableName ).$postfix;
			$nameSpaceClass='\\'.$nameSpace.'\\'.$className;
			$_persist_property = '';
			//保留原来实体的 _开头的属性
			if(class_exists($nameSpaceClass)){
				$_persist_property = self::get_persist_property( $nameSpaceClass);
			}
			$str="<?php
namespace {$nameSpace};

use AtServer\\DB\\MysqlEntity;
use Noodlehaus\\Config;
class {$className} extends MysqlEntity {
	public function __construct( \$id = null ) {
		\$this->_tableName = '{$tableName}';
		";
			if(isset(Config::load(getConfigPath())['mysql']['dbs'])){
				$str .= "\$this->_dbName = '{$dbName}';";
			}else{
				$str .= "\$this->_dbName =Config::load( getConfigPath() )['mysql']['db_name'];";
			}

			$str .="parent::__construct( \$id );
	}
			";
			foreach ( $field as $key => $value ) {
				$type = self::getValType( $value);
				@$str .= "
	/**
	 * {$value['Comment']}
	 * @Type {$value['Type']}
	 * @var {$type['type']}  
	 */
	public \${$key}{$type['default']};
";
			}
			$str				.= "\n    {$_persist_property}
}";
			$savePath || $savePath = MANAGE_PATH . '/Entity/'.self::getClassName($dbName);
			if($_persist_property) echo $str;
			create( $savePath );
			$fileName = $savePath . '/' . $className . '.php';
			if ( file_put_contents( $fileName, $str ) ) {
				return $fileName;
			}
			return false;
		}
	}


	/**
	 * 获取要保留的属性
	 * @param $class
	 *
	 * @return string
	 */
	public static function get_persist_property($class){
		$persistProperty = ClassDocInfo::getPropertiesDocStr( $class );
		$resStr = '';
		$defaultValue = get_class_vars( $class);
		foreach ( $persistProperty as $key => $value ) {
			$default=$defaultValue[$key];
			$default=is_null($default)?'':' = \''. $default.'\'';
			if($key{0}=='_'){
				$resStr .= $value.PHP_EOL;
				$resStr.="    public \${$key}{$default};\n";
			}
		}

		return $resStr;
	}

	/**
	 * @param $fieldData
	 * @return array
	 */
	public static function getValType($fieldData) {
		$type=$fieldData['Type'];
		$default=$fieldData['Null']=='NO'?$fieldData['Default']:'';

		if(strpos($type,'int')!==false) {
			if($fieldData['Extra']=='auto_increment'){
				$default = 0;
			}

			$needType = 'int';
		} else if(strpos($type,'float')!==false || strpos($type,'decimal')!==false) {
			$needType = 'float';
		} else {
			$default = "'$default'";
			$needType = 'string';
		}

		return [
			'default' => strlen( $default ) > 0 ? ' = ' . $default : '',
			'type' => $needType,
		];
	}


	public static function getClassName($tableName){
		if($tableName){
			$str1 = preg_replace('/\_+/', ' ', $tableName);
			$str2 = ucwords($str1);

			return preg_replace('/\s+/', '', $str2);
		}
	}
}