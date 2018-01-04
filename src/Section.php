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

/**
 * Yii2 Component Section File object
 *
 * @package yii2cdn
 * @author Junaid Atari <mj.atari@gmail.com>
 *
 * @access public
 * @version 0.2
 */
class Section {
	/**
	 * Used traits
	 */
	use \yii2cdn\traits\Url;
	use \yii2cdn\traits\File;
	use \yii2cdn\traits\Attributes;

	/**
	 * @var string Section base Url
	 */
	protected $baseUrl;

	/**
	 * @var string Base Path
	 */
	protected $basePath;

	/**
	 * @var string Component ID
	 */
	protected $component;

	/**
	 * @var string Section name
	 */
	protected $section;

	/**
	 * @var array Section attributes
	 */
	protected $attributes = [];

	/**
	 * @var File[] Section files
	 */
	protected $files;

	/**
	 * @var string Section file class
	 */
	protected $fileClass;

	/**
	 * ComponentSection constructor.
	 * @param array $config Configuration object
	 * @throws \yii\base\UnknownPropertyException
	 * @throws \yii\base\InvalidConfigException
	 */
	public function __construct ( array $config ) {
		$this->component = $config['component'];
		$this->section = $config['section'];
		$this->baseUrl = $config['baseUrl'];
		$this->basePath = $config['basePath'];
		$this->fileClass = $config['fileClass'];
		$this->attributes = $config['attributes'];

		if ( !isset($config['files'])
			|| !\is_array($config['files'])
			|| !\count($config['files']) ) {
			return;
		}

		foreach ( $config['files'] as $id => $props ) {
			/** @var array $options */
			/** @var array $_attributes */
			$options = $_attributes = [];

			if ( isset($config['fileAttributes'])
				&& \is_array($config['fileAttributes'])
				&& \count($config['fileAttributes']) ) {

				foreach ( $config['fileAttributes'] as $k => $v ) {
					$inf = \explode('/', $k);

					if ( $k === "@options/{$id}" ) {
						$options = $v;
						continue;
					} else if ( $id === $inf[1] ) {
						$_attributes[ $inf[0] ] = $v;
					}
				}
			}

			/** @var string $basePath */
			$basePath = $this->basePath . DIRECTORY_SEPARATOR . $props['file'];

			// Defined all files attributes
			if ( \null !== ($val = $this->getAttr('@filesAttrs') ) ) {
				$_attributes = ArrayHelper::merge(
					(array) $this->getAttr('@filesAttrs'),
					$_attributes
				);
			}

			if ( isset($props['_component'])
				&& isset($config['preComponents'][$props['_component']]) ) {
				/** @var Section $comp */
				$comp = $config['preComponents'][$props['_component']]->getSection($props['_section']);
				$basePath = $comp->getPath($props['file']);
			}

			/** @var File $fileNode */
			$fileNode = Yii::createObject($this->fileClass, [[
				'fileId' => $id,
				'fileUrl' => $props['url'],
				'fileName' => $props['file'],
				'basePath' => $basePath,
				'section' => (!isset($props['_section']) ? $this->section : $props['_section']),
				'component'=> $this->component,
				'options' => $options,
				'attributes' => $_attributes,
			]]);

			unset($config['preComponents']);

			$this->files[$id] = $fileNode;
		}

		// Remove files attributes
		unset($this->attributes['@filesAttrs']);
	}

	/**
	 * Get section name
	 * @return string
	 */
	public function getName () {
		return $this->section;
	}

	/**
	 * Get section's component or id
	 * @param boolean $asId (optional) True will return component id (default: false)
	 * @param string $property (optional) Property name of `cdn` defined in @app/config/main.php (default: 'cdn')
	 * @return Component|string Component object | Component ID
	 * @throws \yii\base\InvalidConfigException
	 */
	public function getComponent ( $asId = \false, $property = 'cdn' ) {
		return $asId
			? $this->component
			: Yii::$app->get ($property)
				->get ($this->component);
	}

