<?php
/**
 * @author Junaid Atari <mj.atari@gmail.com>
 * @link http://junaidatari.com Author Website
 * @see http://www.github.com/blacksmoke26/yii2-cdn
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 */

namespace yii2cdn;

use Yii;
use yii\base\InvalidParamException;
use yii\base\InvalidValueException;
use yii\base\UnknownPropertyException;
use yii\helpers\ArrayHelper;
use yii2cdn\traits\Attributes;
use yii2cdn\traits\File;
use yii2cdn\traits\Url;

/**
 * Yii2 CDN Component object
 *
 * @package yii2cdn
 * @author Junaid Atari <mj.atari@gmail.com>
 *
 * @access public
 * @version 0.2
 */
class Component {
	/**
	 * Used traits
	 */
	use Url;
	use File;
	use Attributes;

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
	 * Component's base path
	 * @var string
	 */
	protected $basePath;

	/**
	 * Component attributes
	 * @var array
	 */
	protected $attributes = [];

	/**
	 * Sections list
	 * @var Section[]
	 */
	protected $sections = [];

	/**
	 * Class constructor
	 * @param string $id Component ID
	 * @param array $config Configuration
	 * @throws \yii\base\InvalidConfigException
	 * @throws \yii\base\UnknownPropertyException
	 */
	public function __construct ( array $config ) {
		$this->baseUrl = $config['baseUrl'];
		$this->basePath = $config['basePath'];
		$this->attributes = (array) $config['componentAttributes'];
		$this->id = $config['id'];
		$this->buildSections($config);
	}

	/**
	 * Create the component section object from config<br>
	 * Note: Empty sections will be removed
	 *
	 * @param array $config Configuration object
	 * @throws \yii\base\UnknownPropertyException
	 * @throws \yii\base\InvalidConfigException
	 */
	protected function buildSections ( array $config ) {
		foreach ( $config['sections'] as $name ) {
			if ( !\array_key_exists($name, $config)
				|| empty($config[$name]) ) {
				continue;
			}

			/** @var boolean $noNameInPathUrls */
			$noNameInPathUrls = \false;

			/// Section attributes
			/** @var array $sectAttributes */
			$sectAttributes = (array) $config['sectionsAttributes'][$name];

			// Defined all sections attributes
			if ( \null !== $this->getAttr('@sectionsAttrs') ) {
				$sectAttributes = ArrayHelper::merge(
					(array) $this->getAttr('@sectionsAttrs'),
					$sectAttributes
				);
			}

			/** @var array $faName */
			$faName = isset($config['fileAttrs'][$name])
				? $config['fileAttrs'][$name]
				: [];

			/** @var array $_attributes */
			$_attributes = isset($config['fileAttrs'])
				? $faName
				: [];

			// (@hideNameInPathUrls) Source directory url
			if ( isset($sectAttributes['noNameInPathUrls']) ) {
				$noNameInPathUrls = $sectAttributes['noNameInPathUrls'];
			}

			/** @var string $basePath */
			$basePath = isset($sectAttributes['src']) && \trim($sectAttributes['src'])
				? \rtrim($this->basePath, '\\/')
					. \DIRECTORY_SEPARATOR . \ltrim($sectAttributes['src'],'\\/')
				: $this->basePath . (
					!$noNameInPathUrls
						? \DIRECTORY_SEPARATOR . $name
						: ''
				);

			/** @var string $baseUrl */
			$baseUrl = isset($sectAttributes['src']) && \trim($sectAttributes['src'])
				? \rtrim($this->baseUrl, '/')
					. '/' . \ltrim($sectAttributes['src'],'\\/')
				: $this->baseUrl . (
					!$noNameInPathUrls
						? "/{$name}"
						: ''
				);

			// Create section(s) component
			/** @var Section $section */			
			$this->sections[$name] = Yii::createObject($config['sectionClass'], [[
				'component' => $this->id,
				'section' =>$name,
				'files' => $config[$name],
				'baseUrl' => $baseUrl,
				'basePath' => \str_replace('/', DIRECTORY_SEPARATOR, $basePath),
				'attributes' => $sectAttributes,
				'fileAttributes' => $_attributes,
				'fileClass' => $config['fileClass'],
				'preComponents' => $config['preComponents']
			]]);

			unset($config['preComponents']);
		}

		// Remove sections attributes
		unset($this->attributes['@sectionsAttrs']);
	}

