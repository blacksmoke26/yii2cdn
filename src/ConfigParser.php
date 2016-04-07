<?php

/**
 * @copyright Copyright (c) 2016 Junaid Atari
 * @link http://junaidatari.com Website
 * @see http://www.github.com/blacksmoke26/yii2-cdn
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 */

namespace yii2cdn;

use yii\base\InvalidConfigException;
use yii\base\InvalidParamException;

/**
 * Class ComponentConfigParser
 * Parse the component configuration array into components
 * 
 * @package common\yii2cdn
 * @author Junaid Atari <mj.atari@gmail.com>
 *
 * @access public
 * @version 0.1
 */
class ConfigParser
{
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
	 * List of sections name
	 * @var array
	 */
	protected static $sections = [];

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
		$this->config = $config['config'];
		self::$sections = $config['sections'];
		$this->_props['fileClass'] = $config['fileClass'];
		$this->_props['sectionClass'] = $config['sectionClass'];
	}

	/**
	 * Get @src attribute value (empty when not exist/null)
	 * @return string
	 */
	protected function getAttrSrc ()
	{
		return array_key_exists('@src', $this->config) && !empty($this->config['@src'])
			? trim($this->config['@src'])
			: '';
	}

	/**
	 * Get @offline attribute value (empty when not exist/null)
	 * @return string
	 */
	protected function getAttrOffline ()
	{
		return array_key_exists('@offline', $this->config) && !empty($this->config['@offline'])
			? boolval($this->config['@offline'])
			: false;
	}

	/**
	 * Get @offlineSections attribute value (empty when not exist/null)
	 * @return array
	 */
	protected function getAttrOfflineSections ()
	{
		if ( !array_key_exists('@offlineSections', $this->config) ) {
			return [];
		}

		$lst = $this->config['@offlineSections'];

		if ( !is_array($lst) ) {
			throw new InvalidParamException ('Parameter @offlineSections must be an array');
		}

		return $this->config['@offlineSections'];
	}

	/**
	 * Get @baseUrl attribute value (empty when not exist/null)
	 * @return string
	 */
	protected function getAttrBaseUrl ()
	{
		return array_key_exists('@baseUrl', $this->config) && !empty($this->config['@baseUrl'])
			? trim($this->config['@baseUrl'])
			: '';
	}

	/**
	 * Get @src attribute value (empty when not exist/null)
	 * @return string
	 */
	protected function getUrl ()
	{
		$attrBaseUrl = $this->getAttrBaseUrl();
		$attrSrc = $this->getAttrSrc();

		$baseUrl = empty($attrBaseUrl) ? $this->baseUrl : $attrBaseUrl;
		$baseUrl .= empty($attrSrc) ? '/'.$this->_id : '/'.$attrSrc;

		return $baseUrl;
	}

	/**
	 * Get the files of section by name
	 * @param $type string Section name to get
	 * @return array
	 */
	protected function getFilesBySection( $type ) {
		if ( !in_array($type, self::$sections) || !isset($this->config[$type])
			|| !is_array($this->config[$type]) || empty($this->config[$type]) ) {
			return [];
		}

		$list = [];

		foreach ( $this->config[$type] as $file ) {

			$op = $this->getFileName($file, $type);

			if ( $op === null ) {
				continue;
			}

			$_id = key($op);

			$list[$_id] = $op[$_id];
		}

		return $list;
	}

	/**
	 * Get the file id and name
	 * @param string|array $file File name | file object
	 * @param string $type Section name
	 * @throws \yii\base\InvalidParamException when File first param must not string or empty
	 * @throws \yii\base\InvalidParamException when File attribute param not string or empty
	 * @return array|null Key=>Value pair (ID=>FILENAME) / File skipped
	 */
	protected function getFileName ( $file, $type ) {

		if ( !is_array($file) || is_string($file) ) {
			return [  uniqid('*') => $this->replaceFileNameTags($file) ];
		}

		if ( empty($file[0]) || !is_string($file[0]) ) {
			throw new InvalidParamException ('File first param must be string and not empty');
		}

		$params = ['cdn', 'id'];

		foreach ($params as $p ) {
			if ( !empty($file['@'.$p]) && !is_string($file['@'.$p]) ) {
				throw new InvalidParamException ("File @{$p} param must be string and not empty");
			}
		}

		if ( array_key_exists('@offline', $file) && $file['@offline'] !== false && Cdn::isOnline() ) {
			return null;
		}

		// Check file @cdn exist, use that version,
		$filename = array_key_exists('@cdn', $file) && Cdn::isOnline()
			? $file['@cdn']
			: $this->replaceFileNameTags($file[0]); // use offline version

		// Check file ID, if doesn't exist, assign a unique id
		$fileId = array_key_exists('@id', $file)
			? trim($file['@id'])
			: (string)uniqid('*');

		if ( array_key_exists('@options', $file) ) {
			if ( !is_array($file['@options']) || !count($file['@options']) ){
				throw new InvalidParamException ( "File @options param must be an array and should not be empty" );
			}

			$this->filesAttrs[$type]["@options/$fileId"] = $file['@options'];
		}

		$attributes = preg_grep( '/^[a-zA-Z]+$/i', array_keys($file) );
		
		if ( count($attributes) ) {
			foreach ( $attributes as $attr => $val ) {
				if ( in_array($attr, $this->defFileAttrs)) {
					continue;
				}

				$this->filesAttrs[$type]["$val/$fileId"] = $file[$val];
			}
		}

		return [ $fileId => $filename ];
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
	 *    > appUrl : current application url
	 *    > baseUrl : component base url
	 *    > url (*) : * = any url ends with /
	 *    > alias (*) : * = CDN custom alias name
	 *    > yiiAlias (*) : * = Yii alias name
	 * </code><br>
	 * @param string $fileName Replace tags from filename
	 * @return string
	 */
	protected function replaceFileNameTags ( $fileName ) {
		if ( \substr( $fileName, 0, 2 ) === '//'
			|| \filter_var($fileName, \FILTER_VALIDATE_URL )) {
			return $fileName;
		}

		// Replace tags
		if ( strstr($fileName, '@') !== false ) {

			$patterns = [

				// tag: @alias(*)
				'/^(?i)@alias(?-i)\(([^\)]+)\)(.+)$/' => function ($match) {
					if (!array_key_exists($match[1], $this->aliases) ) {
						throw new InvalidConfigException ("Invalid custom url alias '{$match[1]}' given");
					}

					return \Yii::getAlias($match[1]). (substr($match[2],0,1) !== '/' ? '/'.$match[2] : $match[2]);
				},

				// tag: @yiiAlias(*)
				'/^(?i)@yiiAlias(?-i)\(([^\)]+)\)(.+)$/' => function ($match) {
					return \Yii::getAlias($match[1]). (substr($match[2],0,1) !== '/' ? '/'.$match[2] : $match[2]);
				},

				// tag: @url(*)
				'/^(?i)@url(?-i)\(([^\)]+)\)(.+)$/' => function ($match) {
					return $match[1]. (substr($match[2],0,1) !== '/' ? '/'.$match[2] : $match[2]);
				},

				// tag: @appUrl
				'/^((?i)@appUrl(?-i))(.+)$/' => function ($match) {
					return \Yii::$app->request->baseUrl . $match[2];
				},

				// tag: @baseUrl
				'/^((?i)@baseUrl(?-i))(.+)$/' => function ($match) {
					return $this->getUrl() . $match[2];
				},
			];

			return preg_replace_callback_array($patterns, $fileName);
		}

		return rtrim($this->getUrl(), '/') . "/" . ltrim($fileName, '/');
	}

	/**
	 * Get the parsed configuration
	 * @return array|null Component config | null when skipped
	 */
	public function getParsed () {

		if ( $this->getAttrOffline() === true && Cdn::isOnline() ) {
			return null;
		}

		$config = [
			'id' => $this->_id,
			'baseUrl' => $this->getUrl(),
			'sectionClass' => $this->_props['sectionClass'],
			'fileClass' => $this->_props['fileClass'],
			'sections' => self::$sections,
		];

		// Validate section names if given
		if ( count($offlineSections = $this->getAttrOfflineSections()) ) {
			foreach ( $offlineSections as $sect ) {
				if (!in_array($sect, self::$sections )) {
					throw new InvalidConfigException ( "Offline Section '{$sect}' name doesn't exist" );
				}
			}
		}

		foreach ( self::$sections as $section ) {

			if ( in_array($section, $offlineSections ) && Cdn::isOnline () ) {
				continue;
			}

			$config[$section] = $this->getFilesBySection($section);
		}

		$config['fileAttrs'] = $this->filesAttrs;

		return $config;
	}

	/**
	 * Replaces @component* tags from the components
	 * @see ComponentConfigParser::replaceComponentTagsFromFileName()
	 * @param array $components Pre Build Components data
	 * @return array Post components object
	 */
	public static function touchComponentTags ( $components )
	{
		if ( !count($components ) ) {
			return $components;
		}

		# $reListed['filesId'], $reListed['componentsUrl']
		$reListed = self::listFilesByRoot($components);

		foreach ( $components as $componentId => $sections ) {
			foreach ( $sections as $sectionId => $data ) {
				if ( !in_array($sectionId, self::$sections) || !count($data) ) {
					continue;
				}

				foreach ($data as $fileId => $fileName ) {

					$nFileName = !preg_match('/^@component([A-Za-z]+)/i', $fileName)
						? $fileName
						: self::replaceComponentTagsFromFileName($fileName, $reListed);

					$components[$componentId][$sectionId][$fileId] = $nFileName; //str_replace('//', '/', $nFileName);
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
	 * @param string $fileName File name replace from
	 * @param array $indexed Indexed data object
	 * @return array Replaced tags object
	 */
	protected static function replaceComponentTagsFromFileName ( $fileName, array $indexed ) {

		$patterns = [

			// tag: componentUrl(ID)
			'/^@(?i)componentUrl(?-i)\(([^\)]+)\)(.+)$/' => function($match) use ($indexed) {

				if ( !array_key_exists($match[1], $indexed['componentsUrl']) ) {
					throw new InvalidConfigException ("Unknown CDN component id '{$match[1]}' given");
				}

				return $indexed['componentsUrl'][$match[1]]
					. (substr($match[2],0,1) !== '/' ? '/'.$match[2] : $match[2]);
			},

			// tag: componentFile(ID/SECTION/FILE_ID)
			'/^@(?i)componentFile(?-i)\(([^\)]+)\)$/' => function($match) use ($indexed) {

				if ( !array_key_exists($match[1], $indexed['filesId']) ) {
					throw new InvalidConfigException ("Unknown CDN component file id '{$match[1]}' given");
				}

				return $indexed['filesId'][$match[1]];
			},
		];

		return preg_replace_callback_array($patterns, $fileName);
	}

	/**
	 * Get the components files list<br>
	 * Key=Value pair of [COMPONENT_ID/SECTION_ID/FILE_ID]=>FILE_URL
	 * @param array $components Pre Build Components data
	 * @return array
	 */
	protected static function listFilesByRoot ( $components )
	{
		if ( !count($components ) ) {
			return $components;
		}

		$filesId = [];
		$componentsUrl = [];

		foreach ( $components as $componentId => $sections ) {

			$componentsUrl[$componentId] = $sections['baseUrl'];

			foreach ( $sections as $sectionId => $data ) {
				if ( !in_array($sectionId, self::$sections) || !count($data) ) {
					continue;
				}

				foreach ( $data as $fileId => $fileName ) {

					if ( strstr($fileId, '*') !== false ) {
						continue;
					}

					// File unique id
					$uid = "{$componentId}/{$sectionId}/".$fileId;

					$filesId[$uid] = $fileName;
				}
			}
		}

		return [
			'filesId' => $filesId,
			'componentsUrl' => $componentsUrl
		];
	}
}
