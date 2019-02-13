<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/3/1 0001
 * Time: 10:04
 */

namespace AtServer\Verify;

use AtServer\DocParse\ClassDocInfo;

use \AtServer\Exception\ErrorHandler;
use AtServer\Exception\VerifyException;
use AtServer\Sign;
use AtServer\Verify\VerifyRule;


class BaseBootstrap {
	public static $verifyData = [];
	public static $actionDescription = '';

	/**
	 * *
	 * 控制器校验
	 * 在yaf路由解析之后
	 * @param \Yaf\Request_Abstract $request
	 *
	 * @throws \Exception
	 */
	public static function controllerVerify(\Yaf\Request_Abstract $request){
		$dispatcher=\Yaf\Application::app()->getDispatcher();
		$Request = $dispatcher->getRequest();
		$controllerName=$Request->getControllerName();
		$actionName=$Request->getActionName();
		$controllerClass = $controllerName . 'Controller';
		$actionName      = $actionName . 'Action';
		if ( class_exists( $controllerClass ) ) {
			$methodsDocInfo = getArrVal( $controllerClass, self::$verifyData );
			if ( ! $methodsDocInfo ) {
				$methodsDocInfo                       = array_change_key_case( ClassDocInfo::getMethodsInfo( $controllerClass ));
				self::$verifyData[ $controllerClass ] = $methodsDocInfo;
			}
			$verifyMethodDocInfo     = getArrVal( strtolower($actionName), $methodsDocInfo );
			$method = strtoupper(getArrVal('method',$verifyMethodDocInfo));
			if($method && $method != getArrVal('REQUEST_METHOD',$_SERVER)){
				throw new \Exception('请求方式错误',30331);
			}
			
			self::$actionDescription = getArrVal( 'description', $verifyMethodDocInfo );
			self::$actionDescription || self::$actionDescription = getArrVal( 'long_description', $verifyMethodDocInfo );


			$verifyList = getArrVal( 'verify', $verifyMethodDocInfo );
			if ( is_array( $verifyList ) ) {
				foreach ( $verifyList as $verifyStrValue ) {
					self::doVerify( $verifyStrValue );
				}
			} else {
				self::doVerify( $verifyList );
			}
		}
	}


	/**
	 * @param $verifyStrValue
	 *
	 * @return bool
	 */
	public static function doVerify( $verifyStrValue ) {

		if(is_string($verifyStrValue)){
			$verifyRuleList = json_decode( $verifyStrValue ,true);
		}else if(is_array($verifyStrValue)){
			$verifyRuleList=$verifyStrValue;
		}else{
			return false;
		}
		$rules  = '';
		$fields = '';
		if ( isset( $verifyRuleList['rules'] ) ) {
			$rules = $verifyRuleList['rules'];
			unset( $verifyRuleList['rules'] );
		}
		if ( isset( $verifyRuleList['fields'] ) ) {
			$fields = $verifyRuleList['fields'];
			unset( $verifyRuleList['fields'] );
		}
		//固定校验类型优先，它不需要字段名

		$rule = getArrVal( 'rule', $verifyRuleList );

		switch ($rule){
			case VerifyRule::RULE_SIGN:
				self::requestVerify( $verifyRuleList );
				break;
			case VerifyRule::RULE_FORM_HASH:
				self::requestVerify( $verifyRuleList );
				break;
		}

		//一个字段,一个校验规则
		if ( isset( $verifyRuleList['rule'] ) && $verifyRuleList['rule'] && isset($verifyRuleList['field']) && $verifyRuleList['field']) {
			foreach ( $verifyRuleList as $valData ) {
				if ( is_array( $valData ) ) {
					self::requestVerify( $valData );
				} else {
					self::requestVerify( $verifyRuleList );
					break;
				}
			}
		}
		//有$rules，没有$fields
		if ( is_array( $rules ) && $rules && ! $fields ) {
			//一个字段，多个校验规则
			foreach ( $rules as $_rule ) {
				$_rule['field']       =  getArrVal('field',$verifyRuleList);
				$_rule['description'] = getArrVal('description',$verifyRuleList);
				self::requestVerify( $_rule );
			}
		} //一个校验规则，检验多个字段
		else if ( is_array( $fields ) && $fields && ! $rules ) {
			foreach ( $fields as $_filed ) {
				$verifyRuleList['field']       = $_filed['field'];
				$verifyRuleList['description'] = $_filed['description'];
				self::requestVerify( $verifyRuleList );
			}
		} //多个校验规则，检验多个字段
		elseif ( $rules && is_array( $rules ) && $fields && is_array( $fields ) ) {
			foreach ( $rules as $_rule ) {
				foreach ( $fields as $_filed ) {
					$_rule['field']       = $_filed['field'];
					$_rule['description'] = $_filed['description'];
					self::requestVerify( $_rule );
				}
			}
		}
		return false;

	}


	/**
	 * 校验
	 * @param $valData
	 *
	 * @return bool
	 * @throws \AtServer\Exception\VerifyException
	 */
	public static function requestVerify( $valData ) {
		$field = getArrVal( 'field', $valData );//字段
		if(is_array($field)){
			throw new VerifyException( ErrorHandler::VERIFY_RULE_INVALID ,'校验字段规则无效,不能为数组');
		}
		$fieldDes = getArrVal( 'description', $valData );//字段名描述与说明
		$errorMsg = getArrVal( 'error', $valData );//错误信息
		$fieldDes || $fieldDes = $field;
		$filedVal = request_get( $field );

		$verifyRule           = \AtServer\Verify\VerifyFactory::VerifyRule();
		$verifyRule->ruleType = getArrVal( 'rule', $valData );//规则

		if ( is_null( $filedVal )
		     && $verifyRule->ruleType != VerifyRule::RULE_REQUIRED
		     && $verifyRule->ruleType != VerifyRule::RULE_SIGN
		     && $verifyRule->ruleType != VerifyRule::RULE_FORM_HASH
		) {
			return false;
		}
		$verifyRule->field       = $field;
		$verifyRule->description = $fieldDes;
		$verifyRule->value       = $filedVal;
		$verifyRule->error       = $errorMsg;

		$verifyRule->ruleValue = getArrVal( 'val', $valData );

		$verifyRule->verify();
		unset( $verifyRule );
	}
}