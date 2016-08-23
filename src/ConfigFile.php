<?php

/**
 * @copyright Copyright (c) 2016 Junaid Atari
 * @link http://junaidatari.com Website
 * @see http://www.github.com/blacksmoke26/yii2-cdn
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 */

namespace yii2cdn;

/**
 * CDN Configuration File handler
 *
 * @package yii2cdn
 * @author Junaid Atari <mj.atari@gmail.com>
 *
 * @access public
 * @version 0.1
 */
class ConfigFile {

	/**
	 * Absolute config file path
	 * @var string
	 */
	protected $path;

	/**
	 * Component Configuration
	 * @var array
	 */
	protected $config = [];

	/**
	 * File is offline or not
	 * @var boolean
	 */
	protected $offline = false;

	/**
	 * Constructor function
	 * @param object|string $config CDN Config
	 */
	public function __construct ( $config ) {
		if ( isset($config['path']) && !empty($config['path']) ) {
			$this->load($config['path']);
			unset ($config['path']);
		}

		$this->config = $config;
	}

	/**
	 * Load the CDN config
	 * @param string|object $config CDN Config
	 */
	protected function load ( $config ) {
		if ( is_string($config) ) {
			$this->path = $config;
			return;
		}

		$this->path = $config[0];
		$this->offline = array_key_exists( 'offline', $config )
			&& $config['offline'];
	}

	/**
	 * Get the configuration
	 * @return array
	 */
	protected function getListOf () {
		if ( Cdn::isOnline() && $this->offline ) {
			return [];
		}

		/** @var ConfigLoader $loader */
		$loader = \Yii::createObject( $this->config['configLoaderClass'] );
		$loader->online($this->getPath());

		return $loader->asArray();

	}

	/**
	 *  Get config file real path
	 * @return string
	 */
	public function getPath() {
		return \Yii::getAlias( $this->path );
	}

	/**
	 * Get the list of cdn components configuration
	 * @see ConfigFile::getBuiltComponents()
	 * @param array $list (optional) Components configuration
	 * @return array
	 */
	public function get ( array $list = [] ) {
		return $this->getBuiltComponents($list);
	}

	/**
	 * Get pre built list of components
	 * @param array $list (optional) Components configuration
	 * @throws \yii\base\InvalidConfigException
	 */
	protected function getBuiltComponents ( array $list = [] ) {
		$list = count($list) ? $list : $this->getListOf();

		if ( !count($list) ) {
			return [];
		}

		$prebuiltList = [];
		$builtList = [];

		foreach ( $list as $id=>$config ) {

			/** @var ConfigParser $parser */
			$parser = \Yii::createObject($this->config['configParserClass'], [[
				'id' => $id,
				'config' => $config,
				'baseUrl' => $this->config['baseUrl'],
				'basePath' => $this->config['basePath'],
				'sections' => $this->config['sections'],
				'fileClass' => $this->config['fileClass'],
				'sectionClass' =>$this->config['sectionClass'],
			]]);

			if ( ($props = $parser->getParsed()) === null ) {
				continue;
			}

			$prebuiltList[$id] = $props;
		}

		$parserClass = $this->config['configParserClass'];

		// Replace @component* tags from files name
		$postBuiltList = $parserClass::touchComponentTags($prebuiltList);

		foreach ( $postBuiltList as $id=>$data ) {

			$data['preComponents'] = $builtList;
			/** @var Component $component */
			$component = \Yii::createObject( $this->config['componentClass'], [$data] );

			$builtList[$id] = $component;
		}

		return $builtList;
	}
}
