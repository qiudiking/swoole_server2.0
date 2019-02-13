<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 16/8/1
 * Time: 下午9:46
 */

namespace AtServer\Sign;


use AtServer\Exception\ErrorHandler;
use AtServer\Exception\SignException;
use AtServer\Exception\ThrowException;
use AtServer\Log\Log;


class Sign
{
    const debug=false;
    static $sign_ke = '_sign';

	/**
	 * 签名密钥
	 *
	 * @var string
	 */
	private static $secret_key = 'xsdsa9089sdfwe89u42342343213ds';

	/**
	 * url 参数签名认证
	 * 必须参数:
	 * _sign_nonce_str随机字符串
	 * _sign_time 时间戳(秒)
	 * _sign 签名值
	 *
	 * @param null $param
	 *
	 * @throws SignException
	 * @return bool
	 */
	public static function Sign( $param = null )
	{
		$param || $param = $_GET;

		$old_sign = request_get( self::$sign_ke );

		unset($param[self::$sign_ke]);
		if ( empty($old_sign) ) {
			ThrowException::SignException( ErrorHandler::SIGN_INVALID );
		}
		if ( !request_get( '_sign_time' ) ) {
			ThrowException::SignException( ErrorHandler::SIGN_INVALID_SIGN_TIME );
		}
		if ( !request_get( '_sign_nonce_str' ) ) {
			ThrowException::SignException( ErrorHandler::SIGN_INVALID_SIGN_NONCE_STR );
		}

		$param_str = '';
		foreach ( $param as $key => $value ) {
            $value = trim( $value );
			if ( strlen($value)>0 ) {
				$param_str .= empty($param_str) ? '' : '&';
				$param_str .= $key . '=' . ($value);
			}
		}
		$_p_s = $param_str . self::$secret_key;

		// Log::debug( '验证签名 原参数：'.$_p_s . PHP_EOL);
        $base64 = base64_encode( $_p_s );
        //Log::debug( '验证签名 BASE64: ' . $base64 );
        $sign = sha1(  $base64);
        //Log::debug( '验证新密钥：' . $sign . ' ; 旧密钥：' . $old_sign );
		if ( $old_sign != $sign ) {
            //Log::debug( '签名错误' );
			ThrowException::SignException( ErrorHandler::SIGN_ERROR );
		}

		return $old_sign == $sign;
	}

    public static function debug( $data )
    {
        //self::debug && Log::debug( $data );
    }
	/**
	 * 对url 加上签名
	 * @param  string   $url 引用url
	 * @param array $param uri相关参数数组
	 */
	public static function MakeSign( &$url , $param = [] )
	{
		$param_str                = '';
		$param['_sign_time']      = time();
		$param['_sign_nonce_str'] = sha1( uniqid() . rand( 1 , 1000 ) );
        if(isset($param[self::$sign_ke])){
            unset($param[self::$sign_ke]);
        }
        self::debug( 'MakeSign()' );
        self::debug($param );
        $url_param = self::getUrlParamToArr( $url );

        if($param){
            foreach ( $param as $key=>$item ) {
                $url_param[$key]=$item;
            }
        }
		if(isset($url_param['_sign'])){
			unset( $url_param['_sign'] );
		}
		if ( !empty($url_param) ) {
            foreach ( $url_param as $key => $value ) {
                $value = trim( $value );
                if ( strlen($value )>0 ) {
                    $param_str .= empty($param_str) ? '' : '&';
                    $param_str .= $key . '=' . urldecode($value);
                }
            }
		}

        if( $index=strpos( $url , '?' )!==false){
            $requestPath = substr( $url ,0, strpos( $url , '?' )  );
        }else{
            $requestPath =$url;
        }
		$requestPath = str_replace( ' ', '', $requestPath );
		$s_encrypt   = $param_str . self::$secret_key;
        self::debug( '生成：' . $s_encrypt );
        $base64=base64_encode($s_encrypt );
        self::debug( '生成Base64：' . $base64 );

		$url_param['_sign']=sha1($base64);

		$url = $requestPath.'?'.ArrToGetParam($url_param);

    }

    public static function getUrlParamToArr( $url )
    {
        if(!is_string($url))return false;
        $parseArr = parse_url( $url );
        $request_string = getArrVal( 'query' , $parseArr );

        if($request_string){
            $resultArr = [];
            $tmpArr = explode( '&' , $request_string );
            if($tmpArr){
                foreach ( $tmpArr as $item ) {
                    if($item){
                        list($key , $value) = explode('=',$item);
                        $resultArr[$key] = urldecode($value);
                    }
                }
            }

            return $resultArr;
        }
        return false;
    }


}