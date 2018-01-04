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
 * Class File
 *
 * @package yii2cdn\src\traits
 */
trait File {
	/**
	 * Get a base path
	 * @param null|string|array $str (optional) Append string at end of path (/ is optional) | List of strings to append path with (default: null)
	 * @return string|array
	 */
	public function getPath ( $str = \null ) {
		if ( \null === $str || empty($str) ) {
			return $this->basePath;
		}

		if ( !\is_string ($str) && !\is_array($str) ) {
			throw new InvalidParamException ("Parameter 'str' should be string or an array.");
		}

		/** @var array $list */
		$list = \is_string ( $str)
			? [$str]
			: (array) $str;

		/** @var array $newList */
		$newList = [];

		foreach ( $list as $itm ) {
			$newList[] = $this->basePath
				. \DIRECTORY_SEPARATOR
				. \ltrim($itm, '\\/');
		}

		return \count($newList) === 1
			? \array_shift($newList)
			: $newList;
	}
}