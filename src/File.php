<?php
/**
 * @author Junaid Atari <mj.atari@gmail.com>
 * @link http://junaidatari.com Author Website
 * @see http://www.github.com/blacksmoke26/yii2-cdn
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 */

namespace yii2cdn;

use Yii;

/**
 * Yii2 Section File Component
 *
 * @package yii2cdn
 * @author Junaid Atari <mj.atari@gmail.com>
 *
 * @access public
 * @version 0.2
 */
class File {
	/**
	 * Used traits
	 */
	use \yii2cdn\traits\Attributes;

	/**
	 * File name
	 * @var string
	 */
	protected $fileName;
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
	 * Base Path
	 * @var string
	 */
	protected $basePath;

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
		if ( \count ($config) ) {
			foreach ( $config as $prop => $value ) {
				$this->$prop = $value;
			}
		}
	}

	/**
	 * Get current file id
	 *
	 * @return string
	 */
	public function getId () {
		return $this->fileId;
	}

	/**
	 * Get current file name
	 *
	 * @return string
	 */
	public function getName () {
		return $this->fileName;
	}

	/**
	 * Get current file url
	 * @return string
	 */
	public function getUrl () {
		return $this->fileUrl;
	}

	/**
	 * Get current file base path
	 * @return string
	 */
	public function getPath () {
		return $this->basePath;
	}

	/**
	 * Get file section or it's id
	 * @param boolean $idOnly (optional) True will return section name (default: false)
	 * @param string $property (optional) Property name of `cdn` defined in @app/config/main.php (default: 'cdn')
	 * @return Section|string Section object | Section name
	 */
	public function getSection ( $idOnly = \false, $property = 'cdn' ) {
		return $idOnly
			? $this->section
			: $this->getComponent (\false, $property)->getSection ($this->section);
	}

	/**
	 * Get current file component or it's id
	 * @param boolean $idOnly (optional) True will return component id (default: false)
	 * @param string $property (optional) Property name of `cdn` defined in @app/config/main.php (default: 'cdn')
	 * @return Component|string Component object | Component id
	 */
	public function getComponent ( $idOnly = \false, $property = 'cdn' ) {
		return $idOnly
			? $this->component
			: Yii::$app->cdn->get ($property)->get ($this->component);
	}

	/**
	 * Register as CSS file
	 * @param array $options the HTML attributes for the link tag. Please refer to [[Html::cssFile()]] for
	 * the supported options. The following options are specially handled and are not treated as HTML attributes:
	 *
	 * - `depends`: array, specifies the names of the asset bundles that this CSS file depends on.
	 *
	 * @param string $key the key that identifies the CSS script file. If null, it will use
	 * @throws \yii\base\UnknownPropertyException
	 * @return void
	 */
	public function registerAsCssFile ( array $options = [], $key = \null ) {
		if ( !$this->getAttr('registrable', \true) ) {
			return;
		}

		/** @var array $_options */
		$_options = \array_merge($this->options, $options);

		/** @var string $_key */
		$_key = \null === $key
			? $this->getId()
			: $key;

		/** @var string $url */
		$url = $this->fileUrl;

		// Append file modified timestamp at end
		if ( $this->getAttr('timestamp', \false)
			&& ($timestamp = $this->getModifiedTime() ) ) {
			$url .= '?v='.$timestamp;
		}

		Yii::$app->controller->view->registerCssFile( $url, $_options, $_key );
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
	 * @throws \yii\base\UnknownPropertyException
	 * @return void
	 */
	public function registerAsJsFile ( array $options = [], $key = \null ) {
		if ( !$this->getAttr('registrable', \true) ) {
			return;
		}

		/** @var array $_options */
		$_options = \array_merge($this->options, $options);

		/** @var string $_key */
		$_key = \null === $key
			? $this->getId()
			: $key;

		/** @var string $url */
		$url = $this->fileUrl;

		// Append file modified timestamp at end
		if ( $this->getAttr('timestamp', \false)
			&& ($timestamp = $this->getModifiedTime() ) ) {
			$url .= '?v='.$timestamp;
		}

		Yii::$app->controller->view->registerJsFile( $url, $_options, $_key );
	}

	/**
	 * Get the file modified timestamp
	 * @return int|null time stamp | Unable to retrieve file modified time
	 */
	public function getModifiedTime () {
		if ( \file_exists($this->basePath) ) {
			return \filemtime( $this->basePath );
		}

		return \null;
	}
}
