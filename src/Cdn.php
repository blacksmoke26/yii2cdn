<?php

/**
 * @copyright Copyright (c) 2016 Junaid Atari
 * @link http://junaidatari.com Website
 * @see http://www.github.com/blacksmoke26/yii2-cdn
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 */

namespace yii2cdn;

use yii\base\InvalidConfigException;
use yii\base\UnknownPropertyException;
use yii\base\InvalidParamException;
use yii\helpers\ArrayHelper;

defined('YII2CDN_OFFLINE') or define('YII2CDN_OFFLINE', false);

/**
 * Yii2 CDN Component
 *
 * @package yii2cdn
 * @author Junaid Atari <mj.atari@gmail.com>
 *
 * @access public
 * @version 0.1
 */
class Cdn extends \yii\base\Component {

	/**
	 * Components registered under cdn
	 * @var array
	 */
	protected $_regComponents = [];

	/**
	 * Base url to cdn directory
	 * @var string
	 */
	public $baseUrl = null;

	/**
	 * base path to cdn directory
	 * @var string
	 */
	public $basePath = null;

	/**
	 * Custom url aliases, replaces with @alias(*) in files url
	 * Usage:
	 *  ['xyz' => '/url/to', ...]
	 * @var array
	 */
	public $aliases = [];

	/**
	 * CDN component class
	 * default: \yii2cdn\Component
	 * @var string
	 */
	public $componentClass = '\yii2cdn\Component';

	/**
	 * CDN components configuration parser class
	 * default: \yii2cdn\ComponentConfigParser
	 * @var string
	 */
	public $configParserClass = '\yii2cdn\ConfigParser';

	/**
	 * CDN component section class
	 * default: \yii2cdn\ComponentSection
	 * @var string
	 */
	public $sectionClass = '\yii2cdn\Section';

	/**
	 * CDN Configuration File Class
	 * default: \yii2cdn\ConfigFile
	 * @var string
	 */
	public $configFileClass = '\yii2cdn\ConfigFile';

	/**
	 * CDN component section file class
	 * default: \yii2cdn\SectionFile
	 * @var string
	 */
	public $fileClass = '\yii2cdn\File';

	/**
	 * CDN component configuration loader class
	 * default: \yii2cdn\ConfigLoader
	 * @var string
	 */
	public $configLoaderClass = '\yii2cdn\ConfigLoader';

	/**
	 * CDN components configuration files list
	 * Usage:
	 *     1. 'path/to/cdn-config.php' : main file path
	 *     2. ['path/to/cdn-config.php'] : main file path
	 *     3. ['path/to/cdn-config.php', 'offline'=>false] : online cdn file path
	 *     4. ['path/to/cdn-config.php', 'offline'=>true] : offline cdn file path
	 * @var array
	 */
	public $configs = [];

	/**
	 * CDN components configuration
	 * @var array
	 */
	public $components = [];

	/**
	 * Sections name list
	 * default: (<code>css</code>, <code>js</code>)
	 * @var array
	 */
	public $sections = ['js', 'css'];

	/**
	 * Components indexed list
	 * @var array
	 */
	protected $buildIncludes = [];

	/**
	 * Cache Key for caching build cdn configurations to load fast
	 * @var string
	 */
	public $cacheKey = null;

	/**
	 * Enable storing components configuration in cache
	 * @var boolean
	 */
	public $enableCaching = false;

	/**
	 * Component intializer
	 * @throws \yii\base\InvalidConfigException when property is empty
	 */
	public function init() {
		parent::init();

		foreach ( ['baseUrl', 'basePath'] as $prop ) {
			if ( empty($this->$prop) ) {
				throw new InvalidConfigException("{$prop} property is empty");
			}
		}

		if ( $this->enableCaching && empty($this->cacheKey) ) {
			throw new InvalidConfigException("cacheKey property is empty");
		}

		// Build components
		$this->buildComponentsCache();
	}
	/**
	 * Check that Mode Live or Offline
	 * @return bool
	 */
	public static function isOnline () {
		return !defined( 'YII2CDN_OFFLINE' ) ? true : !YII2CDN_OFFLINE;
	}

	/**
	 * Build a components list
	 */
	protected function buildComponentsCache () {

		if ( $this->enableCaching ) {

			$cached = \Yii::$app->cache->get ( $this->cacheKey );

			if ( $cached !== false ) {
				$this->_regComponents = $cached;
				return;
			}
		}

		// @property `components` : load CDN components config
		if ( is_array($this->components) && !empty($this->components) ) {
			$this->loadComponents($this->components);
		}

		// @property `configs` : load CDN components config files
		if ( is_array($this->configs) && !empty($this->configs) ) {

			foreach ( $this->configs as $cfg ) {

				if ( empty($cfg)) {
					continue;
				}

				$this->loadComponentsFile($cfg);
			}
		}

		if ( $this->enableCaching ) {
			\Yii::$app->cache->set($this->cacheKey, $this->_regComponents);
		}
	}

