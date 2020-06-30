<?php //phpcs:disable WordPress.NamingConventions.ValidFunctionName.MethodNameInvalid
/**
 * This file contains the Assertions trait
 *
 * @package Mantle
 */

namespace Mantle\Framework\Testing\Concerns;

use PHPUnit\Framework\Assert as PHPUnit;

/**
 * Assorted Test_Cast assertions.
 */
trait Assertions {

	/**
	 * Detect post-test failure conditions.
	 *
	 * We use this method to detect expectedDeprecated and expectedIncorrectUsage
	 * annotations.
	 */
	protected function assertPostConditions(): void {
		if ( method_exists( $this, 'expectedDeprecated' ) ) {
			$this->expectedDeprecated();
		}
		if ( method_exists( $this, 'expectedIncorrectUsage' ) ) {
			$this->expectedIncorrectUsage();
		}
	}

	/**
	 * Asserts that the given value is an instance of WP_Error.
	 *
	 * @param mixed  $actual  The value to check.
	 * @param string $message Optional. Message to display when the assertion fails.
	 */
	public static function assertWPError( $actual, $message = '' ) {
		PHPUnit::assertInstanceOf( 'WP_Error', $actual, $message );
	}

	/**
	 * Asserts that the given value is not an instance of WP_Error.
	 *
	 * @param mixed  $actual  The value to check.
	 * @param string $message Optional. Message to display when the assertion fails.
	 */
	public static function assertNotWPError( $actual, $message = '' ) {
		if ( '' === $message && is_wp_error( $actual ) ) {
			$message = $actual->get_error_message();
		}
		PHPUnit::assertNotInstanceOf( 'WP_Error', $actual, $message );
	}

	/**
	 * Asserts that the given fields are present in the given object.
	 *
	 * @param object $object The object to check.
	 * @param array  $fields The fields to check.
	 */
	public static function assertEqualFields( $object, $fields ) {
		foreach ( $fields as $field_name => $field_value ) {
			if ( $object->$field_name !== $field_value ) {
				PHPUnit::fail();
			}
		}
	}

	/**
	 * Asserts that two values are equal, with whitespace differences discarded.
	 *
	 * @param string $expected The expected value.
	 * @param string $actual   The actual value.
	 */
	public static function assertDiscardWhitespace( $expected, $actual ) {
		PHPUnit::assertEquals( preg_replace( '/\s*/', '', $expected ), preg_replace( '/\s*/', '', $actual ) );
	}

	/**
	 * Asserts that two values are equal, with EOL differences discarded.
	 *
	 * @since 5.4.0
	 *
	 * @param string $expected The expected value.
	 * @param string $actual   The actual value.
	 */
	public static function assertEqualsIgnoreEOL( $expected, $actual ) {
		PHPUnit::assertEquals( str_replace( "\r\n", "\n", $expected ), str_replace( "\r\n", "\n", $actual ) );
	}

	/**
	 * Asserts that the contents of two un-keyed, single arrays are equal, without accounting for the order of elements.
	 *
	 * @since 3.5.0
	 *
	 * @param array $expected Expected array.
	 * @param array $actual   Array to check.
	 */
	public static function assertEqualSets( $expected, $actual ) {
		sort( $expected );
		sort( $actual );
		PHPUnit::assertEquals( $expected, $actual );
	}

	/**
	 * Asserts that the contents of two keyed, single arrays are equal, without accounting for the order of elements.
	 *
	 * @since 4.1.0
	 *
	 * @param array $expected Expected array.
	 * @param array $actual   Array to check.
	 */
	public static function assertEqualSetsWithIndex( $expected, $actual ) {
		ksort( $expected );
		ksort( $actual );
		PHPUnit::assertEquals( $expected, $actual );
	}

	/**
	 * Asserts that the given variable is a multidimensional array, and that all arrays are non-empty.
	 *
	 * @since 4.8.0
	 *
	 * @param array $array Array to check.
	 */
	public static function assertNonEmptyMultidimensionalArray( $array ) {
		PHPUnit::assertTrue( is_array( $array ) );
		PHPUnit::assertNotEmpty( $array );

		foreach ( $array as $sub_array ) {
			PHPUnit::assertTrue( is_array( $sub_array ) );
			PHPUnit::assertNotEmpty( $sub_array );
		}
	}

	/**
	 * Checks each of the WP_Query is_* functions/properties against expected
	 * boolean value.
	 *
	 * Any properties that are listed by name as parameters will be expected to be
	 * true; all others are expected to be false. For example,
	 * assertQueryTrue( 'is_single', 'is_feed' ) means is_single() and is_feed()
	 * must be true and everything else must be false to pass.
	 *
	 * @param string ...$prop Any number of WP_Query properties that are expected
	 *                        to be true for the current request.
	 */
	public static function assertQueryTrue( ...$prop ) {
		global $wp_query;

		$all = [
			'is_404',
			'is_admin',
			'is_archive',
			'is_attachment',
			'is_author',
			'is_category',
			'is_comment_feed',
			'is_date',
			'is_day',
			'is_embed',
			'is_feed',
			'is_front_page',
			'is_home',
			'is_privacy_policy',
			'is_month',
			'is_page',
			'is_paged',
			'is_post_type_archive',
			'is_posts_page',
			'is_preview',
			'is_robots',
			'is_favicon',
			'is_search',
			'is_single',
			'is_singular',
			'is_tag',
			'is_tax',
			'is_time',
			'is_trackback',
			'is_year',
		];

		foreach ( $prop as $true_thing ) {
			PHPUnit::assertContains( $true_thing, $all, "Unknown conditional: {$true_thing}." );
		}

		$passed  = true;
		$message = '';

		foreach ( $all as $query_thing ) {
			$result = is_callable( $query_thing ) ? call_user_func( $query_thing ) : $wp_query->$query_thing;

			if ( in_array( $query_thing, $prop, true ) ) {
				if ( ! $result ) {
					$message .= $query_thing . ' is false but is expected to be true. ' . PHP_EOL;
					$passed   = false;
				}
			} elseif ( $result ) {
				$message .= $query_thing . ' is true but is expected to be false. ' . PHP_EOL;
				$passed   = false;
			}
		}

		if ( ! $passed ) {
			PHPUnit::fail( $message );
		}
	}

	/**
	 * Assert that a given ID matches the global queried object ID.
	 *
	 * @param int $id Expected ID.
	 */
	public static function assertQueriedObjectId( int $id ) {
		PHPUnit::assertSame( $id, get_queried_object_id() );
	}

	/**
	 * Assert that a given object is equivalent to the global queried object.
	 *
	 * @todo Add support for passing a Mantle Model to compare against a core WP object.
	 *
	 * @param Object $object Expected object.
	 */
	public static function assertQueriedObject( $object ) {
		global $wp_query;
		$queried_object = $wp_query->get_queried_object();

		// First, assert the same object types.
		PHPUnit::assertInstanceOf( get_class( $object ), $queried_object );

		// Next, assert identifying data about the object.
		switch ( true ) {
			case $object instanceof \WP_Post:
			case $object instanceof \WP_User:
				PHPUnit::assertSame( $object->ID, $queried_object->ID );
				break;

			case $object instanceof \WP_Term:
				PHPUnit::assertSame( $object->term_id, $queried_object->term_id );
				break;

			case $object instanceof \WP_Post_Type:
				PHPUnit::assertSame( $object->name, $queried_object->name );
				break;
		}
	}
}
