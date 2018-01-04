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
 * CDN Configuration File handler
 *
 * @package yii2cdn
 * @author Junaid Atari <mj.atari@gmail.com>
 *
 * @access public
 * @version 0.2
 */
class ConfigFile {

	/**
	 * @var string Absolute config file path
	 */
	protected $path;

	/**
	 * @var array Component Configuration
	 */
	protected $config = [];

	/**
	 * @var boolean File is offline or not
	 */
	protected $offline = \false;

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
	 * @param string|array $config CDN Config
	 * @return void
	 */
	protected function load ( $config ) {
		if ( \is_string ($config) ) {
			$this->path = $config;
			return;
		}

		$this->path = $config[0];
		$this->offline = \array_key_exists( 'offline', $config )
			&& $config['offline'];
	}

	/**
	 * Get the configuration
	 * @return array
	 * @throws \yii\base\InvalidConfigException
	 */
	protected function getListOf () {
		if ( Cdn::isOnline() && $this->offline ) {
			return [];
		}

		/** @var ConfigLoader $loader */
		$loader = Yii::createObject( $this->config['configLoaderClass'] );
		$loader->online($this->getPath());

		return $loader->asArray();
	}

	/**
	 *  Get config file real path
	 * @return string
	 */
	public function getPath() {
		return Yii::getAlias( $this->path );
	}

	/**
	 * Get the list of cdn components configuration
	 * @see ConfigFile::getBuiltComponents()
	 * @param array $list (optional) Components configuration
	 * @return array
	 * @throws \yii\base\InvalidConfigException
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
		/** @var array $collection */
		$collection = \count($list)
			? $list
			: $this->getListOf();

		if ( !\count($collection) ) {
			return [];
		}

		/** @var array $prebuiltList */
		$prebuiltList = [];

		/** @var array $builtList */
		$builtList = [];

		foreach ( $collection as $id => $config ) {
			/** @var ConfigParser $parser */
			$parser = Yii::createObject($this->config['configParserClass'], [[
				'id' => $id,
				'config' => $config,
				'baseUrl' => $this->config['baseUrl'],
				'basePath' => $this->config['basePath'],
				'sections' => $this->config['sections'],
				'fileClass' => $this->config['fileClass'],
				'sectionClass' =>$this->config['sectionClass'],
				'aliases' =>$this->config['aliases'],
			]]);

			if ( \null === ($props = $parser->getParsed()) ) {
				continue;
			}

			$prebuiltList[$id] = $props;
		}

		/** @var string $parserClass */
		$parserClass = $this->config['configParserClass'];

		// Replace @component* tags from files name
		/** @var array $postBuiltList */
		$postBuiltList = $parserClass::touchComponentTags($prebuiltList);

		foreach ( $postBuiltList as $id => $data ) {
			$data['preComponents'] = $builtList;
			/** @var Component $component */
			$component = Yii::createObject ($this->config['componentClass'], [$data]);

			$builtList[$id] = $component;
		}

		return $builtList;
	}
}
