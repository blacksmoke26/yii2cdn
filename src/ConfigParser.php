<?php
/**
 * @author Junaid Atari <mj.atari@gmail.com>
 * @link http://junaidatari.com Author Website
 * @see http://www.github.com/blacksmoke26/yii2-cdn
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 */

namespace yii2cdn;

use yii\base\InvalidConfigException;
use yii\base\InvalidParamException;
use yii\helpers\ArrayHelper;

/**
 * Class ComponentConfigParser
 * Parse the component configuration array into components
 *
 * @package common\yii2cdn
 * @author Junaid Atari <mj.atari@gmail.com>
 *
 * @access public
 * @version 0.2
 */
class ConfigParser {
	/**
	 * List of sections name
	 * @var array
	 */
	protected static $sections = [];

	/**
	 * Section options
	 * @var array
	 */
	protected static $sectionOptions = [];

	/**
	 * Component ID
	 * @var string
	 */
	protected $_id;

	/**
	 * CDN Base URL
	 * @var string
	 */
	protected $baseUrl;

	/**
	 * CDN Base Path
	 * @var string
	 */
	protected $basePath;

	/**
	 * Component Configuration
	 * @var array
	 */
	protected $config = [];

	/**
	 * CDN Custom aliases
	 * @var array
	 */
	protected $aliases = [];

	/**
	 * Component file name id Configuration
	 * @var array
	 */
	protected $fileIds = [];

	/**
	 * Files attributes
	 * @var array [ID=>Mixed-Value]
	 */
	protected $filesAttrs = [];

	/**
	 * Files attributes
	 * @var array [ID=>Mixed-Value]
	 */
	protected $_props = [];

	/**
	 * Predefined file attributes
	 * @var array
	 */
	protected $defFileAttrs = ['id', 'cdn', 'offline', 'options'];

	/**
	 * ComponentConfigParser constructor.
	 * @param $config Component Configuration
	 */
	public function __construct ( array $config ) {
		$this->_id = $config['id'];
		$this->baseUrl = $config['baseUrl'];
		$this->basePath = $config['basePath'];
		$this->config = $config['config'];
		static::$sections = $config['sections'];
		$this->_props['fileClass'] = $config['fileClass'];
		$this->_props['sectionClass'] = $config['sectionClass'];
		$this->aliases = $config['aliases'];
	}

	/**
	 * Get the components files list<br>
	 * Key=Value pairs of [COMPONENT_ID/SECTION_ID/FILE_ID]=>FILE_URL
	 * @param array $components Pre Build Components data
	 * @return array
	 */
	protected static function listFilesByRoot ( $components ) {
		if ( !\is_array ($components) || !\count ($components) ) {
			return $components;
		}

		/** @var array $filesId */
		$filesId = [];

		/** @var array $componentsUrl */
		$componentsUrl = [];

		foreach ( $components as $componentId => $sections ) {
			$componentsUrl[$componentId] = $sections['baseUrl'];

			foreach ( $sections as $sectionId => $data ) {
				if ( !\in_array ($sectionId, static::$sections, \true)
					|| (!\is_array ($data) || !\count ($data)) ) {
					continue;
				}

				foreach ( $data as $fileId => $fileName ) {
					if ( \false !== \strpos ($fileId, '*') ) {
						continue;
					}

					// File unique id
					$uid = "{$componentId}/{$sectionId}/" . $fileId;

					$filesId[$uid] = $fileName;
				}
			}
		}

		return [
			'filesId' => $filesId,
			'componentsUrl' => $componentsUrl,
		];
	}

	/**
	 * Replaces @component* tags from the components
	 * @see ComponentConfigParser::replaceComponentTagsFromFileName()
	 * @param array $components Pre Build Components data
	 * @return array Post components object
	 * @throws \yii\base\InvalidConfigException
	 */
	public static function touchComponentTags ( $components ) {
		if ( !\is_array ($components) || !\count ($components) ) {
			return $components;
		}

		/** @var array $reListed */
		$reListed = static::listFilesByRoot ( $components );

		foreach ( $components as $componentId => $sections ) {
			if ( !\is_array ($sections) || !\count ($sections) ) {
				continue;
			}

			foreach ( $sections as $sectionId => $data ) {
				if ( !\in_array ($sectionId, static::$sections, \true)
					|| (!\is_array ($data) || !\count ($data)) ) {
					continue;
				}

				foreach ( $data as $fileId => $props ) {
					/** @var array $file */
					$file = $props;

					if ( \preg_match ( '/^@component([A-Za-z]+)/i', $file['url'] ) > 0 ) {
						/** @var array|false $file */
						$file = static::replaceComponentTags ( $file, $reListed );

						if ( \false === $file ) {
							continue;
						}
					}

					// update file properties
					$components[$componentId][$sectionId][$fileId] = $file;
				}
			}
		}

		return $components;
	}

