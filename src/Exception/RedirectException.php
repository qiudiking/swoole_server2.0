<?php
/**
 * Created by PhpStorm.
 * User: htpc
 * Date: 2018/10/9
 * Time: 14:28
 */

namespace AtServer\Exception;


use Throwable;

class RedirectException extends \Exception {
	private $redirect_url='';
	public function __construct( string $message = "", int $code = 301, \Throwable $previous = null )
	{
		parent::__construct( $message, $code, $previous );
	}

	public function setRedirect_url($url)
	{
		$this->redirect_url = $url;
	}

	public function getRedirect_url()
	{
		return $this->redirect_url;
	}

}