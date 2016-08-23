<?php
/**
 * @copyright Copyright (c) 2016 Junaid Atari
 * @link http://junaidatari.com Website
 * @see http://www.github.com/blacksmoke26/yii2-cdn
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 */

namespace yii2cdn\traits;

/**
 * Class Attributes
 *
 * @package yii2cdn\traits
 */
trait Attributes {
	/**
	 * Get all the attributes
	 * @return array
	 */
	public function getAttributes () {
		return $this->attributes;
	}
	/**
	 * Set attribute's value
	 * @param string $name Name of the attribute
	 * @param mixed $value Value to be set
	 */
	public function setAttr ( $name, $value ) {
		$this->attributes[$name] = $value;
	}

	/**
	 * Get attribute's value
	 * @param string $name File attribute name
	 * @param mixed|null $defaultVal (optional) Return default value when attribute is undefined.
	 * @param boolean $throwException (optional) True will throw exception (default: false)
	 * @return mixed|null The value
	 * @throws \yii\base\UnknownPropertyException When undefined attribute name given.
	 */
	public function getAttr ( $name, $defaultVal = null, $throwException = false ) {
		if ( isset($this->attributes[$name]) ) {
			return $this->attributes[$name];
		}

		if ( $throwException ) {
			throw new UnknownPropertyException ( "Unknown file attribute '{$name}' given" );
		}

		return $defaultVal;
	}
}