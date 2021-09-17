<?php
/**
 * ACF TextField Class
 *
 * Extends acf_field_text.
 *
 * You may copy, distribute and modify the software as long as you track changes/dates in source files.
 * Any modifications to or software including (via compiler) GPL-licensed code must also be made
 * available under the GPL along with build & install instructions.
 *
 * @package    WPS\WP\Plugins\BucketLists\ACF
 * @author     Travis Smith <t@wpsmith.net>
 * @copyright  2015-2021 Travis Smith
 * @license    http://opensource.org/licenses/gpl-2.0.php GNU Public License v2
 * @link       https://github.com/wpsmith/WPS
 * @version    1.0.0
 * @since      0.1.0
 */

namespace WPS\WP\Plugins\ACF\Fields;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( __NAMESPACE__ . '\TextField' ) ) {

	class TextField extends \acf_field_text {

		/*
		*  initialize
		*
		*  This function will setup the field type data
		*
		*  @type	function
		*  @date	5/03/2014
		*  @since	5.0.0
		*
		*  @param	n/a
		*  @return	n/a
		*/

		public function initialize() {

			// vars
			$this->name     = 'text';
			$this->label    = __( 'Text', 'acf' );
			$this->defaults = array(
				'default_value' => '',
				'maxlength'     => '',
				'placeholder'   => '',
				'prepend'       => '',
				'append'        => '',
				'autocomplete'  => '',
			);

		}

		/*
		*  render_field()
		*
		*  Create the HTML interface for your field
		*
		*  @param	$field - an array holding all the field's data
		*
		*  @type	action
		*/
		public function render_field( $field ) {
			$html = '';

			// Prepend text.
			if ( $field['prepend'] !== '' ) {
				$field['class'] .= ' acf-is-prepended';
				$html           .= '<div class="acf-input-prepend">' . \acf_esc_html( $field['prepend'] ) . '</div>';
			}

			// Append text.
			if ( $field['append'] !== '' ) {
				$field['class'] .= ' acf-is-appended';
				$html           .= '<div class="acf-input-append">' . \acf_esc_html( $field['append'] ) . '</div>';
			}

			// Input.
			$input_attrs = array();
			foreach ( $this->get_input_attrs() as $k ) {
				if ( isset( $field[ $k ] ) ) {
					$input_attrs[ $k ] = $field[ $k ];
				}
			}

			$input_attrs = \acf_clean_atts( $input_attrs );
			$html .= '<div class="acf-input-wrap">' . \acf_get_text_input( \acf_filter_attrs( $input_attrs ) ) . '</div>';

			// Display.
			echo $html;
		}

		protected function get_input_attrs() {
			return array(
				'type',
				'id',
				'class',
				'name',
				'value',
				'placeholder',
				'maxlength',
				'pattern',
				'readonly',
				'disabled',
				'required',
				'autocomplete',
			);
		}
	}
}
