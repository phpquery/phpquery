<?php
/**
 * Example of phpQuery plugin.
 * 
 * Use like this:
 * phpQuery::extend('example')
 * phpQuery::extend('phpQuery_example')
 * pq()->extend('example')
 * pq()->extend('phpQuery_example')
 *
 * Have fun writing plugins :)
 */
class phpQuery_example {
	/**
	 * Limit binded methods to specified ones.
	 * 
	 * @var array
	 */
	var $phpQueryExtendBy = null;
	/**
	 * Enter description here...
	 *
	 * @param phpQuery $self
	 */
	public static function example($self) {
		// do something
		$self->append('Im just an example !');
		// change stack of result object
		return $self->find('div');
	}
	protected static function helperFunction() {
		// this method WONT be avaible as phpQuery method, because it isn't publicly callable
	}
}
?>