	/**
	 * Perform a callback on each file and get the filtered files
	 * @param callable $callback Perform a callback on each file
	 * <code>
	 *     # $excluded : True if file has been skipped (found in 'excluded' list), FALSE anyway
	 *     # $included : True if file has been found in 'includeOnly' list, FALSE anyway
	 *     function (string &$fileUrl, string &$fileId, boolean $excluded, boolean $included ) {
	 *         // returning false will skip the file for further process.
	 *     }
	 * </code>
	 * @param array $options (optional) Addition options pass to function<br>
	 * Options are:
	 * <code>
	 *     > boolean listOnly : Returning the files url list only (default: false)
	 *     > boolean noIds : Returning the list without file ids (default: false)
	 *     > boolean unique : True will return only unique files (default: true)
	 *     > array excluded : List of Files id to be skipped (default: [])
	 *     > array includeOnly : List of Files id to be included, other will be skipped (default: [])
	 * </code>
	 * @param boolean $throwException (optional) True will throw exception (default: true)
	 * @throws \yii\base\InvalidParamException Option 'callback' is not a function
	 * @throws \yii\base\InvalidConfigException When option 'excluded' not an array
	 * @throws \yii\base\InvalidConfigException When option 'includeOnly' not an array
	 * @return File[]|array List of files | List of sections and their files [SECTION=>FILES_LIST][]
	 */
	public function callback ( callable $callback, array $options = [ ], $throwException = true ) {
		if ( !\is_callable($callback) ) {
			throw new \yii\base\InvalidParamException("Option 'callback' must be a function");
		}

		// @property: boolean listOnly
		$listOnly = isset($options['listOnly'])
			? \boolval($options['listOnly'])
			: \false;

		// @property: boolean noIds
		$noIds = isset($options['noIds'])
			? \boolval($options['noIds'])
			: \false;

		// @property: boolean unique
		$unique = isset($options['unique'])
			? \boolval($options['unique'])
			: \false;

		/** @var array $excluded */
		/** @var array $includeOnly */
		$excluded = $includeOnly = [];

		// @property: array excluded
		if ( isset($options['excluded']) ) {
			if ( !\is_array ($options['excluded']) && $throwException ) {
				throw new InvalidConfigException("Option 'excluded' must be an array");
			}

			$excluded = $options['excluded'];
		}

		// @property: array includeOnly
		if ( isset($options['includeOnly']) ) {
			if ( !\is_array ($options['excluded']) && $throwException ) {
				throw new InvalidConfigException("Option 'includeOnly' must be an array");
			}

			$includeOnly = $options['includeOnly'];
		}

		/** @var array $files */
		$files = $this->getFiles (\false, $unique);

		if ( !\is_array ($files) || !\count ($files) ) {
			return [];
		}

		/** @var File $file */
		foreach ( $files as $file ) {
			/** @var string $fileId */
			$fileId = $file->getId();
			/** @var string $fileUrl */
			$fileUrl = $file->getUrl();

			// Skipped files if excluded list isn't empty and id isn't present
			if ( \in_array ($fileId, $excluded, \true) ) {
				/** @var mixed $op */
				$op = \is_callable ($callback)
					? $callback($fileUrl, $fileId, \true, \false)
					: \null;

				if ( \null === $op || \false === $op ) {
					continue;
				}
			}

			/** @var mixed|null $op */
			$op = \is_callable($callback)
				? $callback( $fileUrl, $fileId, \false, \false )
				: \null;

			if ( \false === $op ) {
				continue;
			}

			// Skipped files if includeOnly list isn't empty and id isn't present
			if ( (\is_array($includeOnly) && \count($includeOnly))
				&& !\in_array($fileId, $includeOnly) ) {

				/** @var mixed|null|false $op */
				$op = \is_callable($callback)
					? $callback( $fileUrl, $fileId, \false, \true )
					: \null;

				if ( \null === $op || \false === $op ) {
					continue;
				}
			}

			$files[$fileId] = $listOnly
				? $fileUrl
				: $file;
		}

		return $noIds
			? \array_values ($files)
			: $files;
	}

	/**
	 * Get section files list
	 * @param  boolean $asArray True will return files list only (default: false)
	 * @param boolean $unique (optional) True will remove duplicate elements (default: false)
	 * @return File[]|array List of section object/[key=>value] pair of section files
	 */
	public function getFiles ( $asArray = \false, $unique = \false ) {
		if ( !$asArray ) {
			return $this->files;
		}

		/** @var File[] $list */
		$list = [];

		if ( !\is_array($this->files) || !\count($this->files) ) {
			return $list;
		}

		foreach ( $this->files as $file ) {
			$list[$file->getId()] = $file->getUrl();
		}

		if ( $unique ) {
			$list = \array_unique($list);
		}

		return $list;
	}

	/**
	 * Get the file by ID
	 * @param string $id File ID
	 * @param bool $asUrl (optional) True will return file url instead of object (default: false)
	 * @param bool $throwException (optional) True will throw exception when file id not found (default: true)
	 * @throws \yii\base\UnknownPropertyException When file id not found
	 * @return \yii2cdn\File|string|null Section file | File Url | Null when not found
	 */
	public function getFileById ( $id, $asUrl = \false, $throwException = \true ) {
		if ( !\array_key_exists($id, $this->files) ) {

			if ( $throwException ) {
				throw new UnknownPropertyException ( "Unknown file id '{$id}' given" );
			}

			return \null;
		}

		return !$asUrl
			? $this->files[$id]
			: $this->files[$id]->getUrl();
	}

	/**
	 * Register list of files/files object into current view by type
	 * @param string $type Register files as (e.g., js|css)
	 * @param array|null $list List of File[] array | simple list | Include all files
	 * @param array $options (optional) Additional options pass to file while registering files
	 * @param callable|null $callback (optional) Apply callback on each registering file
	 * <code>
	 *    function (string &$fileUrl, string &$fileId, boolean $excluded, boolean $included ) {
	 *      // some logic here ...
	 *    }
	 * </code>
	 * @throws \yii\base\InvalidConfigException
	 */
	public function registerFilesAs ( $type, $list, array $options = [], callable $callback = \null ) {
		if ( !\is_string($type) || empty($type) ) {
			throw new InvalidParamException ("Type must be a string and cannot empty");
		}

		if ( \null === $list ) {
			$list = $this->getFiles();
		}

		/** @var \ArrayIterator $iterator */
		$iterator = new \ArrayIterator($list);

		if ( !$iterator->count() ) {
			throw new InvalidParamException ('List cannot be empty');
		}

		while ( $iterator->valid() ) {
			$file = $iterator->current();

			/** @var string $fileId */
			$fileId = \is_int($iterator->key()
				? \null
				: $iterator->key());

			if ( !$iterator->current() instanceof $this->fileClass ) {

				/** @var File $file */
				$file =  Yii::createObject($this->fileClass, [
					[
						'fileUrl' => $iterator->current(),
						'fileId' => $fileId
					]
				]);
			}

			if ( 'css' === $type ) {
				$file->registerAsCssFile($options);
			} else if ( 'js' === $type ) {
				$file->registerAsJsFile($options);
			}

			// Apply callback to each file
			if ( \is_callable($callback) ) {
				\call_user_func_array($callback, [$file->getUrl(), $options, $file->getId()] );
			}

			$iterator->next();
		}
	}
}
