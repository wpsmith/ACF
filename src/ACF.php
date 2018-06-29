<?php
/**
 * ACF Class
 *
 * Extends ACF.
 *
 * You may copy, distribute and modify the software as long as you track changes/dates in source files.
 * Any modifications to or software including (via compiler) GPL-licensed code must also be made
 * available under the GPL along with build & install instructions.
 *
 * @package    WPS\Core
 * @author     Travis Smith <t@wpsmith.net>
 * @copyright  2015-2018 Travis Smith
 * @license    http://opensource.org/licenses/gpl-2.0.php GNU Public License v2
 * @link       https://github.com/wpsmith/WPS
 * @version    1.0.0
 * @since      0.1.0
 */

namespace WPS\Plugins;

use WPS\Core;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WPS\Plugins\ACF' ) ) {
	/**
	 * Class ACF
	 *
	 * @package WPS\Plugins
	 */
	class ACF extends Core\Singleton {

		/**
		 * User.
		 *
		 * @var Core\User
		 */
		public $user;

		/**
		 * Super User.
		 *
		 * @var Core\User[]
		 */
		public $super_users;

		/**
		 * Field Key for bidirectional.
		 *
		 * @var array
		 */
		public $keys = array();

		/**
		 * Ignored post types
		 *
		 * @var array
		 */
		public $ignored_post_types = array(
			'revision',
			'nav_menu_item',
			'customize_changeset',
			'oembed_cache',
			'user_request',
			'wp_log',
			'custom_css',
		);

		/**
		 * ACF constructor.
		 *
		 * @param array $super_users Array of super users.
		 */
		public function __construct( $super_users = array() ) {

			// No nagging!
			add_filter( 'remove_hube2_nag', '__return_true' );

			add_action( 'plugins_loaded', array( $this, 'plugins_loaded' ) );
			$this->user = new Core\User( $super_users );
		}

		/**
		 * ACF Customizations.
		 */
		public function plugins_loaded() {
			$user = $this->user->user;

			// Special sauce for me!!
			if ( 'wpsmith' === $user->user_login ) {
				global $wp_post_types;
				$post_types = array_keys( $wp_post_types );

				foreach ( $post_types as $post_type ) {
					if ( in_array( $post_type, $this->ignored_post_types, true ) ) {
						continue;
					}
					add_post_type_support( $post_type, 'custom-fields' );
				}

				add_filter( 'acf/settings/remove_wp_meta_box', '__return_false' );
				add_filter( 'is_protected_meta', '__return_false', 999, 3 );
			}
		}

		/**
		 * Adds bidirectional support to a specific key for Post Object and Relationship ACF fields.
		 *
		 * Ensures that bidirectional support is added only once for a specific key.
		 *
		 * @param string $key Key of the ACF field.
		 */
		public function add_bidirectional( $key ) {
			if ( ! in_array( $key, $this->keys, true ) ) {
				add_filter( "acf/update_value/name=$key", array( '\WPS\Plugins\ACF', 'bidirectional' ), 10, 3 );
				$this->keys[] = $key;
			}
		}

		/**
		 * Adds bidirectional support to a specific key for Post Object and Relationship ACF fields.
		 *
		 * @param mixed $value   Value of the specific field.
		 * @param int   $post_id Post ID.
		 * @param array $field   Field data.
		 *
		 * @return mixed
		 */
		public static function bidirectional( $value, $post_id, $field ) {

			// vars.
			$field_name  = $field['name'];
			$field_key   = $field['key'];
			$global_name = 'is_updating_' . $field_name;


			// bail early if this filter was triggered from the update_field() function called within the loop below.
			// - this prevents an inifinte loop.
			if ( ! empty( $GLOBALS[ $global_name ] ) ) {
				return $value;
			}


			// set global variable to avoid infinite loop.
			// - could also remove_filter() then add_filter() again, but this is simpler.
			$GLOBALS[ $global_name ] = 1; // Input var ok.


			// loop over selected posts and add this $post_id.
			foreach ( (array) $value as $post_id2 ) {

				// load existing related posts.
				$value2 = get_field( $field_name, $post_id2, false );


				// allow for selected posts to not contain a value.
				if ( empty( $value2 ) ) {

					$value2 = array();

				}


				// bail early if the current $post_id is already found in selected post's $value2.
				if ( in_array( $post_id, $value2, true ) ) {
					continue;
				}


				// append the current $post_id to the selected post's 'related_posts' value.
				$value2[] = $post_id;


				// update the selected post's value (use field's key for performance).
				update_field( $field_key, $value2, $post_id2 );

			}


			// find posts which have been removed.
			$old_value = get_field( $field_name, $post_id, false );

			foreach ( (array) $old_value as $post_id2 ) {

				// bail early if this value has not been removed.
				if ( is_array( $value ) && in_array( $post_id2, $value, true ) ) {
					continue;
				}


				// load existing related posts.
				$value2 = get_field( $field_name, $post_id2, false );


				// bail early if no value.
				if ( empty( $value2 ) ) {
					continue;
				}


				// find the position of $post_id within $value2 so we can remove it.
				$pos = array_search( $post_id, $value2, true );


				// remove.
				unset( $value2[ $pos ] );


				// update the un-selected post's value (use field's key for performance).
				update_field( $field_key, $value2, $post_id2 );

			}

			// reset global variable to allow this filter to function as per normal.
			$GLOBALS[ $global_name ] = 0; // Input var ok.


			// return.
			return $value;

		}

	}
}
