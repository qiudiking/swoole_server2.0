<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 16/8/2
 * Time: 上午11:09
 */

namespace AtServer\Exception;

/**
 * 签名异常处理
 * Class SignException
 *
 * @package Exception
 */
class MongodbException extends \Exception{
	/**
	 * SignException constructor.
	 *
	 * @param string $code
	 * @param string $msg
	 */
	public function __construct($code,$msg='') {
		parent::__construct( $msg, $code );
	}
}