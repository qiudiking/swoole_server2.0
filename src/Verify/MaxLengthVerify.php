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
 * 最大长度校验类
 * Class MaxLengthVerify
 *
 * @package Verify
 */
class MaxLengthVerify implements Verify {
	/**
	 * @param \AtServer\Verify\VerifyRule $verifyRule
	 *
	 * @return bool|mixed
	 * @throws \AtServer\Exception\VerifyException
	 */
	public function doVerifyRule( VerifyRule $verifyRule ) {
		$verifyRule->chkDataType();

		if ( intval( $verifyRule->ruleValue) < mb_strlen( $verifyRule->value ) ) {
			$verifyRule->error || $verifyRule->error= $verifyRule->getDes(). '长度不能大于' . $verifyRule->ruleValue. '个字符';
			throw new VerifyException( ErrorHandler::VERIFY_MAX_LENGTH, $verifyRule->error );
		}

		return true;
	}

}