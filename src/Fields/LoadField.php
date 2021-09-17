<?php
/**
 * ACF Form Helper Class
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

namespace WPS\WP\Plugins\ACF\Fields;

use WPS\Core\Singleton;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( __NAMESPACE__ . '\LoadField' ) ) {
	/**
	 * Class LoadField
	 *
	 * @package WPS\WP\Plugins\BucketLists\ACF
	 */
	class LoadField extends Singleton {

		/**
		 * LoadField constructor.
		 *
		 * @param null $args
		 */
		protected function __construct( $args = null ) {
			parent::__construct( $args );

			// Load existing post content.
			\add_filter( 'acf/load_value/name=post_content', array( __NAMESPACE__ . '\LoadField', 'load_post_content' ), 10, 3 );
			\add_filter( 'acf/load_value/name=post_title', array( __NAMESPACE__ . '\LoadField', 'load_post_title' ), 10, 3 );

			// Customize wysiwyg field type.
			LoadField::add_customize_wysiwyg_field_type();
		}

		/**
		 * Hooks to limit the wysiwyg field.
		 */
		public static function add_customize_wysiwyg_field_type() {
			\add_action( 'acf/get_valid_field', array( __NAMESPACE__ . '\LoadField', 'customize_wysiwyg_field_type' ), 10, 2 );
		}

		/**
		 * Customize the wysiwyg field types.
		 *
		 * @param array $field ACF Field.
		 *
		 * @return array
		 */
		public static function customize_wysiwyg_field_type( $field ) {
			if ( 'wysiwyg' === $field['type'] ) {
				$field['tabs']         = 'visual';
				$field['toolbar']      = 'basic';
				$field['media_upload'] = 0;
			}

			return $field;
		}

		public static function get_kses_allowed_tags() {
			return array(
				'a'          => array(
					'href'     => true,
					'rel'      => true,
					'rev'      => true,
					'name'     => true,
					'target'   => true,
					'download' => array(
						'valueless' => 'y',
					),
				),
				'abbr'       => array(),
				'acronym'    => array(),
				'b'          => array(),
				'big'        => array(),
				'blockquote' => array(
					'cite'     => true,
					'lang'     => true,
					'xml:lang' => true,
				),
				'br'         => array(),
				'cite'       => array(
					'dir'  => true,
					'lang' => true,
				),
				'code'       => array(),
				'del'        => array(
					'datetime' => true,
				),
				'dd'         => array(),
				'dfn'        => array(),
				'div'        => array(
					'align'    => true,
					'dir'      => true,
					'lang'     => true,
					'xml:lang' => true,
				),
				'dl'         => array(),
				'dt'         => array(),
				'em'         => array(),
				'h3'         => array(
					'align' => true,
				),
				'h4'         => array(
					'align' => true,
				),
				'h5'         => array(
					'align' => true,
				),
				'h6'         => array(
					'align' => true,
				),
				'hr'         => array(
					'align'   => true,
					'noshade' => true,
					'size'    => true,
					'width'   => true,
				),
				'i'          => array(),
				'img'        => array(
					'alt'      => true,
					'align'    => true,
					'border'   => true,
					'height'   => true,
					'hspace'   => true,
					'loading'  => true,
					'longdesc' => true,
					'vspace'   => true,
					'src'      => true,
					'usemap'   => true,
					'width'    => true,
				),
				'li'         => array(
					'align' => true,
					'value' => true,
				),
				'p'          => array(
					'align'    => true,
					'dir'      => true,
					'lang'     => true,
					'xml:lang' => true,
				),
				'pre'        => array(
					'width' => true,
				),
				'q'          => array(
					'cite' => true,
				),
				's'          => array(),
				'span'       => array(
					'style'    => true,
					'dir'      => true,
					'align'    => true,
					'lang'     => true,
					'xml:lang' => true,
				),
				'small'      => array(),
				'strike'     => array(),
				'strong'     => array(),
				'sup'        => array(),
				'table'      => array(
					'align'       => true,
					'bgcolor'     => true,
					'border'      => true,
					'cellpadding' => true,
					'cellspacing' => true,
					'dir'         => true,
					'rules'       => true,
					'summary'     => true,
					'width'       => true,
				),
				'tbody'      => array(
					'align'   => true,
					'char'    => true,
					'charoff' => true,
					'valign'  => true,
				),
				'td'         => array(
					'abbr'    => true,
					'align'   => true,
					'axis'    => true,
					'bgcolor' => true,
					'char'    => true,
					'charoff' => true,
					'colspan' => true,
					'dir'     => true,
					'headers' => true,
					'height'  => true,
					'nowrap'  => true,
					'rowspan' => true,
					'scope'   => true,
					'valign'  => true,
					'width'   => true,
				),
				'tfoot'      => array(
					'align'   => true,
					'char'    => true,
					'charoff' => true,
					'valign'  => true,
				),
				'th'         => array(
					'abbr'    => true,
					'align'   => true,
					'axis'    => true,
					'bgcolor' => true,
					'char'    => true,
					'charoff' => true,
					'colspan' => true,
					'headers' => true,
					'height'  => true,
					'nowrap'  => true,
					'rowspan' => true,
					'scope'   => true,
					'valign'  => true,
					'width'   => true,
				),
				'thead'      => array(
					'align'   => true,
					'char'    => true,
					'charoff' => true,
					'valign'  => true,
				),
				'tr'         => array(
					'align'   => true,
					'bgcolor' => true,
					'char'    => true,
					'charoff' => true,
					'valign'  => true,
				),
				'ul'         => array(
					'type' => true,
				),
				'ol'         => array(
					'start'    => true,
					'type'     => true,
					'reversed' => true,
				),
				'video'      => array(
					'autoplay'    => true,
					'controls'    => true,
					'height'      => true,
					'loop'        => true,
					'muted'       => true,
					'playsinline' => true,
					'poster'      => true,
					'preload'     => true,
					'src'         => true,
					'width'       => true,
				),
			);
		}

		/**
		 * Loads the post title.
		 *
		 * @param mixed $value Value of the field.
		 * @param int $post_id Post ID.
		 * @param array $field Field.
		 *
		 * @return string
		 */
		public static function load_post_title( $value, $post_id, $field ) {
			$value = get_the_title( $post_id );

			return $value;
		}

		/**
		 * Loads the post content.
		 *
		 * @param mixed $value Value of the field.
		 * @param int $post_id Post ID.
		 * @param array $field Field.
		 *
		 * @return string
		 */
		public static function load_post_content( $value, $post_id, $field ) {
			$value = get_the_content( $post_id );

			return $value;
		}
	}
}
