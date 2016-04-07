<?php

/**
 * @copyright Copyright (c) 2016 Junaid Atari
 * @link http://junaidatari.com Website
 * @see http://www.github.com/blacksmoke26/yii2-cdn
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 */

namespace yii2cdn;

use yii\base\InvalidParamException;
use yii\base\InvalidValueException;
use yii\base\UnknownPropertyException;

/**
 * Yii2 CDN Component object
 *
 * @package yii2cdn
 * @author Junaid Atari <mj.atari@gmail.com>
 *
 * @access public
 * @version 0.1
 */
class Component
{
	/**
	 * Component ID
	 * @var string
	 */
	protected $id;

	/**
	 * Component's base url
	 * @var string
	 */
	protected $baseUrl;

	/**
	 * Sections list
	 * @var Section[]
	 */
	protected $sections = [];

	/**
	 * CDN sections name
	 * @var array
	 */
	protected $sectionsName = [];

	/**
	 * Component constructor.
	 *
	 * @param string $id Component ID
	 * @param array $config Configuration
	 */
	public function __construct ( array $config )
	{
		$this->baseUrl = $config['baseUrl'];
		$this->id = $config['id'];
		$this->sectionsName = $config['sections'];
		$this->buildSections($config);
	}

	/**
	 * Create the component section object from config<br>
	 * Note: Empty sections will be removed
	 *
	 * @param array $config Configuration object
	 */
	protected function buildSections ( array $config ) {
		foreach ( $this->sectionsName as $name ) {

			if ( !array_key_exists($name, $config) || empty($config[$name]) ) {
				continue;
			}

			$_attributes = isset ($config['fileAttrs'])
				? isset($config['fileAttrs'][$name]) ? $config['fileAttrs'][$name] : []
				: [];

			// Create section(s) component
			/** @var Section $section */			
			$this->sections[$name] = \Yii::createObject($config['sectionClass'], [[
				'componentId' => $this->id,
				'section' =>$name,
				'files' => $config[$name],
				'baseUrl' => rtrim($this->baseUrl, '/'). "/{$name}",
				'attributes' => $_attributes,
				'fileClass' => $config['fileClass'],
			]]);
		}
	}

	/**
	 * Get component base Url
	 * @param string $str (optional) Append string at end of url (/ is optional)
	 * @return string
	 */
	public function getUrl ( $str = null ) {
		if ( empty($str) ) {
			return $this->baseUrl;
		}

		return $this->baseUrl . (substr($str,0,1) !== '/' ? '/'.$str : $str);
	}

	/**
	 * Get a section(s) object by ID
	 * @param string|array Section name|List of sections name
	 * @param boolean $throwException (optional) True will throw exception when section name not found (default: true)
	 * @throws \yii\base\InvalidParamException When null given as section
	 * @throws \yii\base\UnknownPropertyException When section name not found
	 * @return Section|array|null Section | Sections List | Null when not found
	 */
	public function getSection ( $name, $throwException = true ) {

		$sections = $name;

		if ( !is_array($name) ) {
			$sections = [$name];
		}

		$list = [];

		foreach ( $sections as $name ) {
			if ( !$this->sectionExists($name, $throwException ) ) {
				continue;
			}

			$list[] = $this->sections[$name];
		}

		return count($list) == 1 ? array_shift($list) : $list;
	}

	/**
	 * Check that given section exists
	 * @param string $name Section name to check
	 * @param boolean $throwException (optional) True will throw exception when section name not found (default: false)
	 * @throws \yii\base\InvalidValueException When section name is empty
	 * @throws \yii\base\UnknownPropertyException When section name not found
	 * @return True on exist | False when not found
	 */
	public function sectionExists ( $name, $throwException = true ) {

		if ( empty($name) ) {
			if ( $throwException ) {
				throw new InvalidValueException ('Section name cannot be empty');
			}

			return false;
		}

		if ( !array_key_exists($name, $this->sections) ) {

			if ( $throwException ) {
				throw new UnknownPropertyException ("Section '{$name}' not found");
			}

			return false;
		}

		return true;
	}