	/**
	 * Replaces @component* tags (case insensitive) from given filename
	 * Tags (starts with @component)<br>
	 * <code>
	 *    > componentUrl(ID)
	 *    > componentFile(ID/SECTION/FILE_ID)
	 * </code>
	 *
	 * @param string $fileName File name replace from
	 * @param array $indexed Indexed data object
	 * @return array Replaced tags object
	 * @throws \yii\base\InvalidConfigException
	 */
	protected static function replaceComponentTags ( $file, array $indexed ) {
		/** @var string $fileName */
		$fileName = $file['url'];

		// tag: componentFile(ID/SECTION/FILE_ID)
		if ( \preg_match ('/^@(?i)componentFile(?-i)\(([^\)]+)\)$/', $fileName, $match) ) {
			if ( !\array_key_exists ($match[1], $indexed['filesId']) ) {
				throw new InvalidConfigException ("Unknown CDN component file id '{$match[1]}' given");
			}

			list($componentId, $sectionId) = \explode ('/', $match[1]);

			return \array_merge ($indexed['filesId'][$match[1]], [
				'_component' => $componentId,
				'_section' => $sectionId,
			]);
		}

		// tag: componentUrl(ID)
		if ( \preg_match('/^@(?i)componentUrl(?-i)\(([^\)]+)\)(.+)$/', $fileName, $match) ) {
			if ( !\array_key_exists ( $match[1], $indexed['componentsUrl'] ) ) {
				throw new InvalidConfigException ( "Unknown CDN component id '{$match[1]}' given" );
			}
			return [
				'file' => $match[2],
				'url' => $indexed['componentsUrl'][$match[1]]
					.  '/' . \ltrim($match[2], '/'),
			];
		}

		return [];
	}

	/**
	 * Get the parsed configuration
	 *
	 * @return array|null Component config | null when skipped
	 * @throws \yii\base\InvalidConfigException
	 */
	public function getParsed () {
		if ( \true === $this->getAttrOffline () && Cdn::isOnline () ) {
			return \null;
		}

		/** @var array $config */
		$config = [
			'id' => $this->_id,
			'baseUrl' => $this->getUrl (),
			'componentAttributes' => $this->getAttrAttributes(),
			'basePath' => $this->basePath . DIRECTORY_SEPARATOR
				. ($this->getAttrSrc() ? $this->getAttrSrc() : $this->_id),
			'sectionClass' => $this->_props['sectionClass'],
			'fileClass' => $this->_props['fileClass'],
			'sections' => static::$sections,
		];

		/** @var array $offlineSections */
		$offlineSections = $this->getAttrOfflineSections ();

		// Validate section names if given
		if ( \is_array($offlineSections) && \count ( $offlineSections ) ) {
			foreach ( $offlineSections as $sect ) {
				if ( !\in_array ( $sect, static::$sections, true ) ) {
					throw new InvalidConfigException ( "Offline Section '{$sect}' name doesn't exist" );
				}
			}
		}

		foreach ( static::$sections as $section ) {
			if ( \in_array ( $section, $offlineSections, \true ) && Cdn::isOnline () ) {
				continue;
			}

			// Array of section files
			$config[$section] = $this->getFilesBySection ( $section );
		}

		$config['fileAttrs'] = $this->filesAttrs;
		$config['sectionsAttributes'] = static::$sectionOptions;

		return $config;
	}

	/**
	 * Get @src attribute value (empty when not exist/null)
	 * @return string
	 */
	protected function getUrl () {
		/** @var string $attrBaseUrl */
		$attrBaseUrl = $this->getAttrBaseUrl ();
		/** @var string $attrSrc */
		$attrSrc = $this->getAttrSrc ();

		/** @var string $baseUrl */
		$baseUrl = empty($attrBaseUrl)
			? $this->baseUrl : $attrBaseUrl;

		$baseUrl .= empty($attrSrc)
			? '/' . $this->_id : '/' . $attrSrc;

		return $baseUrl;
	}

