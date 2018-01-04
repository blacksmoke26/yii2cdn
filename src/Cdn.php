<?php
/**
 * @author Junaid Atari <mj.atari@gmail.com>
 * @link http://junaidatari.com Author Website
 * @see http://www.github.com/blacksmoke26/yii2-cdn
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 */

namespace yii2cdn;

use Yii;
use yii\base\InvalidConfigException;
use yii\base\InvalidParamException;
use yii\base\UnknownPropertyException;
use yii\helpers\ArrayHelper;

\defined('YII2CDN_OFFLINE')
	or \define('YII2CDN_OFFLINE', \false);

/**
 * Yii2 CDN Component
 *
 * @package yii2cdn
 * @author Junaid Atari <mj.atari@gmail.com>
 *
 * @access public
 * @version 0.2
 */
class Cdn extends \yii\base\Component {
	/**
	 * Used traits
	 */
	use \yii2cdn\traits\Url;
	use \yii2cdn\traits\File;

	/**
	 * @var string Base url to cdn directory
	 */
	public $baseUrl = \null;

	/**
	 * @var string base path to cdn directory
	 */
	public $basePath = \null;

	/**
	 * @var array Custom url aliases, replaces with @alias(*) in files url
	 * Usage:
	 *  ['xyz' => '/url/to', ...]
	 */
	public $aliases = [];

	/**
	 * @var string CDN component class
	 * default: \yii2cdn\Component
	 */
	public $componentClass = '\yii2cdn\Component';

	/**
	 * @var string CDN components configuration parser class
	 * default: \yii2cdn\ComponentConfigParser
	 */
	public $configParserClass = '\yii2cdn\ConfigParser';

	/**
	 * @var string CDN component section class
	 * default: \yii2cdn\ComponentSection
	 */
	public $sectionClass = '\yii2cdn\Section';

	/**
	 * @var string CDN Configuration File Class
	 * default: \yii2cdn\ConfigFile
	 */
	public $configFileClass = '\yii2cdn\ConfigFile';

	/**
	 * @var string CDN component section file class
	 * default: \yii2cdn\SectionFile
	 */
	public $fileClass = '\yii2cdn\File';

	/**
	 * @var string CDN component configuration loader class
	 * default: \yii2cdn\ConfigLoader
	 */
	public $configLoaderClass = '\yii2cdn\ConfigLoader';

	/**
	 * @var array CDN components configuration files list
	 * Usage:
	 * <code>
	 *  1. 'path/to/cdn-config.php' : main file path
	 *  2. ['path/to/cdn-config.php'] : main file path
	 *  3. ['path/to/cdn-config.php', 'offline'=>false] : online cdn file path
	 *  4. ['path/to/cdn-config.php', 'offline'=>true] : offline cdn file path
	 * </code>
	 */
	public $configs = [];

	/**
	 * @var array CDN components configuration
	 */
	public $components = [];

	/**
	 * @var array (optional) Add the Sections name only to be parsed (other will be skipped)
	 * default: (<code>css</code>, <code>js</code>)
	 */
	public $sections = ['css', 'js'];

	/**
	 * @var string Cache Key for caching built components configuration to load fast
	 */
	public $cacheKey = \null;

	/**
	 * @var boolean Enable storing components configuration in cache
	 */
	public $enableCaching = \false;

	/**
	 * @var array Components registered under cdn
	 */
	protected $_regComponents = [];