	/**
	 * Get a javaScript files list
	 * @param boolean $filesOnly (optional) True will return files url only (default: false)
	 * @param boolean $unique (optional) True will remove duplicate elements (default: true)
	 * @return array List of js section files
	 */
	public function getJsFiles ( $asUrl = false, $unique = true ) {
		$files = $this->getSection('css')->getFiles(true, $unique);

		if ( !$asUrl ) {
			return $files;
		}

		return array_values($files);
	}

	/**
	 * Get style files list
	 * @param boolean $filesOnly (optional) True will return files url only (default: false)
	 * @param boolean $unique (optional) True will remove duplicate elements (default: true)
	 * @return array List of css section files
	 */
	public function getCssFiles ( $asUrl = true, $unique = true ) {
		$files = $this->getSection('css')->getFiles(true, $unique);

		if ( !$asUrl ) {
			return $files;
		}

		return array_values($files);
	}

	/**
	 * Register css and js files into current view
	 * @see Component::registerCssFiles() for $cssOptions
	 * @see Component::registerJsFiles() for $jsOptions
	 * 
	 * @param array $cssOptions (optional) Optional pass to css files
	 * @param array $jsOptions (optional) Optional pass to js files
	 * @param callable|null $callback (optional) Perform callback on each registering file
	 * <code>
	 *    function ( string $fileUrl, string $fileId ) {
	 *      // some logic here ...
	 *    }
	 * </code>
	 */
	public function register ( array $cssOptions = [], array $jsOptions = [], callable $callback = null ) {
		$this->registerCssFiles ( $cssOptions, $callback );
		$this->registerJsFiles ( $jsOptions, $callback );
	}

	/**
	 * Register component CSS files
	 * @see Section::registerFilesAs()
	 * @param array $options the HTML attributes for the link tag. Please refer to [[Html::cssFile()]] for
	 * the supported options. The following options are specially handled and are not treated as HTML attributes:
	 *
	 * - `depends`: array, specifies the names of the asset bundles that this CSS file depends on.
	 *
	 * @param callable|null $callback (optional) Perform callback on each registering file
	 * <code>
	 *    function ( string &$fileUrl, string &$fileId ) {
	 *      // some logic here ...
	 *    }
	 * </code>
	 */
	public function registerCssFiles( array $options = [], callable $callback = null ) {
		if ( !$this->sectionExists('css', false ) ) {
			return;
		}

		$this->getSection('css')->registerFilesAs ('css', null, $options, $callback );
	}

	/**
	 * Register component JavaScript files
	 * @see Section::registerFilesAs()
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
	 * @param callable|null $callback (optional) Perform callback on each registering file
	 * <code>
	 *    function ( string &$fileUrl, string &$fileId ) {
	 *      // some logic here ...
	 *    }
	 * </code>
	 */
	public function registerJsFiles( array $options = [], callable $callback = null ) {
		if ( !$this->sectionExists('js', false ) ) {
			return;
		}

		$this->getSection('js')->registerFilesAs('js', null, $options, $callback );
	}

	/**
	 * Get the file by root
	 * Root example : section/file-id
	 * @see Section::getSection()
	 * @see Section::getFileById()
	 * @param string $root Root to file
	 * @param bool $asUrl True will return file url instead of object (default: false)
	 * @param bool $throwException True will throw exception (default: true)
	 * @throws \yii\base\InvalidParamException When null given as section
	 * @throws \yii\base\UnknownPropertyException When section name not found
	 * @throws \yii\base\UnknownPropertyException When file id not found
	 * @return \yii2cdn\File|string|null Section file | File Url | Null when not found
	 */
	public function getFileByRoot ( $root, $asUrl = false, $throwException = true ) {
		// validate the root
		if ( !is_string($root) || ($counts = substr_count($root, '/') ) === false || $counts != 2 ) {
			throw new InvalidParamException ("Invalid root '{$root}' given");
		}

		list ($sectionId, $fileId) = explode('/', $root);

		return $this->getSection($sectionId, $throwException)
			->getFileById ($fileId, $asUrl, $throwException);
	}
}
