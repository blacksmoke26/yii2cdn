<?php
/**
 * @author Junaid Atari <mj.atari@gmail.com>
 * @link http://junaidatari.com Author Website
 * @see http://www.github.com/blacksmoke26/yii2-cdn
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 */

namespace yii2cdn\traits;

use yii\base\InvalidParamException;

/**
 * Trait Url
 *
 * @package yii2cdn\src\traits
 */
trait Url {
	/**
	 * Get the Url
	 * @param null|string|array $str (optional) Append string at end of url (/ is optional) | List of strings to append url with (default: null)
	 * @return string|array
	 */
	public function getUrl ( $str = \null ) {
		if ( \null === $str || empty($str) ) {
			return $this->baseUrl;
		}

		if ( !\is_string ($str) && !\is_array($str) ) {
			throw new InvalidParamException ("Parameter 'str' should be string or an array.");
		}

		/** @var array $list */
		$list = \is_string ($str)
			? [$str]
			: (array) $str;

		/** @var array $newList */
		$newList = [];

		foreach ( $list as $itm ) {
			$newList[] = $this->baseUrl
				. ( 0 !== \strpos ( $itm, '/')
					? '/' . $itm
					: $itm
				);
		}

		return 1 === \count($newList)
			? \array_shift($newList)
			: $newList;
	}
}