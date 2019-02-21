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
 * @copyright  2015-2018 Travis Smith
 * @license    http://opensource.org/licenses/gpl-2.0.php GNU Public License v2
 * @link       https://github.com/wpsmith/WPS
 * @version    1.0.0
 * @since      0.1.0
 */

namespace WPS\WP\Plugins\ACF;

use WPS\Core;
use WPS\Templates\TemplateData;
use WPS\Templates\TemplateLoader;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( __NAMESPACE__ . '\Hours' ) ) {
	/**
	 * Class ACF
	 *
	 * @package WPSWP\\Plugins
	 */
	class Hours extends Core\Singleton {

		/**
		 * Sunday
		 */
		const SUNDAY = 0;

		/**
		 * Monday
		 */
		const MONDAY = 1;

		/**
		 * Tuesday
		 */
		const TUESDAY = 2;

		/**
		 * Wednesday
		 */
		const WEDNESDAY = 3;

		/**
		 * Thursday
		 */
		const THURSDAY = 4;

		/**
		 * Friday
		 */
		const FRIDAY = 5;

		/**
		 * Saturday
		 */
		const SATURDAY = 6;

		/**
		 * Opening Hours Meta Key.
		 *
		 * @var string
		 */
		protected $meta_key;

		/**
		 * Opening Hours - Day Meta Key.
		 *
		 * @var string
		 */
		protected $day_meta_key;

		/**
		 * Opening Hours - Open Meta Key.
		 *
		 * @var string
		 */
		protected $open_meta_key;

		/**
		 * Opening Hours - Close Meta Key.
		 *
		 * @var string
		 */
		protected $close_meta_key;

		/**
		 * OpeningHours constructor.
		 *
		 * @param string $meta_key
		 * @param string $day_meta_key
		 * @param string $open_meta_key
		 * @param string $close_meta_key
		 */
		protected function __construct( $args = array() ) {
			$args = wp_parse_args( $args, array(
				'meta_key'       => 'hours',
				'day_meta_key'   => 'day',
				'open_meta_key'  => 'open',
				'close_meta_key' => 'close',
			) );

			$this->meta_key       = $args['meta_key'];
			$this->day_meta_key   = $args['day_meta_key'];
			$this->open_meta_key  = $args['open_meta_key'];
			$this->close_meta_key = $args['close_meta_key'];
		}

		/**
		 * Loads the hours template.
		 *
		 * @param int    $post_id          Post ID.
		 * @param string $plugin_directory Plugin dirname.
		 */
		public function hours( $post_id, $plugin_directory ) {

			$loader = new TemplateLoader(array(
				'filter_prefix'            => 'wps_hours',
				'plugin_directory'         => $plugin_directory,
			));

			$data = TemplateData::get_instance();
			$data->update( $this->meta_key, 'post_id', $post_id );
			$data->update( 'hours', 'post_id', $post_id );

			$loader->get_template_part( 'hours', $this->meta_key, true );

		}

		/**
		 * Gets the opening hours.
		 *
		 * @param int    $post_id  Post ID.
		 * @param string $meta_key Meta key holding opening hours repeater.
		 *
		 * @return string
		 */
		public function get_the_hours( $post_id, $key, $output = array() ) {

			$output = wp_parse_args( $output, array(
				'show' => false,
				'meta' => true,
			) );

			// @fix output

			$html  = '';
			$hours = array();

			// check if the repeater field has rows of data
			if ( have_rows( $this->meta_key, $post_id ) ) {

				// loop through the rows of data
				while ( have_rows( $this->meta_key, $post_id ) ): the_row();

					// display a sub field value
					$day   = get_sub_field( $this->day_meta_key );
					$open  = get_sub_field( $this->open_meta_key );
					$close = get_sub_field( $this->close_meta_key );

					$hours[ $day ] = array( $open, $close );

					// Meta
					if ( $output['meta'] ) {
						$html .= sprintf(
							'<meta itemprop="%s" content="%s %s-%s"/>',
							esc_attr( $key ),
							esc_attr( $day ),
							esc_attr( acf_format_date( $open, 'H:i:s' ) ),
							esc_attr( acf_format_date( $close, 'H:i:s' ) )
						);
					}

				endwhile;

			} else {

				return $html;

			}

			if ( ! empty( $hours ) && $output['show'] ) {
				$html  .= '<p>';
				$count = 1;
				foreach ( $hours as $day => $hrs ) {
					$day_slug = strtolower( sanitize_html_class( $day ) );
					$day      = self::get_day_of_week_str( $day_slug );
					$html     .= sprintf(
						'<span class="day day-%d day-%s">%s %s-%s</span>',
						$count,
						$day_slug,
						esc_html( $day ),
						esc_html( $hrs[0] ),
						esc_html( $hrs[1] )
					);
					$count ++;
				}
				$html .= '</p>';
			}

			return $html;

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
					->addRepeater( $this->meta_key, array(
						'label'                => __( 'Hours of Operation', 'wps' ),
						'max'                  => 7,
						'min'                  => 1,
						// For use by "mcguffin/acf-quick-edit-fields".
						'allow_quickedit'      => true,
						'allow_bulkedit'       => true,
						'show_column'          => true,
						'show_column_sortable' => true,
					) )
					->addSelect( $this->day_meta_key )
					->addChoices( self::get_days_of_week() )
					->addTimePicker( $this->open_meta_key, array(
						'display_format' => 'g:i a',
						'return_format'  => 'g:i a',
					) )
					->addTimePicker( $this->close_meta_key, array(
						'display_format' => 'g:i a',
						'return_format'  => 'g:i a',
					) )
					->endRepeater()
					// Needs to be a date.
					->addRepeater( $this->meta_key . '-pickup', array(
						'label'                => __( 'Hours for Pickup', 'wps' ),
						'max'                  => 7,
						'min'                  => 1,
						// For use by "mcguffin/acf-quick-edit-fields".
						'allow_quickedit'      => true,
						'allow_bulkedit'       => true,
						'show_column'          => true,
						'show_column_sortable' => true,
					) )
					->addDatePicker( 'date' )
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

		/**
		 * Gets the day of the week constant value.
		 *
		 * @param string $day_of_week Day of the week.
		 *
		 * @return int
		 */
		public static function get_day_of_week( $day_of_week ) {

			switch ( strtolower( $day_of_week ) ) {
				case 'su':
				case 'sun':
				case 'sunday':
					return self::SUNDAY;
				case 'mo':
				case 'mon':
				case 'monday':
					return self::MONDAY;
				case 'tu':
				case 'tue':
				case 'tues':
				case 'tuesday':
					return self::TUESDAY;
				case 'we':
				case 'wed':
				case 'wednesday':
					return self::WEDNESDAY;
				case 'th':
				case 'thu':
				case 'thur':
				case 'thurs':
				case 'thursday':
					return self::THURSDAY;
				case 'fr':
				case 'fri':
				case 'friday':
					return self::FRIDAY;
				case 'sa':
				case 'sat':
				case 'saturday':
					return self::SATURDAY;
			}

			return - 1;

		}

		/**
		 * Gets the day of the week from an array of defaults.
		 *
		 * @param string $day_of_week Day of the week.
		 * @param array  $defaults    Array of days.
		 *
		 * @return string
		 */
		protected static function get_dow( $day_of_week, $defaults ) {
			if ( is_int( $day_of_week ) ) {
				return $defaults[ $day_of_week ];
			}

			$dow = self::get_day_of_week( $day_of_week );
			if ( - 1 !== $dow ) {
				return $defaults[ $dow ];
			} elseif ( false !== strpos( $day_of_week, '-' ) ) {
				$days = explode( '-', $day_of_week );

				foreach ( $days as $i => $day ) {
					$dow = self::get_day_of_week( $day );
					if ( - 1 !== $dow ) {
						$days[ $i ] = $defaults[ $dow ];
					} else {
						return '';
					}
				}

				return implode( '-', $days );
			}

			return '';
		}

		/**
		 * Gets the day of the week long name.
		 *
		 * @param string $day_of_week Day of the week.
		 *
		 * @return string
		 */
		public static function get_day_of_week_str( $day_of_week ) {

			$defaults = array( 'Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday' );

			return self::get_dow( $day_of_week, $defaults );

		}

		/**
		 * Gets the schema two character day of the week.
		 *
		 * @param string $day_of_week Day of the week.
		 *
		 * @return string
		 */
		public static function get_day_of_week_2char( $day_of_week ) {

			$defaults = array( 'Su', 'Mo', 'Tu', 'We', 'Th', 'Fr', 'Sa' );

			return self::get_dow( $day_of_week, $defaults );

		}

		/**
		 * Gets the days of the week as a key-value array.
		 *
		 * @return array
		 */
		public static function get_days_of_week() {

			return array(
				array( 'Mo-Fr' => __( 'Monday-Friday', 'wps' ) ),
				array( 'Mo-Sa' => __( 'Monday-Saturday', 'wps' ) ),
				array( 'Su-Sa' => __( 'Sunday-Saturday', 'wps' ) ),
				array( 'Su' => __( 'Sunday', 'wps' ) ),
				array( 'Mo' => __( 'Monday', 'wps' ) ),
				array( 'Tu' => __( 'Tuesday', 'wps' ) ),
				array( 'We' => __( 'Wednesday', 'wps' ) ),
				array( 'Th' => __( 'Thursday', 'wps' ) ),
				array( 'Fr' => __( 'Friday', 'wps' ) ),
				array( 'Sa' => __( 'Saturday', 'wps' ) ),
			);

		}

		/**
		 * Gets the meta key.
		 *
		 * @return string
		 */
		public function get_key() {
			return $this->meta_key;
		}

	}
}
