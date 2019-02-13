<?php
/**
 * Created by PhpStorm.
 * User: user
 * Date: 2019/1/26
 * Time: 15:56
 */

namespace AtServer\Token;


use AtServer\Exception\ErrorHandler;
use AtServer\Exception\ThrowException;

class Token
{
	public function verifyToken()
	{
		$skey = getCookie('skey');
		$skey || ThrowException::SignException(ErrorHandler::TOKEN_NOD);

	}
}