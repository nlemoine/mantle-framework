<?php
/**
 * String helpers
 *
 * @package Mantle
 */

namespace Mantle\Support\Helpers;

use Mantle\Support\Str;
use Mantle\Support\Stringable;

if ( ! function_exists( 'str' ) ) {
	/**
	 * Get a new stringable object from the given string.
	 *
	 * @param  string|null $string Optional. The string value to wrap.
	 * @return Stringable|
	 */
	function str( string|null $string = null ): Stringable {
		return new Stringable( $string );
	}
}
