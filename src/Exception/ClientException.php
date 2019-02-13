<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 16/8/4
 * Time: 下午11:39
 */

namespace AtServer\Exception;

/**
 * Class SOAClientException
 *
 * @package Exception
 */
class ClientException extends \Exception{
	public function __construct( $message, $code  ) {
		parent::__construct( $message, $code );
	}
}