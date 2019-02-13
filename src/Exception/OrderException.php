<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2016/7/29 0029
 * Time: 15:12
 */

namespace AtServer\Exception;


/**
 * 订单处理异常
 * Class OrderException
 *
 * @package Exception
 */
class OrderException extends \Exception {
	public function __construct( $message = "", $code = 0, \Throwable $previous = null ) {

		$message || ErrorHandler::getErrMsg( $code );

		parent::__construct( $message, $code, $previous );
	}

}