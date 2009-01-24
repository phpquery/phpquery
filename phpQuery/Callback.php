<?php
/**
 * Callback class implementing ParamStructures, pattern similar to Currying.
 *
 * @link http://code.google.com/p/phpquery/wiki/Callbacks#Param_Structures
 * @author Tobiasz Cudnik
 */
class Callback {
	public $callback = null;
	public $params = null;
	public function __construct($callback, $param1 = null, $param2 = null, $param3 = null) {
		$params = func_get_args();
		$params = array_slice($params, 1);
		if ($callback instanceof Callback) {
			// TODO implement recurention
		} else {
			$this->callback = $callback;
			$this->params = $params;
		}
	}
	// TODO test me !!!
	public function param() {
		$params = func_get_args();
		return new Callback($this->callback, $this->params+$params);
	}
}
class CallbackReturnReference extends Callback {
	protected $reference;
	public function __construct(&$reference, $name = null){
		$this->reference =& $reference;
		$this->callback = array($this, 'callback');
	}
	public function callback() {
		return $this->reference;
	}
}
class CallbackReturnValue extends Callback {
	protected $value;
	public function __construct($value, $name = null){
		$this->value =& $value;
		$this->callback = array($this, 'callback');
	}
	public function callback() {
		return $this->value;
	}
}
/**
 * CallbackParameterToReference can be used when we don't really want a callback,
 * only parameter passed to it. CallbackReference takes first parameter's value
 * and passes it to reference. Thanks to that, we can use *if statement* instead
 * of *callback function*.
 *
 * @author Tobiasz Cudnik <tobiasz.cudnik/gmail.com>
 *
 */
class CallbackParameterToReference extends Callback {
	/**
	 *
	 * @param $reference
	 * @param $paramIndex
	 * @TODO implement $paramIndex; 
	 * param index choose which callback param will be passed to reference
	 */
	public function __construct(&$reference, $paramIndex = null){
		$this->callback =& $reference;
	}
}
class CallbackReference extends Callback {
	/**
	 *
	 * @param $reference
	 * @param $paramIndex
	 * @todo implement $paramIndex; param index choose which callback param will be passed to reference
	 */
	public function __construct(&$reference, $name = null){
		$this->callback =& $reference;
	}
}
class CallbackParam {}