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
 * 正则校验
 * Class PatternVerify
 *
 * @package Verify
 */
class PatternVerify implements Verify {
	/**
	 * @param \AtServer\Verify\VerifyRule $verifyRule
	 *
	 * @return mixed|void
	 * @throws \AtServer\Exception\VerifyException
	 */
	public function doVerifyRule( VerifyRule $verifyRule ) {
		$verifyRule->chkDataType();
		if ( $verifyRule->ruleValue && ! preg_match( $verifyRule->ruleValue, $verifyRule->value) ) {
			$verifyRule->error || $verifyRule->error = $verifyRule->getDes() . '匹配不通过';
			throw new VerifyException( ErrorHandler::VERIFY_PATTERN, $verifyRule->error );
		}
	}

}