	/**
	 * Component initializer
	 * @throws \yii\base\InvalidConfigException when property is empty
	 */
	public function init () {
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
	 * Get a ConfigFile Object
	 * @param string $file
	 * @return ConfigFile
	 * @throws \yii\base\InvalidConfigException
	 */
	protected function getFileConfigObject ( $file = \null ) {
		/** @var ConfigFile $fileConfig */
		return Yii::createObject($this->configFileClass, [ [
			'path' => $file,
			'componentClass' => $this->componentClass,
			'configParserClass' => $this->configParserClass,
			'configLoaderClass' => $this->configLoaderClass,
			'fileClass' => $this->fileClass,
			'sectionClass' => $this->sectionClass,
			'basePath' => $this->basePath,
			'baseUrl' => $this->baseUrl,
			'aliases' => $this->aliases,
			'sections' => $this->sections,
		]]);
	}

	/**
	 * Import components from configuration array
	 *
	 * @param array $config Components configuration
	 * @throws \yii\base\InvalidConfigException
	 */
	protected function loadComponents ( array $config ) {
		/** @var ConfigFile $configFile */
		$configFile = $this->getFileConfigObject (\null);

		$this->_regComponents = ArrayHelper::merge (
			$this->_regComponents,
			$configFile->get ($config)
		);
	}

	/**
	 * Import components from configuration file
	 * @param string $path Components configuration file path
	 * @throws \yii\base\InvalidConfigException
	 */
	protected function loadComponentsFile ( $path ) {
		/** @var ConfigFile $configFile */
		$configFile = $this->getFileConfigObject ($path);

		$this->_regComponents = ArrayHelper::merge(
			$this->_regComponents,
			$configFile->get()
		);
	}

	/**
	 * Build a components list
	 * @throws \yii\base\InvalidConfigException
	 */
	protected function buildComponentsCache () {
		if ( $this->enableCaching ) {
			/** @var mixed|false $cached */
			$cached = Yii::$app->cache->get ($this->cacheKey);

			if ( \false !== $cached ) {
				$this->_regComponents = $cached;
				return;
			}
		}

		// @property `components` : load CDN components config
		if ( \is_array($this->components) && !empty($this->components) ) {
			$this->loadComponents($this->components);
		}

		// @property `configs` : load CDN components config files
		if ( \is_array($this->configs) && !empty($this->configs) ) {
			foreach ( $this->configs as $cfg ) {
				if ( empty($cfg)) {
					continue;
				}

				$this->loadComponentsFile($cfg);
			}
		}

		if ( $this->enableCaching ) {
			Yii::$app->cache->set($this->cacheKey, $this->_regComponents);
		}
	}

	/**
	 * Clear cache and rebuild components
	 * @throws \yii\base\InvalidConfigException
	 */
	public function refresh () {
		Yii::$app->cache->delete ($this->cacheKey);
		$this->buildComponentsCache();
	}

	/**
	 * Check that Mode Live or Offline
	 *
	 * @return bool
	 */
	public static function isOnline () {
		return !\defined ('YII2CDN_OFFLINE')
			? \true
			: !YII2CDN_OFFLINE;
	}

	/**
	 * Get cdn component by ID
	 * @see Cdn::exists()
	 * @param string $id Component ID
	 * @return Component|null Component Object
	 * @throws \yii\base\UnknownPropertyException
	 */
	public function get ( $id, $throwException = \true ) {
		if ( !$this->exists($id, $throwException) ) {
			return \null;
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
	public function exists ( $id, $throwException = \true ) {
		if ( !\array_key_exists($id, $this->_regComponents) ) {
			if ( $throwException ) {
				throw new UnknownPropertyException ("Unknown cdn component '{$id}'");
			}

			return \false;
		}

		return \true;
	}

	/**
	 * Get the file by root
	 * Root example : component-id/section
	 *
	 * @see Component::get()
	 * @see Section::getSection()
	 * @param string $root Root to file
	 * @param bool $throwException True will throw exception (default: true)
	 * @throws \yii\base\UnknownPropertyException When unknown component id given
	 * @throws \yii\base\InvalidParamException When null given as section
	 * @throws \yii\base\UnknownPropertyException When section name not found
	 * @return \yii2cdn\Section Section Object
	 */
	public function getSectionByRoot ( $root, $throwException = \true ) {
		// validate the root
		if ( !\is_string ( $root )
			|| 1 != \substr_count ( $root, '/' ) ) {
			throw new InvalidParamException ( "Invalid section root '{$root}' given" );
		}

		/** @var string $componentId */
		/** @var string $sectionId */
		list ($componentId, $sectionId) = \explode ('/', $root);

		return $this->get ($componentId, $throwException)
			->getSection ($sectionId, $throwException);
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
	public function getFileByRoot ( $root, $asUrl = \false, $throwException = \true ) {
		// validate the root
		if ( !\is_string($root) || 2 != \substr_count($root, '/') ) {
			throw new InvalidParamException ("Invalid file root '{$root}' given");
		}

		/** @var string $componentId */
		/** @var string $sectionId */
		list ($componentId, $sectionId, $fileId) = \explode ('/', $root);

		return $this->get ($componentId, $throwException)
			->getFileByRoot ("$sectionId/$fileId", $asUrl, $throwException);
	}

	/**
	 * Perform a callback function when Offline mode is active
	 * @see Cdn::isOnline()
	 * @param callable $callback A callback function
	 * <code>
	 *     function ( \yii2cdn\Cdn $cdn ) {
	 *         // some logic here
	 *     }
	 * </code>
	 * @param string $property (optional) Default component property name (default: cdn)
	 * @return Cdn
	 * @throws \yii\base\InvalidConfigException When the callback parameter is not a function
	 */
	public function whenOffline ( $callback, $property = 'cdn' ) {
		if ( !\is_callable($callback) ) {
			throw new InvalidParamException ("Parameter '{callback}' should be a function");
		}

		if ( !self::isOnline()  ) {
			\call_user_func_array( $callback, [Yii::$app->get($property)] );
		}

		return $this;
	}

	/**
	 * Perform a callback function when Online mode is active
	 * @see Cdn::isOnline()
	 * @param callable $callback A callback function
	 * <code>
	 *     function ( \yii2cdn\Cdn $cdn ) {
	 *         // some logic here
	 *     }
	 * </code>
	 * @param string $property (optional) Default component property name (default: cdn)
	 * @return Cdn
	 * @throws \yii\base\InvalidConfigException When the callback parameter is not a function
	 */
	public function whenOnline ( $callback, $property = 'cdn' ) {
		if ( !\is_callable($callback) ) {
			throw new InvalidParamException ("Parameter '{callback}' should be a function");
		}

		if ( self::isOnline()  ) {
			\call_user_func_array( $callback, [Yii::$app->get($property)] );
		}

		return $this;
	}
}
