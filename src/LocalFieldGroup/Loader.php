<?php
/**
 * ACF Class
 *
 * Extends ACF.
 *
 * You may copy, distribute and modify the software as long as you track
 * changes/dates in source files. Any modifications to or software including
 * (via compiler) GPL-licensed code must also be made available under the GPL
 * along with build & install instructions.
 *
 * @package    WPS\WP\Plugins\ManaoRadio
 * @author     Travis Smith <t@wpsmith.net>
 * @copyright  2015-2021 Travis Smith
 * @license    http://opensource.org/licenses/gpl-2.0.php GNU Public License v2
 * @link       https://wpsmith.net
 * @version    1.0.0
 * @since      0.1.0
 */

namespace WPS\WP\Plugins\ACF\LocalFieldGroup;

use WPS\Core\Singleton;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( __NAMESPACE__ . '\Loader' ) ) {
	/**
	 * Class Loader
	 *
	 * @package WPS\WP\Plugins\ACF
	 */
	class Loader {
		/**
		 * Plugin file path.
		 *
		 * @var string
		 */
		protected string $file = __FILE__;

		/**
		 * Field groups.
		 *
		 * @var array
		 */
		protected array $field_groups = [];

		/**
		 * ACF constructor.
		 *
		 * @param string $file The __FILE__ of the plugin.
		 */
		public function __construct( string $file = __FILE__ ) {
			$this->file = $file;
			$this->auto_add();
			\add_filter( 'acf/settings/load_json', [ $this, 'load_json' ] );
		}

		/**
		 * Automatically adds all files in a specific directory.
		 */
		public function auto_add() {
			$files = array_diff( scandir( $this->get_acf_json_path() ), array( '.', '..', 'index.php', ) );
			foreach ( $files as $file ) {
				if ( strlen( $file ) > 6 && false !== strpos( $file, '.json', -5 ) ) {
					$this->add_group( $file );
				}
			}
		}

		/**
		 * Adds a group.
		 *
		 * @param string $group ACF group ID.
		 */
		public function add_group( $group ) {
			if ( ! in_array( $group, $this->field_groups ) ) {
				$this->field_groups[] = $group;
			}
		}

		/**
		 * Loads the fields.
		 */
		public function load() {
			// Instantiate our groups.
			if ( ! empty( $this->field_groups ) ) {
				foreach ( $this->field_groups as $field_group ) {
					new LocalFieldGroup( $field_group, $this->get_acf_json_path() );
				}
			}
		}

		/**
		 * Add our ACF JSON path to the load paths.
		 *
		 * @param array $paths Load paths.
		 *
		 * @return mixed
		 */
		public function load_json( $paths ) {
			$paths[] = $this->get_acf_json_path();

			return $paths;
		}

		/**
		 * Plugin's ACF JSON folder.
		 *
		 * @return string
		 */
		public function get_acf_json_path() {
			return plugin_dir_path( $this->file ) . 'acf-json';
		}
	}
}
