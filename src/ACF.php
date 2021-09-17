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
 * @package    WPS\WP\Plugins
 * @author     Travis Smith <t@wpsmith.net>
 * @copyright  2015-2019 Travis Smith
 * @license    http://opensource.org/licenses/gpl-2.0.php GNU Public License v2
 * @link       https://github.com/wpsmith/WPS
 * @version    1.0.0
 * @since      0.1.0
 */

namespace WPS\WP\Plugins\ACF;

use WPS\Core\Singleton;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( __NAMESPACE__ . '\ACF' ) ) {
	/**
	 * Class ACF
	 *
	 * @package WPS\WP\Plugins
	 */
	class ACF extends Singleton {

		/**
		 * User.
		 *
		 * @var \WPS\WP\Users\CurrentUser
		 */
		protected $user;

		/**
		 * Array of administrators/super users.
		 *
		 * @var string[]
		 */
		protected array $super_users;

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

			$this->super_users = $super_users;
			\add_action( 'plugins_loaded', array( $this, 'plugins_loaded' ) );

		}

		/**
		 * Set super users.
		 *
		 * @param array $super_users Super Users.
		 */
		public function set_super_users( $super_users ) {
			$this->super_users = $super_users;
		}

		/**
		 * Gets the current user.
		 *
		 * @return \WPS\WP\Users\CurrentUser
		 */
		protected function get_current_user() {
			if ( $this->user ) {
				return $this->user;
			}

			if ( class_exists( 'WPS\WP\Users\CurrentUser' ) ) {
				$this->user = \WPS\WP\Users\CurrentUser::get_instance( $this->super_users );
			}
		}

		/**
		 * ACF Customizations.
		 */
		public function plugins_loaded() {
			$user = $this->get_current_user();
			if ( $user ) {
				$user = $user->user;
			}

			// Special sauce for super users!!
			if ( in_array( $user->user_login, $this->super_users, true ) ) {
				global $wp_post_types;
				$post_types = array_keys( $wp_post_types );

				foreach ( $post_types as $post_type ) {
					if ( in_array( $post_type, $this->ignored_post_types, true ) ) {
						continue;
					}

					\add_post_type_support( $post_type, 'custom-fields' );
				}

				\add_filter( 'acf/settings/remove_wp_meta_box', '__return_false' );
				\add_filter( 'is_protected_meta', '__return_false', 999, 3 );
			}
		}

	}
}