	/**
	 * Get @offline attribute value (empty when not exist/null)
	 * @return boolean
	 */
	protected function getAttrOffline () {
		return \array_key_exists('@offline', $this->config)
		&& !empty($this->config['@offline'])
			? (bool) $this->config['@offline']
			: \false;
	}

	/**
	 * Get @baseUrl attribute value (empty when not exist/null)
	 * @return string
	 */
	protected function getAttrBaseUrl () {
		return \array_key_exists('@baseUrl', $this->config)
		&& !empty($this->config['@baseUrl'])
			? \trim( $this->config['@baseUrl'] )
			: '';
	}

	/**
	 * Get @attributes attribute value (empty when not exist/null)
	 * @return array
	 */
	protected function getAttrAttributes () {
		return \array_key_exists ('@attributes', $this->config)
		&& \is_array ($this->config['@attributes'])
		&& !empty($this->config['@attributes'])
			? $this->config['@attributes']
			: [];
	}

	/**
	 * Get @src attribute value (empty when not exist/null)
	 * @return string
	 */
	protected function getAttrSrc () {
		return \array_key_exists ( '@src', $this->config )
		&& !empty( $this->config['@src'] )
			? \trim ( $this->config['@src'] )
			: '';
	}

	/**
	 * Get @offlineSections attribute value (empty when not exist/null)
	 * @return array
	 */
	protected function getAttrOfflineSections () {
		if ( !\array_key_exists ('@offlineSections', $this->config) ) {
			return [];
		}

		/** @var array $collection */
		$collection = $this->config['@offlineSections'];

		if ( !\is_array ($collection) ) {
			throw new InvalidParamException ('Parameter @offlineSections must be an array');
		}

		return $this->config['@offlineSections'];
	}

	/**
	 * Get the current component's inheritable section/file attributes
	 * @param string $type The section name (or type)
	 * @param bool $asFileAttrs (optional) Returns file attributes list
	 * @return array The key=>value pair of attributes
	 */
	protected function getInheritableAttrs ( $type, $asFileAttrs = \false ) {
		/** @var array $compAttrs */
		$compAttrs = $this->getAttrAttributes ();

		if ( isset($compAttrs['@sectionsAttrs'])
			&& !\is_array ($compAttrs['@sectionsAttrs']) ) {
			throw new InvalidParamException ('Parameter @sectionsAttrs must be an array');
		}

		if ( !isset($compAttrs['@sectionsAttrs'])
			|| !\is_array ($compAttrs['@sectionsAttrs'])
			|| !\count ($compAttrs['@sectionsAttrs']) ) {
			return [];
		}

		/** @var array $sectAttrs */
		$sectAttrs = $compAttrs['@sectionsAttrs'];

		// Section attributes only
		if ( !$asFileAttrs ) {
			unset($sectAttrs['@filesAttrs']);
			return $sectAttrs;
		}

		// File attributes only
		if ( isset($sectAttrs['@filesAttrs'])
			&& !\is_array ($sectAttrs['@filesAttrs']) ) {
			throw new InvalidParamException ('Parameter @filesAttrs must be an array');
		}

		if ( !isset($sectAttrs['@filesAttrs'])
			|| !\is_array ($sectAttrs['@filesAttrs'])
			|| !\count ($sectAttrs['@filesAttrs']) ) {
			return [];
		}

		return $sectAttrs['@filesAttrs'];
	}

	/**
	 * Get the files of section by name
	 * @param $type string Section name to get
	 * @return array
	 * @throws \yii\base\InvalidParamException
	 * @throws \yii\base\InvalidConfigException
	 */
	protected function getFilesBySection( $type ) {
		if ( !\in_array($type, static::$sections, \true)
			|| !isset($this->config[$type])
			|| !\is_array($this->config[$type])
			|| empty($this->config[$type]) ) {
			return [];
		}

		/** @var array $list */
		$list = [];
		static::$sectionOptions[$type] = [];

		foreach ( $this->config[$type] as $tag => $file ) {
			// Check if element contains section attributes
			if ( '@attributes' === $tag ) {

				if ( !\is_array($file) ) {
					throw new InvalidParamException ('@attributes tag value should be an array.');
				}

				static::$sectionOptions[$type] = (array) $file;
				continue;
			}

			/** @var array $op */
			$op = $this->getFileName($file, $type, $tag);

			if ( \null === $op ) {
				continue;
			}

			/** @var string $_id */
			$_id = \key($op);

			$list[$_id] = $op[$_id];
		}

		return $list;
	}

