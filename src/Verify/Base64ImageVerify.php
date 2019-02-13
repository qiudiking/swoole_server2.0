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
 * base64图片校验
 * Class UrlVerify
 *
 * @package Verify
 */
class Base64ImageVerify implements Verify {
	/**
	 * @param \AtServer\Verify\VerifyRule $verifyRule
	 *
	 * @return bool|mixed
	 * @throws \AtServer\Exception\VerifyException
	 */
	public function doVerifyRule( VerifyRule $verifyRule ) {
		$verifyRule->chkDataType();
		if (preg_match( '/^(data\:image\/([A-Za-z]{3,4})\;base64\,)/', $verifyRule->value) == 0) {
			$verifyRule->error || $verifyRule->error = $verifyRule->getDes() . '不是base64图片';
			throw new VerifyException(ErrorHandler::VERIFY_EMAIL_INVALID, $verifyRule->error);
		}

		return true;
	}

}