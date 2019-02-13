<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2016/7/22 0022
 * Time: 12:28
 */

namespace AtServer\Log;



class Log {
	/**
	 * 日志信息
	 * @param $msg
	 * @param string $level
	 */
	public static function log($msg, $level = SEASLOG_INFO) {
		if (!is_string($msg)) {
			$msg = print_r($msg, true);
		}
		$msg = PHP_EOL . PHP_EOL . $msg;

		switch ($level) {
			case SEASLOG_INFO:
				\SeasLog::info($msg);
				break;
			case SEASLOG_DEBUG:
				\SeasLog::debug($msg);
				break;
			case SEASLOG_ERROR:
				\SeasLog::error($msg);
				break;
			case SEASLOG_WARNING:
				\SeasLog::warning($msg);
				break;
			default:
				\SeasLog::info($msg);
				break;
		}
		unset($msg);
	}

	/**
	 * 错误日志
	 * @param $msg
	 */
	public static function error($msg, $code = 0) {


		$err_content='[ content ] : ' . $msg . PHP_EOL . __FILE__ . PHP_EOL . self::getTrace();
		self::log($err_content, SEASLOG_ERROR);
		unset($traceContent, $traceArr);
	}

	/**
	 * 严重错误日志，并发异常通知
	 *
	 * @param     $msg
	 * @param int $code
	 */
	public static function grossError($msg,$code=0){
	    self::error($msg,$code);
	}

	public static function getTrace() {
		$traceArr = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
		$traceContent = '';
		foreach ($traceArr as $key => $arr) {
			$argsVal = '';
			if (!empty($arr['args'])) {
				$argsVal = implode(',', $arr['args']);
			}
			if (!empty($arr['type'])) {
				$clf = $arr['class'] . $arr['type'] . $arr['function'];
			} else {
				$clf = $arr['function'];
			}
			$clf .= '(' . $argsVal . ')';
			$file = '';
			if (!empty($arr['file'])) {
				$file = $arr['file'];
			}
			$line = '';
			if (!empty($arr['line'])) {
				$line = $arr['line'];
			}
			$traceContent .= "[$key] {$clf} {$file} {$line}\n";
		}

		return $traceContent;
	}


	/**
	 * 调试信息
	 * @param $msg
	 */
	public static function debug($msg)
	{
		self::log($msg, SEASLOG_DEBUG);
	}

	/**
	 * 警告信息
	 * @param $msg
	 */
	public static function warning($msg)
	{
		self::log($msg, SEASLOG_WARNING);
	}

	/**
	 * 日志路径设置
	 * @param string $basePath 基目录
	 * @param string $logger 存储目录
	 */
	public static function setPath($basePath, $logger = 'default/')
	{
		if (!is_dir($basePath)) create($basePath);
		\SeasLog::setBasePath($basePath);
		if (!is_dir($basePath . $logger)) {
			create($basePath . $logger);
		}
		\SeasLog::setLogger($logger);
	}
}