	/**
	 * Get a file id and name
	 * @param string|array $file File name | file object
	 * @param string $type Section name
	 * @param string $tag (optional) Section attribute tag name
	 * @throws \yii\base\InvalidParamException when File first param must not string or empty
	 * @throws \yii\base\InvalidParamException when File attribute param not string or empty
	 * @return array|null Key=>Value pair (ID=>FILENAME) / File skipped
	 * @throws \yii\base\InvalidConfigException
	 */
	protected function getFileName ( $file, $type, $tag = \null ) {
		if ( is_string($file) ) {
			/** @var string $uniqueId */
			$uniqueId = \uniqid('*', \false);

			if ( preg_match ( '/^@component([A-Za-z]+)/i', $file ) > 0 ) {
				return [$uniqueId => [
					'file' => \null,
					'url' => $file
				]];
			}

			return [$uniqueId => [
				'file' => \ltrim(
					\preg_replace('/^@[a-zA-Z]+/', '', $file)
					, '\\/'
				),
				'url' => $this->replaceFileNameTags($file, $type)
			]
			];
		}

		if ( empty($file[0]) || !\is_string($file[0]) ) {
			throw new InvalidParamException ('File first param must be string and not empty');
		}

		/** @var array $params */
		$params = ['cdn', 'id'];

		foreach ($params as $p ) {
			if ( !empty($file['@'.$p]) && !\is_string($file['@'.$p]) ) {
				throw new InvalidParamException ("File @{$p} param must be string and cannot be emptied");
			}
		}

		if ( \array_key_exists('@offline', $file)
			&& \false !== $file['@offline'] && Cdn::isOnline() ) {
			return \null;
		}

		// Check file @cdn exist, use that version,
		$filename = \array_key_exists('@cdn', $file) && Cdn::isOnline()
			? $file['@cdn']
			: $this->replaceFileNameTags($file[0], $type); // use offline version

		// Check file ID, if doesn't exist, assign a unique id
		$fileId = \array_key_exists('@id', $file)
			? \trim($file['@id'])
			: (string) uniqid('*', \false);

		if ( \array_key_exists('@options', $file) ) {
			if ( !\is_array($file['@options']) ){
				throw new InvalidParamException ( "File @options param should be an array" );
			}

			$this->filesAttrs[$type]["@options/$fileId"] = $file['@options'];
		}

		$attributes = \preg_grep( '/^[a-zA-Z]+$/i', \array_keys($file) );

		if ( \is_array($attributes) && \count($attributes) ) {
			foreach ( $attributes as $attr => $val ) {
				if ( \in_array($attr, $this->defFileAttrs, \true)) {
					continue;
				}

				$this->filesAttrs[$type]["$val/$fileId"] = $file[$val];
			}
		}

		return [$fileId => [
			'file' => $file[0],
			'url' => $filename
		]];
	}

