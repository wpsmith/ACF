<?php

namespace WPS\WP\Plugins\ACF;

use WPS\Core\Singleton;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( __NAMESPACE__ . '\UserRoleFieldSetting' ) ) {
	/**
	 * Class UserRoleFieldSetting
	 *
	 * @package WPS\Plugins\ACF
	 */
	class UserRoleFieldSetting extends Singleton {

		/**
		 * Choices.
		 *
		 * @var array
		 */
		private $choices = array();

		/**
		 * Current User.
		 *
		 * @var array
		 */
		private $current_user = array();

		/**
		 * Excluded ACF Field Types.
		 *
		 * @var array
		 */
		private $exclude_field_types = array(
			'tab'   => 'tab',
			'clone' => 'clone'
		);

		/**
		 * Fields removed.
		 *
		 * @var array
		 */
		private $removed = array();

		/**
		 * UserRoleFieldSetting constructor.
		 */
		protected function __construct() {
			\add_action( 'init', array( $this, 'init' ), 1 );
			\add_action( 'acf/init', array( $this, 'add_actions' ) );
			\add_action( 'acf/save_post', array( $this, 'save_post' ), - 1 );
			\add_action( 'after_setup_theme', array( $this, 'after_setup_theme' ) );
			//add_filter('acf/get_field_types', array($this, 'add_actions'), 20, 1);
		}

		/**
		 * Conditionally hooks prepare_field or get_fields based on ACF Version.
		 */
		public function after_setup_theme() {
			// check the ACF version
			// if >= 5.5.0 use the acf/prepare_field hook to remove fields
			if ( ! function_exists( 'acf_get_setting' ) ) {
				// acf is not installed/active
				return;
			}
			$acf_version = acf_get_setting( 'version' );
			if ( version_compare( $acf_version, '5.5.0', '>=' ) ) {
				\add_filter( 'acf/prepare_field', array( $this, 'prepare_field' ), 99 );
			} else {
				// if < 5.5.0 user the acf/get_fields hook to remove fields
				\add_filter( 'acf/get_fields', array( $this, 'get_fields' ), 20, 2 );
			}
		}

		/**
		 * Prepares the field.
		 *
		 * If the field is to be excluded, returns false. If the field is to be
		 * removed, this function prints a hidden input field.
		 *
		 * @param array $field ACF Field array.
		 *
		 * @return array|false
		 */
		public function prepare_field( $field ) {
			$exclude = \apply_filters( 'acf/user_role_setting/exclude_field_types', $this->exclude_field_types );

			if ( in_array( $field['type'], $exclude, true ) ) {
				return $field;
			}

			if ( isset( $field['user_roles'] ) ) {
				if ( ! empty( $field['user_roles'] ) && is_array( $field['user_roles'] ) ) {
					foreach ( $field['user_roles'] as $role ) {
						if ( $role == 'all' || in_array( $role, $this->current_user ) ) {
							return $field;
						}
					}
				}

                // no user roles have been selected for this field
                // it will never be displayed, this is probably an error
			} else {
				// user roles not set for this field
				// this field was created before this plugin was in use
				// or user roles is otherwise disabled for this field
				return $field;
			}

			preg_match( '/(\[[^\]]+\])/', $field['name'], $matches );
			$name = $matches[1];

			if ( ! in_array( $name, $this->removed ) ) {
				$this->removed[] = $name;
				?><input type="hidden" name="acf_removed<?php echo $name; ?>" value="<?php
				echo $field['name']; ?>" /><?php
			}

			return false;
		}

		/**
		 * Save post action.
		 *
		 * @param bool  $post_id Post ID.
		 * @param array $values  Field Values.
		 */
		public function save_post( $post_id = false, $values = array() ) {
			// Don't do anything if ACF not present.
			if ( ! isset( $_POST['acf'] ) ) {
				return;
			}

			// Capture excluded field types
			$this->exclude_field_types = \apply_filters( 'acf/user_role_setting/exclude_field_types', $this->exclude_field_types );

			// Filter out "uneditable fields".
			if ( is_array( $_POST['acf'] ) ) {
				$_POST['acf'] = $this->filter_post_values( $_POST['acf'] ); // Input var ok.
			}

			// Puts ACF removed fields back into $_POST.
			if ( isset( $_POST['acf_removed'] ) ) {
				$this->get_removed( $post_id );
				$_POST['acf'] = $this->array_merge_recursive_distinct( $_POST['acf'], $_POST['acf_removed'] ); // Input var ok.
			}
		}

		/**
		 * Gets removed fields.
		 *
		 * @param int $post_id Post ID.
		 */
		private function get_removed( $post_id ) {
			foreach ( $_POST['acf_removed'] as $field_key => $value ) {
				$_POST['acf_removed'][ $field_key ] = \get_field( $field_key, $post_id, false );
			}
		}

		/**
		 * Merges two arrays recursively without over-writing first array values.
		 *
		 * @param array $array1 First array to be merged into.
		 * @param array $array2 Second array to merge.
		 *
		 * @return array
		 */
		private function array_merge_recursive_distinct( array &$array1, array &$array2 ) {
			$merged = $array1;
			foreach ( $array2 as $key => &$value ) {
				if ( is_array( $value ) && isset( $merged[ $key ] ) && is_array( $merged[ $key ] ) ) {
					$merged[ $key ] = $this->array_merge_recursive_distinct( $merged[ $key ], $value );
				} else {
					// do not overwrite value in first array
					if ( ! isset( $merged[ $key ] ) ) {
						$merged[ $key ] = $value;
					}
				}
			}

			return $merged;
		}

		private function keep_filtered_field( $value ) {
			return is_array( $value ) ? $this->filter_post_values( $value ) : $value;
		}

		/**
		 * Filters $_POST values.
		 *
		 * This is a recursive function the examinse all posted fields
		 * and removes any fields the a user is not supposed to have access to.
		 *
		 * @param array $input Array of $_POST fields.
		 *
		 * @return array
		 */
		private function filter_post_values( $input ) {
			$output = array();
			foreach ( $input as $index => $value ) {
				// Only check ACF fields.
				if ( 'field_' === substr( $index, 0, 6 ) ) {

					$field = \get_field_object( $index );

					// If excluded, then keep.
					if ( in_array( $field['type'], $this->exclude_field_types, true ) ) {
						$output[ $index ] = $this->keep_filtered_field( $value );
						continue;

						// check to see if this field can be edited
					} elseif (
						isset( $field['user_roles'] ) &&
						is_array( $field['user_roles'] ) &&
						! empty( $field['user_roles'] )
					) {
						// Check to see if our role can edit.
						foreach ( $field['user_roles'] as $role ) {
							// If all roles, or our role, let's keep it.
							if ( 'all' === $role || in_array( $role, $this->current_user ) ) {
								$output[ $index ] = $this->keep_filtered_field( $value );
								// keepiing, no point in continuing to other roles.
								break;
							}
						}
					}
				} else {
					// keeping any non-ACF stuffs.
					$output[ $index ] = $this->keep_filtered_field( $value );
				}
			}

			return $output;
		}

		/**
		 * Initialize this.
		 */
		public function init() {
			$this->get_roles();
			$this->current_user_roles();
		}

		/**
		 * Add render_field_settings to any fields that are not excluded.
		 */
		public function add_actions() {
			// Make sure we have ACF.
			if ( ! function_exists( 'acf_get_setting' ) ) {
				return;
			}

			// Get our exclusions.
			$exclude = \apply_filters( 'acf/user_role_setting/exclude_field_types', $this->exclude_field_types );

			// Get ACF version.
			$acf_version = \acf_get_setting( 'version' );

			// Get the sections.
			$sections = \acf_get_field_types();

			// ACF version < 5.5.0 or >= 5.6.0
			if ( version_compare( $acf_version, '5.5.0', '<' ) || version_compare( $acf_version, '5.6.0', '>=' ) ) {
				foreach ( (array) $sections as $section ) {
					foreach ( (array) $section as $type => $label ) {
						if ( ! isset( $exclude[ $type ] ) ) {
							\add_action( 'acf/render_field_settings/type=' . $type, array(
								$this,
								'render_field_settings'
							), 1 );
						}
					}
				}
			} else {
				// ACF Version >= 5.5.0 || < 5.6.0
				foreach ( (array) $sections as $type => $settings ) {
					if ( ! isset( $exclude[ $type ] ) ) {
						\add_action( 'acf/render_field_settings/type=' . $type, array(
							$this,
							'render_field_settings'
						), 1 );
					}
				}
			}
		} // end public function add_actions

		/**
		 * Gets the current user's roles.
		 */
		private function current_user_roles() {
			global $current_user;
			if ( is_object( $current_user ) && isset( $current_user->roles ) ) {
				$this->current_user = $current_user->roles;
			}
			if ( \is_multisite() && \current_user_can( 'update_core' ) ) {
				$this->current_user[] = 'super_admin';
			}
		}

		/**
		 * Gets the roles.
		 *
		 * Sets the choices property with role-key => role-label array.
		 *
		 * @return array
		 */
		private function get_roles() {
			if ( count( $this->choices ) ) {
				return $this->choices;
			}

			$wp_roles = \wp_roles();
			$choices  = array( 'all' => __( 'All', 'wps' ) );
			if ( \is_multisite() ) {
				$choices['super_admin'] = __( 'Super Admin', 'wps' );
			}

			foreach ( $wp_roles->roles as $role => $settings ) {
				$choices[ $role ] = $settings['name'];
			}
			$this->choices = $choices;

			return $choices;
		}

		/**
		 * Get fields.
		 *
		 * @param array $fields Array of ACF Fields.
		 * @param array $parent ACF Field array.
		 *
		 * @return array
		 */
		public function get_fields( $fields, $parent ) {
			// do not alter when editing field or field group
			if (
//				is_object( get_post() ) &&
//				get_the_ID() &&
//				(
				'acf-field-group' === \get_post_type() ||
				'acf-field' === \get_post_type()
//				)
			) {

				return $fields;
			}

			// Get excluded fields.
			$this->exclude_field_types = \apply_filters( 'acf/user_role_setting/exclude_field_types', $this->exclude_field_types );

			// Check the fields.
			$fields = $this->check_fields( $fields );

			return $fields;
		}

		private function keep_checked_field( $field ) {
			if ( isset( $field['layouts'] ) ) {
				$field['layouts'] = $this->check_fields( $field['layouts'] );
			}
			if ( isset( $field['sub_fields'] ) ) {
				$field['sub_fields'] = $this->check_fields( $field['sub_fields'] );
			}

			return $field;
		}

		/**
		 * Check the fields recu.
		 *
		 * @param array $fields Array of ACF Fields.
		 *
		 * @return array
		 */
		private function check_fields( $fields ) {
			// recursive function
			// see if field should be kept
			$keep_fields = array();
			if ( is_array( $fields ) && count( $fields ) ) {
				foreach ( $fields as $field ) {
					if ( in_array( $field['type'], $this->exclude_field_types ) ) {
						$keep_fields[] = $this->keep_checked_field( $field );
					} else {
						if ( isset( $field['user_roles'] ) ) {
							if ( ! empty( $field['user_roles'] ) && is_array( $field['user_roles'] ) ) {
								foreach ( $field['user_roles'] as $role ) {
									if ( $role == 'all' || in_array( $role, $this->current_user ) ) {
										$keep_fields[] = $this->keep_checked_field( $field );
										// already keeping, no point in continuing to check
										break;
									}
								}
							}
						} else {
							// field setting is not set
							// this field was created before this plugin was in use
							// or this field is not effected, it could be a "layout"
							// there is currently no way to add field settings to
							// layouts in ACF
							// assume 'all'
							$keep_fields[] = $this->keep_checked_field( $field );
						}
					}
				}
			} else {
				return $fields;
			}

			return $keep_fields;
		}

		/**
		 * Render field settings.
		 *
		 * @param array $field ACF Field array.
		 */
		public function render_field_settings( $field ) {
			$args = array(
				'type'          => 'checkbox',
				'label'         => \__( 'User Roles', 'wps' ),
				'name'          => 'user_roles',
				'instructions'  => \__( 'Select the User Roles that are allowed to view and edit this field.' .
				                       ' This field will be removed for any user type not selected.' .
				                       ' <strong><em>If nothing is selected then this field will never be' .
				                       ' included in the field group.</em></strong>', 'wps' ),
				'required'      => 0,
				'default_value' => array( 'all' ),
				'choices'       => $this->choices,
				'layout'        => 'horizontal'
			);
			\acf_render_field_setting( $field, $args, false );

		}
	}
}
