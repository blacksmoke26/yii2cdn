<?php
/**
 * @copyright Copyright (c) 2016 Junaid Atari
 * @link http://junaidatari.com Website
 * @see http://www.github.com/blacksmoke26/yii2-cdn
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 */

namespace yii2cdn\traits;

/**
 * Trait Url
 *
 * @package yii2cdn\src\traits
 */
trait Url {
	/**
	 * Get the Url
	 *
	 * @param null|string|array $str (optional) Append string at end of url (/ is optional) | List of strings to append url with (default: null)
	 * @return string|array
	 */
	public function getUrl ( $str = null ) {
		if ( $str === null || empty($str) ) {
			return $this->baseUrl;
		}

		if ( !is_string ($str) && !is_array($str) ) {
			throw new InvalidParamException ("Parameter 'str' should be string or an array.");
		}

		$list = is_string ( $str)
			? [$str]
			: (array) $str;

		$newList = [];

		foreach ( $list as $itm ) {
			$newList[] = $this->baseUrl
				. ( 0!== strpos ( $itm, '/') ? '/' . $itm : $itm );
		}

		return count($newList) === 1
			? array_shift($newList)
			: $newList;
	}
}