	/**
	 * Replaces tags (starts with @) from file name<br><br>
	 * Rules: (no url appends at beginning if)<br>
	 * <code>
	 *    > An actual url
	 *    > starts with //
	 * </code><br>
	 * Supported tags (case insensitive)<br>
	 * <code>
	 *    > @appUrl : current application url
	 *    > @baseUrl : component base url
	 *    > @thisSectionUrl : parent section base url
	 *    > @thisSectionPath : parent section base path
	 *    > @url (*) : * = any url ends with /
	 *    > @alias (*) : * = CDN custom alias name
	 *    > @yiiAlias (*) : * = Yii alias name
	 * </code><br>
	 * @param string $fileName Replace tags from filename
	 * @param string $type The section name (or type)
	 * @return string
	 * @throws \yii\base\InvalidConfigException
	 */
	protected function replaceFileNameTags ( $fileName, $type ) {
		if ( 0 === \strpos( $fileName, '//' )
			|| \filter_var($fileName, \FILTER_VALIDATE_URL )) {
			return $fileName;
		}

		// Replace tags
		if ( \false !== \strpos($fileName, '@') ) {
			$patterns = [
				// tag: @thisComponentUrl
				'/^((?i)@thisComponentUrl(?-i))(.+)$/' => function ( $match ) use ( $type ) {
					return $this->getUrl () . '/'
						. \ltrim ($match[2], '/');
				},

				// tag: @thisSectionUrl
				'/^((?i)@thisSectionUrl(?-i))(.+)$/' => function ( $match ) use ( $type ) {
					return $this->getSectionUrl ($type, $match[2]);
				},

				// tag: @alias(*)
				'/^(?i)@alias(?-i)\(([^\)]+)\)(.+)?$/' => function ( $match ) {
					if ( !\array_key_exists ($match[1], $this->aliases) ) {
						throw new InvalidConfigException ("Invalid custom url alias '{$match[1]}' given");
					}

					return $this->aliases[$match[1]]
						. '/' . \ltrim ($match[2], '/');
				},

				// tag: @yiiAlias(*)
				'/^(?i)@yiiAlias(?-i)\(([^\)]+)\)(.+)$/' => function ( $match ) {
					return \Yii::getAlias ($match[1])
						. '/' . \ltrim ($match[2], '/');
				},

				// tag: @url(*)
				'/^(?i)@url(?-i)\(([^\)]+)\)(.+)$/' => function ( $match ) {
					return $match[1]
						. '/' . \ltrim ($match[2], '/');
				},

				// tag: @appUrl
				'/^((?i)@appUrl(?-i))(.+)$/' => function ( $match ) {
					return $this->baseUrl
						. '/' . \ltrim ($match[2], '/');
				},

				// tag: @baseUrl
				'/^((?i)@baseUrl(?-i))(.+)$/' => function ( $match ) {
					return $this->getUrl ()
						. '/' . \ltrim ($match[2], '/');
				},
			];

			/** @var string $output */
			$output = \rtrim(preg_replace_callback_array($patterns, $fileName), '/');

			// tag: @*** remove
			return \preg_replace(
				'/^(@[a-zA-Z]+\([a-zA-Z0-9_]+\)|@([a-zA-Z]+))/',
				'',
				$output
			);
		}

		return $this->getSectionUrl($type, $fileName);
	}

	/**
	 * Get the section url
	 * @param string $type The section name (or type)
	 * @param string|null $fileName (optional) filename append at ned
	 * @param array $attributes (optional) Section attributes
	 * @return string The final url
	 * @throws \yii\base\InvalidConfigException when all of the attributes aren't valid
	 */
	protected function getSectionUrl ( $type, $fileName = \null, array $attributes = [] ) {
		/** @var array $_attributes */
		$_attributes = ArrayHelper::merge(
			$this->getInheritableAttrs($type),
			(array) static::$sectionOptions[$type],
			$attributes
		);

		// (@noNameInPathUrls) Source directory url
		$noNameInPathUrls = isset($_attributes['noNameInPathUrls']) ?
			(bool) $_attributes['noNameInPathUrls']
			: \false;

		// Base Url
		if ( isset($_attributes['baseUrl']) ) {
			if ( !\is_string($_attributes['baseUrl']) || !\trim($_attributes['baseUrl']) ) {
				throw new InvalidConfigException("Section `{$type}`'s `baseUrl` attribute is not valid ");
			}

			return \rtrim($_attributes['baseUrl'], '/')
				. ( $fileName ? '/'. \ltrim($fileName, '/') : '' );
		}

		// (@src) Source directory url
		if ( isset($_attributes[$type]['src']) ) {
			if ( !\is_string($_attributes['src'])
				|| !\trim($_attributes['src']) ) {
				throw new InvalidConfigException("Section `{$type}`'s `src` attribute is not valid ");
			}

			/** @var string $baseUrl */
			$baseUrl = \rtrim($this->getUrl(), '/')
				. ( $noNameInPathUrls
					? ''
					: '/' . \ltrim($_attributes['src'], '/')
				);

			return $baseUrl
				. ( $fileName
					? '/'. \ltrim($fileName, '/')
					: ''
				);
		}

		// Section type name url
		return \rtrim($this->getUrl(), '/') . "/"
			. ( $noNameInPathUrls ? '' : "{$type}/")
			. \ltrim($fileName, '/');
	}
}
