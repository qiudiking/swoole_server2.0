<?php
namespace AtServer\Cache;
class YacCache {
	private $prefix = '';
	private static $instance;
	private $yac;
	public function __construct($prefix='') {
		$this->prefix = $prefix;
		$this->yac = new \Yac( $this->prefix );
	}


	/**
	 * @param string $prefix
	 *
	 * @return \AtServer\Cache\YacCache
	 */
	static function getYacInstance($prefix='') {
		if(is_null(self::$instance)){
			self::$instance = new self( $prefix);
		}

		return self::$instance;
	}

	public function set( $key, $value ) {
		return $this->yac->set( $key, $value );
	}

	public function get( $key ) {
		return $this->yac->get( $key );
	}
}