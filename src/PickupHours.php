<?php
/**
 * Pickup Hours Class
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

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( __NAMESPACE__ . '\PickupHours' ) ) {
	/**
	 * Class PickupHours
	 *
	 * @package WPS\WP\Plugins
	 */
	class PickupHours extends Hours {

		/**
		 * PickupHours constructor.
		 *
		 * @param string $meta_key
		 * @param string $day_meta_key
		 * @param string $open_meta_key
		 * @param string $close_meta_key
		 */
		protected function __construct( $args = array() ) {
			parent::__construct( $args );

			$this->meta_key     = 'pickup-hours';
			$this->day_meta_key = 'date';
		}

		/**
		 * Gets the pickup hours.
		 *
		 * @param int    $post_id  Post ID.
		 * @param string $meta_key Meta key holding opening hours repeater.
		 *
		 * @return string
		 */
		public function get_hours( $post_id, $show = true ) {

			return $this->get_the_hours( $post_id, '', array( 'show' => $show, 'meta' => false ) );

		}

		/**
		 * Adds opening hours fields to form.
		 *
		 * @param \StoutLogic\AcfBuilder\FieldsBuilder $builder ACF fields builder.
		 *
		 * @return \StoutLogic\AcfBuilder\FieldsBuilder
		 */
		public function get_hours_fields( \StoutLogic\AcfBuilder\FieldsBuilder $builder ) {

			$orig = $builder;

			try {
				$builder
					// Needs to be a date.
					->addRepeater( $this->meta_key, array(
						'label'                => __( 'Hours for Pickup', 'wps' ),
						'max'                  => 7,
						'min'                  => 1,
						// For use by "mcguffin/acf-quick-edit-fields".
						'allow_quickedit'      => true,
						'allow_bulkedit'       => true,
						'show_column'          => true,
						'show_column_sortable' => true,
					) )
					->addDatePicker( 'date', array(
						'display_format' => 'l M jS, Y',
						'return_format'  => 'l M jS, Y',
					) )
					->addTimePicker( $this->open_meta_key, array(
						'display_format' => 'g:i a',
						'return_format'  => 'g:i a',
					) )
					->addTimePicker( $this->close_meta_key, array(
						'display_format' => 'g:i a',
						'return_format'  => 'g:i a',
					) )
					->endRepeater();
			} catch ( \Exception $e ) {
				return $orig;
			}

			return $builder;
		}

	}
}
