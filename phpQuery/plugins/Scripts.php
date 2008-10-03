<?php
/**
 * phpQuery plugin class extending phpQuery object.
 * Methods from this class are callable on every phpQuery object.
 *
 * Class name prefix 'phpQueryObjectPlugin_' must be preserved.
 */
abstract class phpQueryObjectPlugin_Scripts {
	/**
	 * Limit binded methods.
	 *
	 * null means all public.
	 * array means only specified ones.
	 *
	 * @var array|null
	 */
	public static $phpQueryMethods = null;
	public static $config = array();
	/**
	 * Enter description here...
	 *
	 * @param phpQueryObject $self
	 */
	public static function script($self, $arg1) {
		$params = func_get_args();
		$params = array_slice($params, 2);
		$return = null;
		$config = self::$config;
		if ($arg1 != '__config' && file_exists(dirname(__FILE__)."/Scripts/$arg1.php")) {
			phpQuery::debug("Loading script '$arg1'");
			require dirname(__FILE__)."/Scripts/$arg1.php";
		} else {
			phpQuery::debug("Requested script '$arg1' doesn't exist");
		}
		return $return
			? $return
			: $self;
	}
}
abstract class phpQueryPlugin_Scripts {
	public static function __initialize() {
		if (file_exists(dirname(__FILE__)."/Scripts/__config.php")) {
			include dirname(__FILE__)."/Scripts/__config.php";
			phpQueryObjectPlugin_Scripts::$config = $config;
		}
	}
}
?>