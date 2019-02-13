<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/1/14 0014
 * Time: 12:16
 */

namespace AtServer\Verify;

use \AtServer\Exception\ErrorHandler;
use AtServer\Exception\VerifyException;


/**
 * 邮箱校验
 * Class EmailVerify
 *
 * @package Verify
 */
class EmailVerify implements Verify {
	/**
	 * @param \AtServer\Verify\VerifyRule $verifyRule
	 *
	 * @return bool|mixed
	 * @throws \AtServer\Exception\VerifyException
	 */
	public function doVerifyRule( VerifyRule $verifyRule ) {
		$verifyRule->chkDataType();
		$pattern = "/^([0-9A-Za-z\\-_\\.]+)@([0-9a-z]+\\.[a-z]{2,3}(\\.[a-z]{2})?)$/i";
		if ( ! preg_match( $pattern, $verifyRule->value ) ) {
			$verifyRule->error || $verifyRule->error= $verifyRule->getDes(). '格式无效' ;
			throw new VerifyException( ErrorHandler::VERIFY_EMAIL_INVALID, $verifyRule->error );
		}

		return true;
	}

}