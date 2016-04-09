<?php

/**
 * @copyright Copyright (c) 2016 Junaid Atari
 * @link http://junaidatari.com Website
 * @see http://www.github.com/blacksmoke26/yii2-cdn
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 */

namespace yii2cdn;

use yii\base\InvalidConfigException;
use yii\base\InvalidValueException;
use yii\helpers\ArrayHelper;

/**
 * Yii CDN Configuration File Loader
 *
 * @package yii2cdn
 * @author Junaid Atari <mj.atari@gmail.com>
 *
 * @access public
 * @version 0.1
 */
class ConfigLoader {

	/**
	 * Configuration object
	 * @var array
	 */
	protected $configs = [];

	/**
	 * Constructor function
	 * @param array $config (optional) Default configuration
	 */
	public function __construct( array $config = [] ) {
		$this->configs = $config;
	}

	/**
	 * Append config using file path
	 * @param string $path Config file path
	 * @throws \yii\base\InvalidConfigException When file not found
	 * @throws \yii\base\InvalidConfigException When file is unreadable
	 * @return ConfigLoader
	 */
	protected function appendWithFromFile ( $path ) {
		$path = \Yii::getAlias($path);

		if ( !is_file($path) ) {
			throw new InvalidConfigException("File '{$path}' not found");
		}

		if ( !is_readable( $path) ) {
			throw new InvalidConfigException ("File '{$path}' not readable");
		}

		$this->appendWith( require($path) );
		return $this;
	}

	/**
	 * Append array with current config
	 * @param array $config Config object
	 * @return ConfigLoader
	 */
	public function appendWith ( array $config = [] ) {
		$this->configs = ArrayHelper::merge( $this->configs, $config );
		return $this;
	}

	/**
	 * Load a configuration file only when offline mode is active
	 * @param string $path CDN config file path
	 * @return ConfigLoader
	 */
	public function offline ( $path ) {
		if ( !Cdn::isOnline() ) {
			return $this;
		}

		return $this->online( $path );
	}

	/**
	 * Load a configuration file
	 * @param string $path CDN config file path
	 * @return ConfigLoader
	 */
	public function online ( $path ) {
		return $this->appendWithFromFile( $path );
	}

	/**
	 * Load a files list and merge with configuration<br>
	 * Usage:
	 *     1. '...' : main file path
	 *     2. ['...'] : main file path
	 *     3. ['...', 'offline'=false] : online cdn file path
	 *     4. ['...', 'offline'=true] : offline cdn file path
	 * @param array $filesPath Config files list
	 * @throws \InvalidArgumentException
	 */
	public function loadConfig ( array $filesPath ) {
		if ( !is_array( $filesPath) || !count( $filesPath) ) {
			throw new InvalidValueException ('Files list is empty');
		}

		foreach ( $filesPath as $path ) {
			if ( is_string($path) ) {
				$this->online($path);
				continue;
			}

			if ( !is_array($path) || !count($path) ) {
				throw new InvalidValueException ('Path Value in not array nor string given');
			}

			if ( count($path) === 1 ) {
				$this->online($path[0]);
				continue;
			}

			if ( isset($path['offline']) && $path['offline'] ) {
				$this->offline($path[0]);
				continue;
			}

			continue;
		}
	}

	/**
	 * Get configuration object as array
	 * @return array
	 */
	public function asArray () {
		return $this->configs;
	}
}
