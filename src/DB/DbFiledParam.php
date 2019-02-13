<?php
/**
 * Created by PhpStorm.
 * User: yons
 * Date: 2018/7/17
 * Time: 20:19
 */

namespace AtServer\DB;


use AtServer\Exception\ThrowException;

class DbFiledParam {
	/**
	 * 字段名
	 *
	 * @var string
	 */
	public $filed = '';
	/**
	 * 新字段名，修改时用
	 * @var string
	 */
	public $new_filed = '';
	/**
	 * 字段长度
	 *
	 * @var int
	 */
	public $length = 0;
	/**
	 * 小数点位数
	 *
	 * @var int
	 */
	public $point = 2;
	/**
	 * 类型
	 *
	 * @var string
	 */
	public $type = '';
	/**
	 * 默认值得
	 *
	 * @var string
	 */
	public $default = '';
	/**
	 * 是否允许空
	 *
	 * @var bool
	 */
	public $is_null = true;
	/**
	 * 备注
	 *
	 * @var string
	 */
	public $comment = '';
	/**
	 * 字符集
	 *
	 * @var string
	 */
	public $charset = 'utf8';

	const type_int = 'int';

	const type_varchar = 'varchar';

	const type_tinyint = 'tinyint';
	const type_bigint = 'bigint';
	const type_float = 'float';
	const type_double = 'double';
	const type_decimal = 'decimal';
	const type_char = 'char';
	const type_text = 'text';
	const type_date = 'date';
	const type_datetime = 'datetime';

	private static $instance;

	/**
	 * @return \AtServer\DbFiledParam
	 */
	public static function instance(){
	    if(!self::$instance){
		    self::$instance = new self();
	    }
	    self::$instance->init();

		return self::$instance;
	}

	private function __construct() {
	}
	public function init(){
		$this->filed = '';
		$this->type = '';
		$this->point=2;
		$this->length=0;
		$this->is_null=true;
		$this->default = '';
		$this->comment = '';
		$this->charset = 'utf8';
	}

	/**
	 * 检测字段安全
	 * @throws \AtServer\DBException
	 *
	 */
	public function chkParam() {
		if(!$this->filed || !is_string($this->filed)){
			ThrowException::DBException( 5001200, '字段名不为能空或无效' );
		}
		if(!$this->type || !is_string($this->type)){
			ThrowException::DBException( 5001201, '字段类型不为能空或无效' );
		}

		if($this->type != self::type_text && ($this->length ==0|| !is_int($this->length))){
			ThrowException::DBException( 5001202, '字段长度不为能空或无效' );
		}

		if(!$this->charset|| !is_string($this->charset)){
			ThrowException::DBException( 5001201, '字段字符集不为能空或无效' );
		}
	}

}