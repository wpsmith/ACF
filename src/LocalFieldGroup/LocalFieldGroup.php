<?php
/**
 * ACF Local Field Group Class
 *
 * Enables dynamic saving of ACF json locally by field group ID.
 *
 * You may copy, distribute and modify the software as long as you track changes/dates in source files.
 * Any modifications to or software including (via compiler) GPL-licensed code must also be made
 * available under the GPL along with build & install instructions.
 *
 * @package    WPS\WP\Plugins\ACF
 * @author     Travis Smith <t@wpsmith.net>
 * @copyright  2015-2021 Travis Smith
 * @license    http://opensource.org/licenses/gpl-2.0.php GNU Public License v2
 * @link       https://github.com/wpsmith/ACF
 */

namespace WPS\WP\Plugins\ACF\LocalFieldGroup;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( __NAMESPACE__ . '\LocalFieldGroup' ) ) {
	/**
	 * Class ACFLocalFieldGroup
	 *
	 * @package WPS\WP\Plugins\ACF
	 */
	class LocalFieldGroup {
		/**
		 * The default ACF save_json path.
		 *
		 * @var string
		 */
		protected string $default_save_json_path;

		/**
		 * Field group ID.
		 *
		 * @var string
		 */
		protected string $field_group_id;

		/**
		 * ACF Field Group object.
		 *
		 * @var array
		 */
		protected array $field_group;

		/**
		 * Save location for the field group.
		 *
		 * @var string
		 */
		protected string $new_save_location;

		/**
		 * ACFLocalFieldGroup constructor.
		 *
		 * @param string $field_group_id Field Group ID.
		 * @param string $new_save_location Save location path.
		 */
		public function __construct( $field_group_id, $new_save_location ) {
			$this->default_save_json_path = \acf_get_setting( 'save_json' );

			if ( \acf_get_instance( 'ACF_Local_JSON' )->is_enabled() ) {
				$this->field_group_id    = $field_group_id;
				$this->new_save_location = $new_save_location;
				\add_filter( 'acf/update_field_group', [ $this, 'maybe_change_save_json' ], 1 );
				\add_filter( 'acf/update_field_group', [ $this, 'remove_maybe_change_save_json' ], PHP_INT_MAX );
			}
		}

		/**
		 * Writes field group data to JSON file.
		 *
		 * @param array $field_group The field group.
		 *
		 * @return mixed
		 */
		public function maybe_change_save_json( $field_group ) {
			if ( $this->field_group_id !== $field_group['key'] ) {
				return $field_group;
			}

			$this->field_group = $field_group;
			\add_filter( 'acf/settings/save_json', [ $this, 'save_json' ], 1 );

			return $field_group;
		}

		/**
		 * Change the save_json path to this one.
		 *
		 * @param string $path Current save_json path.
		 *
		 * @return string
		 */
		public function save_json( $path ) {
			return $this->new_save_location;
		}

		/**
		 * @param array $field_group The field group.
		 *
		 * @return mixed
		 */
		public function remove_maybe_change_save_json( $field_group ) {
			if ( \has_filter( 'acf/settings/save_json', [ $this, 'save_json' ] ) ) {
				\remove_filter( 'acf/settings/save_json', [ $this, 'save_json' ], 1 );
			}

			return $field_group;
		}
	}
}
