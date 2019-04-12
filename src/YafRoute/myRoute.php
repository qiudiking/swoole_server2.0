<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/6/21 0021
 * Time: 19:45
 */

namespace AtServer\YafRoute;



use Noodlehaus\Config;
use AtServer\Log;

class myRoute implements \Yaf\Route_Interface {

	public $controllerName = '';

	public $moduleName = '';

	public $actionName = '';

	public static $routerList=[];

	/**
	 * @param \Yaf\Request_Abstract $request
	 *
	 * @return bool
	 */
	public function route( $request ) {

		$this->controllerName = '';
		$this->moduleName     = '';
		$this->actionName     = '';

		$requestUri = parse_url( $_SERVER['REQUEST_URI'] )['path'];
		$requestUri = preg_replace( [
			'/\s+/',
			'/\/{2,}/',
		], [
			'',
			'/',
		], urldecode( $requestUri ) );
		$prep = Config::load(getConfigPath())->get('route',[]);
		foreach ( $prep as $parent => $replace ) {
			if ( preg_match( $parent, $requestUri ) ) {
				$requestUri = preg_replace( $parent, $replace, $requestUri );
				break;
			}
		}

		//Log::log( '=========== 路由' . $requestUri );
		$firstStr = substr( $requestUri, 0, 1 );
		if ( $firstStr === '' ) {
			$uri = '/';
		} else if ( $firstStr !== '/' ) {
			$uri = '/' . $requestUri;
		} else {
			$uri = $requestUri;
		}
		if ( substr( $uri, - 1 ) !== '/' ) {
			$uri .= '/';
		}
		$uriArr               = explode( '/', $uri );
		$this->moduleName     = getArrVal( 1, $uriArr, '' );
		$this->controllerName = getArrVal( 2, $uriArr, '' );
		$this->controllerName = ucfirst( strtolower( $this->controllerName ) );
		$this->actionName     = getArrVal( 3, $uriArr, '' );

		$request->setModuleName( $this->moduleName );
		$request->setControllerName( $this->controllerName );
		$request->setActionName( $this->actionName );
		$request->setRequestUri( $requestUri );

		return true;
	}

	/**
	 * @param array      $info
	 * @param array|null $query
	 *
	 * @return bool
	 */
	public function assemble( array $info, array $query = null ) {
		return true;
	}


	private static $instance;

	public static function instance() {
		if ( ! self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}
}