<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2016/7/26 0026
 * Time: 10:53
 */

namespace AtServer\Exception;




class MemberException extends \Exception
{

	public function __construct(  $code  ,$message='') {
		$message || $message = ErrorHandler::getErrMsg( $code );
		//Log::error( 'code=' . $code . ';message=' . $message. $this->getTraceAsString());
		parent::__construct( $message , $code  );
	}

	private $redirect_url='';

	/**
	 * @return string
	 */
	public function getRedirectUrl()
	{
		return $this->redirect_url;
	}

	/**
	 * @param string $redirect_url
	 */
	public function setRedirectUrl( $redirect_url )
	{
		$this->redirect_url = $redirect_url;
	}


}