	/**
	 * Get a javaScript files list
	 * @param boolean $asUrl (optional) True will return files url only (default: false)
	 * @param boolean $unique (optional) True will remove duplicate elements (default: true)
	 * @return array List of js section files
	 * @throws \yii\base\UnknownPropertyException
	 */
	public function getJsFiles ( $asUrl = \false, $unique = \true ) {
		/** @var array|null|\yii2cdn\File[] $files */
		$files = $this->getSection ('js')
			->getFiles (\true, $unique);

		if ( !$asUrl ) {
			return $files;
		}

		return \array_values ( $files );
	}

	/**
	 * Get a section(s) object by ID
	 * @param string|array Section name|List of sections name
	 * @param boolean $throwException (optional) True will throw exception when section name not found (default: true)
	 * @throws \yii\base\InvalidParamException When null given as section
	 * @throws \yii\base\UnknownPropertyException When section name not found
	 * @return Section|array|null Section | Sections List | Null when not found
	 */
	public function getSection ( $name, $throwException = \true ) {
		/** @var array $sections */
		$sections = $name;

		if ( !\is_array($name) ) {
			$sections = [$name];
		}

		/** @var array $list */
		$list = [];

		foreach ( $sections as $section ) {
			if ( !$this->sectionExists($section, $throwException ) ) {
				continue;
			}

			$list[] = $this->sections[$section];
		}

		return 1 == \count($list)
			? \array_shift($list)
			: $list;
	}

	/**
	 * Check that given section exists
	 * @param string $name Section name to check
	 * @param boolean $throwException (optional) True will throw exception when section name not found (default: false)
	 * @throws \yii\base\InvalidValueException When section name is empty
	 * @throws \yii\base\UnknownPropertyException When section name not found
	 * @return True when exist | False otherwise
	 */
	public function sectionExists ( $name, $throwException = \true ) {
		if ( empty($name) ) {
			if ( $throwException ) {
				throw new InvalidValueException ('Section name cannot be empty');
			}

			return \false;
		}

		if ( !\array_key_exists($name, $this->sections) ) {
			if ( $throwException ) {
				throw new UnknownPropertyException ("Section '{$name}' not found");
			}

			return \false;
		}

		return \true;
	}

	/**
	 * Get style files list
	 * @param boolean $asUrl (optional) True will return files url only (default: false)
	 * @param boolean $unique (optional) True will remove duplicate elements (default: true)
	 * @return array List of css section files
	 * @throws \yii\base\UnknownPropertyException
	 */
	public function getCssFiles ( $asUrl = \true, $unique = \true ) {
		/** @var array|null|\yii2cdn\File[] $files */
		$files = $this->getSection('css')->getFiles(\true, $unique);

		if ( !$asUrl ) {
			return $files;
		}

		return \array_values($files);
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
	 * @throws \yii\base\UnknownPropertyException
	 */
	public function register ( array $cssOptions = [], array $jsOptions = [], callable $callback = \null ) {
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
	 * @throws \yii\base\UnknownPropertyException
	 */
	public function registerCssFiles( array $options = [], callable $callback = \null ) {
		if ( $this->sectionExists('css', \false ) ) {
			$this->getSection('css')
				->registerFilesAs ('css', \null, $options, $callback );
		}
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
	 * @throws \yii\base\UnknownPropertyException
	 */
	public function registerJsFiles( array $options = [], callable $callback = \null ) {
		if ( $this->sectionExists('js', \false ) ) {
			$this->getSection('js')->registerFilesAs('js', \null, $options, $callback );
		}
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
	public function getFileByRoot ( $root, $asUrl = \false, $throwException = \true ) {
		// validate the root
		if ( !\is_string($root) || 1 != \substr_count($root, '/') ) {
			throw new InvalidParamException ("Invalid root '{$root}' given");
		}

		/** @var string $sectionId */
		/** @var string $fileId */
		list ($sectionId, $fileId) = \explode('/', $root);

		return $this->getSection($sectionId, $throwException)
			->getFileById ($fileId, $asUrl, $throwException);
	}
}
