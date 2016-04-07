<?php

/**
 * @copyright Copyright (c) 2016 Junaid Atari
 * @link http://junaidatari.com Website
 * @see http://www.github.com/blacksmoke26/yii2-cdn
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 */

namespace yii2cdn;

use yii\base\UnknownPropertyException;

/**
 * Yii2 Section File Component
 *
 * @package yii2cdn
 * @author Junaid Atari <mj.atari@gmail.com>
 *
 * @access public
 * @version 0.1
 */
class File {

	/**
	 * File ID
	 * @var string
	 */
	protected $fileId;

	/**
	 * File Url
	 * @var string
	 */
	protected $fileUrl;

	/**
	 * Section name
	 * @var string
	 */
	protected $section;

	/**
	 * Component name
	 * @var string
	 */
	protected $component;

	/**
	 * File options
	 * @var array
	 */
	protected $options = [];

	/**
	 * File attributes
	 * @var array
	 */
	protected $attributes = [];

	/**
	 * ComponentFile constructor.
	 *
	 * @param array $config Configuration object
	 */
	public function __construct ( array $config ) {
		
		if ( !count( $config ) ) {
			return;
		}
		
		foreach ($config as $prop => $value ) {
			$this->$prop = $value;
		}
	}

	/**
	 * Get current file id
	 * @return string
	 */
	public function getId () {
		return $this->fileId;
	}

	/**
	 * Get current file url
	 * @return string
	 */
	public function getUrl () {
		return $this->fileUrl;
	}

	/**
	 * Get file section or it's id
	 * @param boolean $asId (optional) True will return section name (default: false)
	 * @return Section|string Section object | Section name
	 */
	public function getSection ( $asId ) {
		return $asId ? $this->section : $this->getComponent()->getSection($this->section);
	}

	/**
	 * Get current file component or it's id
	 * @param boolean $asId (optional) True will return component id (default: false)
	 * @return Component|string Component object | Component id
	 */
	public function getComponent ( $asId ) {
		return $asId ? $this->component : \Yii::$app->cdn->get($this->section);
	}

	/**
	 * Get the file attributes
	 * @return array
	 */
	public function getAttributes () {
		return $this->attributes;
	}

	/**
	 * @param string $name File attribute name
	 * @param boolean $throwException (optional) True will throw exception (default: true)
	 * @throws \yii\base\UnknownPropertyException
	 * @return mixed|null Attribute value | null on empty
	 */
	public function getAttr ( $name, $throwException = true ) {

		if ( isset($this->attributes[$name]) ) {
			return $this->attributes[$name];
		}

		if ( $throwException ) {
			throw new UnknownPropertyException ("Unknown file attribute '{$name}' given");
		}

		return null;
	}

	/**
	 * Register as CSS file
	 * @param array $options the HTML attributes for the link tag. Please refer to [[Html::cssFile()]] for
	 * the supported options. The following options are specially handled and are not treated as HTML attributes:
	 *
	 * - `depends`: array, specifies the names of the asset bundles that this CSS file depends on.
	 *
	 * @param string $key the key that identifies the CSS script file. If null, it will use
	 */
	public function registerAsCssFile ( array $options = [], $key = null ) {

		$options = !count($options) ? $this->options : $options;
		$key = is_null($key) ? $this->getId() : $key;

		\Yii::$app->controller->view->registerCssFile( $this->fileUrl, $options, $key );
	}

	/**
	 * Register as JavaScript file
	 * @param array $options the HTML attributes for the script tag. The following options are specially handled
	 * and are not treated as HTML attributes:
	 *
	 * - `depends`: array, specifies the names of the asset bundles that this JS file depends on.
	 * - `position`: specifies where the JS script tag should be inserted in a page. The possible values are:
	 *     * [[POS_HEAD]]: in the head section
	 *     * [[POS_BEGIN]]: at the beginning of the body section
	 *     * [[POS_END]]: at the end of the body section. This is the default value.
	 *
	 * Please refer to [[Html::jsFile()]] for other supported options.
	 *
	 * @param string $key the key that identifies the JS script file. If null, it will use
	 * $url as the key. If two JS files are registered with the same key, the latter
	 * will overwrite the former.
	 */
	public function registerAsJsFile ( array $options = [], $key = null ) {

		$options = !count($options) ? $this->options : $options;
		$key = is_null($key) ? $this->getId() : $key;

		\Yii::$app->controller->view->registerJsFile( $this->fileUrl, $options, $key );
	}
}
