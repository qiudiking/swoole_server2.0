<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/5/8
 * Time: 22:43
 */

namespace AtServer\Client;



class Pack {

	const signKey = '39055540915371ca9107a4e19b227d46';

	/**
	 * 包头格式
	 */
	const HEADER_PACK = "NA16A32";

	const HEADER_STRUCTURE = "Nlength/A16nonceStr/A32sign";

	const HEADER_LENGTH = 52;

	/**
	 * 协议打包
	 * @param $data
	 *
	 * @return string
	 */
	public static function encode( string $data ) {
		$nonce_str = getRandChar( 16 );
		$signVal   = self::getSignValue( $nonce_str, $data );
		$data = pack( self::HEADER_PACK, strlen( $data ), $nonce_str, $signVal ) . $data;
		return $data;
	}

	/**
	 *
	 * @param $randChar
	 * @param $data
	 *
	 * @return string
	 */
	public static function getSignValue( $randChar, $data ) {
		return md5( self::getSignKey() . $randChar . $data . $randChar );
	}

	public static function getSignKey(){

		 $sign=self::signKey;

		return $sign;
	}


	/**
	 * 协议解包
	 * @param $data
	 *
	 * @return bool|string
	 */
	public static function decode( $data ) {
		$headerData = substr( $data, 0, self::HEADER_LENGTH );
		$head       = unpack( self::HEADER_STRUCTURE, $headerData );
		$nonce_str  = $head['nonceStr'];
		$sign       = $head['sign'];
		$len        = $head['length'];
		$body       = substr( $data, self::HEADER_LENGTH, $len );
		$newSign    = self::getSignValue( $nonce_str, $body );
		unset( $headerData, $head, $data );
		if ( $sign != $newSign ) {
			echo '签名错误，非法数据', PHP_EOL;

			return false;
		}
		return  $body;
	}

	/**
	 * 发送包打包
	 * @param $data
	 *
	 * @return string
	 */
	public static function sendEncode($data){
		return pack( 'N', strlen( $data ) ) . $data;
	}

	/**
	 * 解原始包
	 * @param $data
	 *
	 * @return bool|string
	 *
	 */
	public static function decodeData($data){
	    $head=unpack('Nlen',substr($data,0,4));
		$len = (int) $head['len'];
	    $_data=substr($data,4,$len);
	    if(strlen($_data)!=$len){
		    \AtServer\Log\Log::error( '数据不完成' );
	    }
		return $_data;
	}
}