	/**
	 * Get a ConfigFile Object
	 * @param string $file
	 * @return ConfigFile
	 */
	protected function getFileConfigObject ( $file = null ) {
		/** @var ConfigFile $fileConfig */
		return \Yii::createObject($this->configFileClass, [ [
			'path' => $file,
			'componentClass' => $this->componentClass,
			'configParserClass' => $this->configParserClass,
			'configLoaderClass' => $this->configLoaderClass,
			'fileClass' => $this->fileClass,
			'sectionClass' => $this->sectionClass,
			'basePath' => $this->basePath,
			'baseUrl' => $this->baseUrl,
			'aliases' => $this->aliases,
			'sections' => $this->sections
		]]);
	}

	/**
	 * Import components from configuration array
	 * @param array $config Components configuration
	 */
	protected function loadComponents ( array $config ) {
		/** @var ConfigFile $configFile */
		$configFile = $this->getFileConfigObject( null );

		$this->_regComponents = ArrayHelper::merge(
			$this->_regComponents,
			$configFile->get( ($config) )
		);
	}

	/**
	 * Import components from configuration file
	 * @param string $path Components configuration file path
	 */
	protected function loadComponentsFile ( $path ) {
		/** @var ConfigFile $configFile */
		$configFile = $this->getFileConfigObject( $path );

		$this->_regComponents = ArrayHelper::merge(
			$this->_regComponents,
			$configFile->get()
		);
	}

	/**
	 * Remove cache and rebuild components list
	 */
	public function refresh () {
		\Yii::$app->cache->delete( $this->cacheKey );
		$this->buildComponentsCache();
	}

	/**
	 * Get cdn component by ID
	 * @see Cdn::exists()
	 * @param string $id Component ID
	 * @return Component|null Component Object
	 */
	public function get ( $id, $throwException = true )
	{
		if ( !$this->exists($id, $throwException) ) {
			return null;
		}

		return $this->_regComponents[$id];
	}

	/**
	 * Check that cdn component exists
	 * @param string $id Component ID
	 * @param boolean $throwException (optional) Throw exception when unknown component id given (default: false)
	 * @throws \yii\base\UnknownPropertyException When unknown component id given
	 * @return boolean True when exist, False when undefined
	 */
	public function exists ( $id, $throwException = true )
	{
		if ( !array_key_exists($id, $this->_regComponents) ) {
			if ( $throwException ) {
				throw new UnknownPropertyException ("Unknown cdn component '{$id}'");
			} else {
				return false;
			}
		}

		return true;
	}

	/**
	 * Get the file by root
	 * Root example : component-id/section
	 * @see Component::get()
	 * @see Section::getSection()
	 * @param string $root Root to file
	 * @param bool $throwException True will throw exception (default: true)
	 * @throws \yii\base\UnknownPropertyException When unknown component id given
	 * @throws \yii\base\InvalidParamException When null given as section
	 * @throws \yii\base\UnknownPropertyException When section name not found
	 * @return \yii2cdn\Section Section Object
	 */
	public function getSectionByRoot ( $root, $throwException = true ) {
		// validate the root
		if ( !is_string($root) || substr_count($root, '/') != 1 ) {
			throw new InvalidParamException ("Invalid section root '{$root}' given");
		}

		list ( $componentId, $sectionId ) = explode( '/', $root );

		return $this->get($componentId, $throwException)->getSection($sectionId, $throwException);
	}

	/**
	 * Get the file by root
	 * Root example : component-id/section/file-id
	 * @see Component::get()
	 * @see Section::getSection()
	 * @see Section::getFileById()
	 * @param string $root Root to file
	 * @param bool $asUrl True will return file url instead of object (default: false)
	 * @param bool $throwException True will throw exception (default: true)
	 * @throws \yii\base\UnknownPropertyException When unknown component id given
	 * @throws \yii\base\InvalidParamException When null given as section
	 * @throws \yii\base\UnknownPropertyException When section name not found
	 * @throws \yii\base\UnknownPropertyException When file id not found
	 * @return \yii2cdn\File|string|null Section file | File Url | Null when not found
	 */
	public function getFileByRoot ( $root, $asUrl = false, $throwException = true ) {
		// validate the root
		if ( !is_string($root) || substr_count($root, '/') != 2 ) {
			throw new InvalidParamException ("Invalid file root '{$root}' given");
		}

		list ($componentId, $sectionId, $fileId) = explode('/', $root);

		return $this->get($componentId, $throwException)->getFileByRoot( "$sectionId/$fileId", $asUrl, $throwException );
	}
}
