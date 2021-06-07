<?php
/**
 * ACF Form Class
 *
 * Extends ACF.
 *
 * You may copy, distribute and modify the software as long as you track changes/dates in source files.
 * Any modifications to or software including (via compiler) GPL-licensed code must also be made
 * available under the GPL along with build & install instructions.
 *
 * @package    WPS\WP\Plugins
 * @author     Travis Smith <t@wpsmith.net>
 * @copyright  2015-2021 Travis Smith
 * @license    http://opensource.org/licenses/gpl-2.0.php GNU Public License v2
 * @link       https://github.com/wpsmith/WPS
 * @version    1.0.0
 * @since      0.1.0
 */

namespace WPS\WP\Plugins\ACF;

use JetBrains\PhpStorm\Pure;
use WPS\Core\Singleton;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( __NAMESPACE__ . '\ACFForm' ) ) {
	/**
	 * Class ACFForm
	 *
	 * @package WPS\WP\Plugins\ACF
	 */
	abstract class ACFForm extends Singleton {

		/**
		 * ACF ID.
		 *
		 * @var string
		 */
		protected string $id = '';

		/**
		 * Gets field group.
		 *
		 * @return string
		 */
		public function get_field_group(): string {
			return $this->id;
		}

		/**
		 * Unsets a key from $_POST for acf.
		 *
		 * @param string $key Key under $_POST['acf'].
		 */
		protected function unset_key( string $key ): void {
			if ( 'acf' === $key && isset( $_POST['acf'] ) ) {
				unset( $_POST['acf'] );
			} else {
				$key = $this->get_field_key( $key );

				if ( isset( $_POST['acf'] ) && isset( $_POST['acf'][ $key ] ) ) {
					unset( $_POST['acf'][ $key ] );
				}
			}

		}

		/**
		 * Gets a normalized key name for $_POST['acf'].
		 * Adds the prefix if one is available.
		 * Adds the field froup if one is available.
		 *
		 * @param string $key Key under $_POST['acf'].
		 * @param string $field_group
		 *
		 * @return string
		 */
		protected function get_field_key( string $key, string $field_group = '' ): string {
			if ( $field_group ) {
				return "field_{$field_group}_{$key}";
			} elseif ( $this->get_field_group() ) {
				return "field_{$this->get_field_group()}_{$key}";
			} elseif ( $this->id ) {
				return "field_{$this->id}_{$key}";
			}

			return "field_{$key}";
		}

		/**
		 * Gets a value from $_POST for acf key.
		 *
		 * @param string $key Key under $_POST['acf'].
		 *
		 * @return string
		 */
		public function get_acf_post_value( string $key ): string {
			return sanitize_text_field( $this->get_acf_post_raw_value( $key ) );
		}

		/**
		 * Gets a value from $_POST for acf key.
		 *
		 * @param string $key Key under $_POST['acf'].
		 *
		 * @return string
		 */
		public function get_acf_post_raw_value( string $key ): string {
			$key = $this->get_field_key( $key );

			if ( isset( $_POST['acf'] ) && isset( $_POST['acf'][ $key ] ) ) {
				return $_POST['acf'][ $key ];
			}

			return '';
		}

		/**
		 * @param string $key ACF Key under $_POST.
		 *
		 * @return string
		 */
		public function get_acf_data_post_value( string $key ): string {
			if ( 0 !== strpos( $key, '_acf_' ) ) {
				$key = "_acf_$key";
			}
			if ( isset( $_POST[ $key ] ) ) {
				return sanitize_text_field( $_POST[ $key ] );
			}
		}

		/**
		 * Whether user can save the post.
		 *
		 * @param int $post_id Post ID.
		 *
		 * @return bool
		 */
		public function can_save_post( $post_id ): bool {
			return (
				is_user_logged_in() &&
				current_user_can( 'publish_posts' ) &&
				isset( $_POST['_acf_form_id'] ) && $_POST['_acf_form_id'] === $this->id
			);
		}
	}
}
