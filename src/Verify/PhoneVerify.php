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
 * 手机号码校验
 * Class PhoneVerify
 *
 * @package Verify
 */
class PhoneVerify implements Verify {
	/**
	 * @param \AtServer\Verify\VerifyRule $verifyRule
	 *
	 * @return bool|mixed
	 * @throws \AtServer\Exception\VerifyException
	 */
	public function doVerifyRule( VerifyRule $verifyRule ) {
		$verifyRule->chkDataType();
		if (preg_match( '/^1\d{10}$/', $verifyRule->value) == 0) {
			$verifyRule->error || $verifyRule->error = $verifyRule->getDes() . '必须是手机号码';
			throw new VerifyException(ErrorHandler::VERIFY_EMAIL_INVALID, $verifyRule->error);
		}

		return true;
	}

}