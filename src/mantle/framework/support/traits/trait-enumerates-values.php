<?php
/**
 * Enumerates_Values trait file.
 *
 * @package mantle
 */

// phpcs:disable Squiz.Commenting.FunctionComment.MissingParamComment

// phpcs:disable Squiz.Commenting.FunctionComment.ParamNameNoMatch

// phpcs:disable Squiz.Commenting.FunctionComment.MissingParamTag

namespace Mantle\Framework\Support\Traits;

use CachingIterator;
use Closure;
use Exception;
use Mantle\Framework\Contracts\Support\Arrayable;
use Mantle\Framework\Contracts\Support\Jsonable;
use function Mantle\Framework\Helpers\data_get;
use Mantle\Framework\Support\Arr;
use Mantle\Framework\Support\Collection;
use Mantle\Framework\Support\Enumerable;
use JsonSerializable;
use Mantle\Framework\Support\Higher_Order_Collection_Proxy;
use Symfony\Component\VarDumper\VarDumper;
use Traversable;

/**
 * Enumerate_Values trait.
 *
 * @property-read Higher_Order_Collection_Proxy $average
 * @property-read Higher_Order_Collection_Proxy $avg
 * @property-read Higher_Order_Collection_Proxy $contains
 * @property-read Higher_Order_Collection_Proxy $each
 * @property-read Higher_Order_Collection_Proxy $every
 * @property-read Higher_Order_Collection_Proxy $filter
 * @property-read Higher_Order_Collection_Proxy $first
 * @property-read Higher_Order_Collection_Proxy $flat_map
 * @property-read Higher_Order_Collection_Proxy $group_by
 * @property-read Higher_Order_Collection_Proxy $key_by
 * @property-read Higher_Order_Collection_Proxy $map
 * @property-read Higher_Order_Collection_Proxy $max
 * @property-read Higher_Order_Collection_Proxy $min
 * @property-read Higher_Order_Collection_Proxy $partition
 * @property-read Higher_Order_Collection_Proxy $reject
 * @property-read Higher_Order_Collection_Proxy $some
 * @property-read Higher_Order_Collection_Proxy $sort_by
 * @property-read Higher_Order_Collection_Proxy $sort_by_desc
 * @property-read Higher_Order_Collection_Proxy $sum
 * @property-read Higher_Order_Collection_Proxy $unique
 * @property-read Higher_Order_Collection_Proxy $until
 */
trait Enumerates_Values {
	/**
	 * The methods that can be proxied.
	 *
	 * @var array
	 */
	protected static $proxies = [
		'average',
		'avg',
		'contains',
		'each',
		'every',
		'filter',
		'first',
		'flatMap',
		'groupBy',
		'keyBy',
		'map',
		'max',
		'min',
		'partition',
		'reject',
		'skipUntil',
		'skipWhile',
		'some',
		'sortBy',
		'sortByDesc',
		'sum',
		'takeUntil',
		'takeWhile',
		'unique',
		'until',
	];

	/**
	 * Create a new collection instance if the value isn't one already.
	 *
	 * @param  mixed $items
	 * @return static
	 */
	public static function make( $items = [] ) {
		return new static( $items );
	}

	/**
	 * Wrap the given value in a collection if applicable.
	 *
	 * @param  mixed $value
	 * @return static
	 */
	public static function wrap( $value ) {
		return $value instanceof Enumerable
			? new static( $value )
			: new static( Arr::wrap( $value ) );
	}

	/**
	 * Get the underlying items from the given collection if applicable.
	 *
	 * @param  array|static $value
	 * @return array
	 */
	public static function unwrap( $value ) {
		return $value instanceof Enumerable ? $value->all() : $value;
	}

	/**
	 * Alias for the "avg" method.
	 *
	 * @param  callable|string|null $callback
	 * @return mixed
	 */
	public function average( $callback = null ) {
		return $this->avg( $callback );
	}

	/**
	 * Alias for the "contains" method.
	 *
	 * @param  mixed $key
	 * @param  mixed $operator
	 * @param  mixed $value
	 * @return bool
	 */
	public function some( $key, $operator = null, $value = null ) {
		return $this->contains( ...func_get_args() );
	}

	/**
	 * Determine if an item exists, using strict comparison.
	 *
	 * @param  mixed $key
	 * @param  mixed $value
	 * @return bool
	 */
	public function contains_strict( $key, $value = null ) {
		if ( func_num_args() === 2 ) {
			return $this->contains(
				function ( $item ) use ( $key, $value ) {
					return data_get( $item, $key ) === $value;
				}
			);
		}

		if ( $this->use_as_callable( $key ) ) {
			return ! is_null( $this->first( $key ) );
		}

		foreach ( $this as $item ) {
			if ( $item === $key ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Dump the items and end the script.
	 *
	 * @param  mixed ...$args
	 * @return void
	 */
	public function dd( ...$args ) {
		call_user_func_array( [ $this, 'dump' ], $args );

		die( 1 );
	}

	/**
	 * Dump the items.
	 *
	 * @return $this
	 */
	public function dump() {
		( new static( func_get_args() ) )
			->push( $this )
			->each(
				function ( $item ) {
					VarDumper::dump( $item );
				}
			);

		return $this;
	}

	/**
	 * Execute a callback over each item.
	 *
	 * @param  callable $callback
	 * @return $this
	 */
	public function each( callable $callback ) {
		foreach ( $this as $key => $item ) {
			if ( $callback( $item, $key ) === false ) {
				break;
			}
		}

		return $this;
	}

	/**
	 * Execute a callback over each nested chunk of items.
	 *
	 * @param  callable $callback
	 * @return static
	 */
	public function each_spread( callable $callback ) {
		return $this->each(
			function ( $chunk, $key ) use ( $callback ) {
				$chunk[] = $key;

				return $callback( ...$chunk );
			}
		);
	}

	/**
	 * Determine if all items pass the given truth test.
	 *
	 * @param  string|callable $key
	 * @param  mixed           $operator
	 * @param  mixed           $value
	 * @return bool
	 */
	public function every( $key, $operator = null, $value = null ) {
		if ( func_num_args() === 1 ) {
			$callback = $this->value_retriever( $key );

			foreach ( $this as $k => $v ) {
				if ( ! $callback( $v, $k ) ) {
					return false;
				}
			}

			return true;
		}

		return $this->every( $this->operator_for_where( ...func_get_args() ) );
	}

	/**
	 * Get the first item by the given key value pair.
	 *
	 * @param  string $key
	 * @param  mixed  $operator
	 * @param  mixed  $value
	 * @return mixed
	 */
	public function first_where( $key, $operator = null, $value = null ) {
		return $this->first( $this->operator_for_where( ...func_get_args() ) );
	}

	/**
	 * Determine if the collection is not empty.
	 *
	 * @return bool
	 */
	public function is_not_empty() {
		return ! $this->is_empty();
	}

	/**
	 * Run a map over each nested chunk of items.
	 *
	 * @param  callable $callback
	 * @return static
	 */
	public function map_spread( callable $callback ) {
		return $this->map(
			function ( $chunk, $key ) use ( $callback ) {
				$chunk[] = $key;

				return $callback( ...$chunk );
			}
		);
	}

	/**
	 * Run a grouping map over the items.
	 *
	 * The callback should return an associative array with a single key/value pair.
	 *
	 * @param  callable $callback
	 * @return static
	 */
	public function map_to_groups( callable $callback ) {
		$groups = $this->map_to_dictionary( $callback );

		return $groups->map( [ $this, 'make' ] );
	}

	/**
	 * Map a collection and flatten the result by a single level.
	 *
	 * @param  callable $callback
	 * @return static
	 */
	public function flat_map( callable $callback ) {
		return $this->map( $callback )->collapse();
	}

	/**
	 * Map the values into a new class.
	 *
	 * @param  string $class
	 * @return static
	 */
	public function map_into( $class ) {
		return $this->map(
			function ( $value, $key ) use ( $class ) {
				return new $class( $value, $key );
			}
		);
	}

	/**
	 * Get the min value of a given key.
	 *
	 * @param  callable|string|null $callback
	 * @return mixed
	 */
	public function min( $callback = null ) {
		$callback = $this->value_retriever( $callback );

		return $this->map(
			function ( $value ) use ( $callback ) {
				return $callback( $value );
			}
		)->filter(
			function ( $value ) {
				return ! is_null( $value );
			}
		)->reduce(
			function ( $result, $value ) {
				return is_null( $result ) || $value < $result ? $value : $result;
			}
		);
	}

	/**
	 * Get the max value of a given key.
	 *
	 * @param  callable|string|null $callback
	 * @return mixed
	 */
	public function max( $callback = null ) {
		$callback = $this->value_retriever( $callback );

		return $this->filter(
			function ( $value ) {
				return ! is_null( $value );
			}
		)->reduce(
			function ( $result, $item ) use ( $callback ) {
				$value = $callback( $item );

				return is_null( $result ) || $value > $result ? $value : $result;
			}
		);
	}

	/**
	 * "Paginate" the collection by slicing it into a smaller collection.
	 *
	 * @param  int $page
	 * @param  int $per_page
	 * @return static
	 */
	public function for_page( $page, $per_page ) {
		$offset = max( 0, ( $page - 1 ) * $per_page );

		return $this->slice( $offset, $per_page );
	}

	/**
	 * Partition the collection into two arrays using the given callback or key.
	 *
	 * @param  callable|string $key
	 * @param  mixed           $operator
	 * @param  mixed           $value
	 * @return static
	 */
	public function partition( $key, $operator = null, $value = null ) {
		$passed = [];
		$failed = [];

		$callback = func_num_args() === 1
			? $this->value_retriever( $key )
			: $this->operator_for_where( ...func_get_args() );

		foreach ( $this as $key => $item ) {
			if ( $callback( $item, $key ) ) {
				$passed[ $key ] = $item;
			} else {
				$failed[ $key ] = $item;
			}
		}

		return new static( [ new static( $passed ), new static( $failed ) ] );
	}

	/**
	 * Get the sum of the given values.
	 *
	 * @param  callable|string|null $callback
	 * @return mixed
	 */
	public function sum( $callback = null ) {
		if ( is_null( $callback ) ) {
			$callback = function ( $value ) {
				return $value;
			};
		} else {
			$callback = $this->value_retriever( $callback );
		}

		return $this->reduce(
			function ( $result, $item ) use ( $callback ) {
				return $result + $callback( $item );
			},
			0
		);
	}

	/**
	 * Apply the callback if the value is truthy.
	 *
	 * @param  bool|mixed    $value
	 * @param  callable|null $callback
	 * @param  callable|null $default
	 * @return static|mixed
	 */
	public function when( $value, callable $callback = null, callable $default = null ) {
		if ( ! $callback ) {
			return new HigherOrderWhenProxy( $this, $value );
		}

		if ( $value ) {
			return $callback( $this, $value );
		} elseif ( $default ) {
			return $default( $this, $value );
		}

		return $this;
	}

	/**
	 * Apply the callback if the collection is empty.
	 *
	 * @param  callable      $callback
	 * @param  callable|null $default
	 * @return static|mixed
	 */
	public function when_empty( callable $callback, callable $default = null ) {
		return $this->when( $this->is_empty(), $callback, $default );
	}

	/**
	 * Apply the callback if the collection is not empty.
	 *
	 * @param  callable      $callback
	 * @param  callable|null $default
	 * @return static|mixed
	 */
	public function when_not_empty( callable $callback, callable $default = null ) {
		return $this->when( $this->is_not_empty(), $callback, $default );
	}

	/**
	 * Apply the callback if the value is falsy.
	 *
	 * @param  bool          $value
	 * @param  callable      $callback
	 * @param  callable|null $default
	 * @return static|mixed
	 */
	public function unless( $value, callable $callback, callable $default = null ) {
		return $this->when( ! $value, $callback, $default );
	}

	/**
	 * Apply the callback unless the collection is empty.
	 *
	 * @param  callable      $callback
	 * @param  callable|null $default
	 * @return static|mixed
	 */
	public function unless_empty( callable $callback, callable $default = null ) {
		return $this->when_not_empty( $callback, $default );
	}

	/**
	 * Apply the callback unless the collection is not empty.
	 *
	 * @param  callable      $callback
	 * @param  callable|null $default
	 * @return static|mixed
	 */
	public function unless_not_empty( callable $callback, callable $default = null ) {
		return $this->when_empty( $callback, $default );
	}

	/**
	 * Filter items by the given key value pair.
	 *
	 * @param  string $key
	 * @param  mixed  $operator
	 * @param  mixed  $value
	 * @return static
	 */
	public function where( $key, $operator = null, $value = null ) {
		return $this->filter( $this->operator_for_where( ...func_get_args() ) );
	}

	/**
	 * Filter items where the given key is not null.
	 *
	 * @param  string|null $key
	 * @return static
	 */
	public function where_null( $key = null ) {
		return $this->where_strict( $key, null );
	}

	/**
	 * Filter items where the given key is null.
	 *
	 * @param  string|null $key
	 * @return static
	 */
	public function where_not_null( $key = null ) {
		return $this->where( $key, '!==', null );
	}

	/**
	 * Filter items by the given key value pair using strict comparison.
	 *
	 * @param  string $key
	 * @param  mixed  $value
	 * @return static
	 */
	public function where_strict( $key, $value ) {
		return $this->where( $key, '===', $value );
	}

	/**
	 * Filter items by the given key value pair.
	 *
	 * @param  string $key
	 * @param  mixed  $values
	 * @param  bool   $strict
	 * @return static
	 */
	public function where_in( $key, $values, $strict = false ) {
		$values = $this->get_arrayable_items( $values );

		return $this->filter(
			function ( $item ) use ( $key, $values, $strict ) {
				return in_array( data_get( $item, $key ), $values, $strict );
			}
		);
	}

	/**
	 * Filter items by the given key value pair using strict comparison.
	 *
	 * @param  string $key
	 * @param  mixed  $values
	 * @return static
	 */
	public function where_in_strict( $key, $values ) {
		return $this->where_in( $key, $values, true );
	}

	/**
	 * Filter items such that the value of the given key is between the given values.
	 *
	 * @param  string $key
	 * @param  array  $values
	 * @return static
	 */
	public function where_between( $key, $values ) {
		return $this->where( $key, '>=', reset( $values ) )->where( $key, '<=', end( $values ) );
	}

	/**
	 * Filter items such that the value of the given key is not between the given values.
	 *
	 * @param  string $key
	 * @param  array  $values
	 * @return static
	 */
	public function where_not_between( $key, $values ) {
		return $this->filter(
			function ( $item ) use ( $key, $values ) {
				return data_get( $item, $key ) < reset( $values ) || data_get( $item, $key ) > end( $values );
			}
		);
	}

	/**
	 * Filter items by the given key value pair.
	 *
	 * @param  string $key
	 * @param  mixed  $values
	 * @param  bool   $strict
	 * @return static
	 */
	public function where_not_in( $key, $values, $strict = false ) {
		$values = $this->get_arrayable_items( $values );

		return $this->reject(
			function ( $item ) use ( $key, $values, $strict ) {
				return in_array( data_get( $item, $key ), $values, $strict );
			}
		);
	}

	/**
	 * Filter items by the given key value pair using strict comparison.
	 *
	 * @param  string $key
	 * @param  mixed  $values
	 * @return static
	 */
	public function where_not_in_strict( $key, $values ) {
		return $this->where_not_in( $key, $values, true );
	}

	/**
	 * Filter the items, removing any items that don't match the given type.
	 *
	 * @param  string $type
	 * @return static
	 */
	public function where_instance_of( $type ) {
		return $this->filter(
			function ( $value ) use ( $type ) {
				return $value instanceof $type;
			}
		);
	}

	/**
	 * Pass the collection to the given callback and return the result.
	 *
	 * @param  callable $callback
	 * @return mixed
	 */
	public function pipe( callable $callback ) {
		return $callback( $this );
	}

	/**
	 * Pass the collection to the given callback and then return it.
	 *
	 * @param  callable $callback
	 * @return $this
	 */
	public function tap( callable $callback ) {
		$callback( clone $this );

		return $this;
	}

	/**
	 * Create a collection of all elements that do not pass a given truth test.
	 *
	 * @param  callable|mixed $callback
	 * @return static
	 */
	public function reject( $callback = true ) {
		$use_as_callable = $this->use_as_callable( $callback );

		return $this->filter(
			function ( $value, $key ) use ( $callback, $use_as_callable ) {
				return $use_as_callable
				? ! $callback( $value, $key )
				: $value != $callback;
			}
		);
	}

	/**
	 * Return only unique items from the collection array.
	 *
	 * @param  string|callable|null $key
	 * @param  bool                 $strict
	 * @return static
	 */
	public function unique( $key = null, $strict = false ) {
		$callback = $this->value_retriever( $key );

		$exists = [];

		return $this->reject(
			function ( $item, $key ) use ( $callback, $strict, &$exists ) {
				$id = $callback( $item, $key );
				if ( in_array( $id, $exists, $strict ) ) {
					return true;
				}

				$exists[] = $id;
			}
		);
	}

	/**
	 * Return only unique items from the collection array using strict comparison.
	 *
	 * @param  string|callable|null $key
	 * @return static
	 */
	public function unique_strict( $key = null ) {
		return $this->unique( $key, true );
	}

	/**
	 * Take items in the collection until the given condition is met.
	 *
	 * This is an alias to the "takeUntil" method.
	 *
	 * @param  mixed $key
	 * @return static
	 *
	 * @deprecated Use the "takeUntil" method directly.
	 */
	public function until( $value ) {
		return $this->take_until( $value );
	}

	/**
	 * Collect the values into a collection.
	 *
	 * @return \Mantle\Framework\Support\Collection
	 */
	public function collect() {
		return new Collection( $this->all() );
	}

	/**
	 * Get the collection of items as a plain array.
	 *
	 * @return array
	 */
	public function to_array() {
		return $this->map(
			function ( $value ) {
				return $value instanceof Arrayable ? $value->to_array() : $value;
			}
		)->all();
	}

	/**
	 * Convert the object into something JSON serializable.
	 *
	 * @return array
	 */
	public function jsonSerialize() { // phpcs:ignore WordPress.NamingConventions.ValidFunctionName.MethodNameInvalid
		return array_map(
			function ( $value ) {
				if ( $value instanceof JsonSerializable ) {
					return $value->jsonSerialize();
				} elseif ( $value instanceof Jsonable ) {
					return json_decode( $value->to_json(), true );
				} elseif ( $value instanceof Arrayable ) {
					return $value->to_array();
				}

				return $value;
			},
			$this->all()
		);
	}

	/**
	 * Get the collection of items as JSON.
	 *
	 * @param  int $options
	 * @return string
	 */
	public function to_json( $options = 0 ) {
		return json_encode( $this->jsonSerialize(), $options ); // phpcs:ignore WordPress.WP.AlternativeFunctions.json_encode_json_encode
	}

	/**
	 * Get a CachingIterator instance.
	 *
	 * @param  int $flags
	 * @return \CachingIterator
	 */
	public function get_caching_iterator( $flags = CachingIterator::CALL_TOSTRING ) {
		return new CachingIterator( $this->getIterator(), $flags );
	}

	/**
	 * Count the number of items in the collection using a given truth test.
	 *
	 * @param  callable|null $callback
	 * @return static
	 */
	public function count_by( $callback = null ) {
		if ( is_null( $callback ) ) {
			$callback = function ( $value ) {
				return $value;
			};
		}

		return new static(
			$this->group_by( $callback )->map(
				function ( $value ) {
					return $value->count();
				}
			)
		);
	}

	/**
	 * Convert the collection to its string representation.
	 *
	 * @return string
	 */
	public function __toString() {
		return $this->to_json();
	}

	/**
	 * Add a method to the list of proxied methods.
	 *
	 * @param  string $method
	 * @return void
	 */
	public static function proxy( $method ) {
		static::$proxies[] = $method; // phpcs:ignore WordPressVIPMinimum.Variables.VariableAnalysis.StaticOutsideClass
	}

	/**
	 * Dynamically access collection proxies.
	 *
	 * @param  string $key
	 * @return mixed
	 *
	 * @throws \Exception Throw on nonexistent property keys.
	 */
	public function __get( $key ) {
		if ( ! in_array( $key, static::$proxies ) ) { // phpcs:ignore WordPressVIPMinimum.Variables.VariableAnalysis.StaticOutsideClass
			throw new Exception( "Property [{$key}] does not exist on this collection instance." );
		}

		return new Higher_Order_Collection_Proxy( $this, $key );
	}

	/**
	 * Results array of items from Collection or Arrayable.
	 *
	 * @param  mixed $items
	 * @return array
	 */
	protected function get_arrayable_items( $items ) {
		if ( is_array( $items ) ) {
			return $items;
		} elseif ( $items instanceof Enumerable ) {
			return $items->all();
		} elseif ( $items instanceof Arrayable ) {
			return $items->to_array();
		} elseif ( $items instanceof Jsonable ) {
			return json_decode( $items->to_json(), true );
		} elseif ( $items instanceof JsonSerializable ) {
			return (array) $items->jsonSerialize();
		} elseif ( $items instanceof Traversable ) {
			return iterator_to_array( $items );
		}

		return (array) $items;
	}

	/**
	 * Get an operator checker callback.
	 *
	 * @param  string      $key
	 * @param  string|null $operator
	 * @param  mixed       $value
	 * @return \Closure
	 */
	protected function operator_for_where( $key, $operator = null, $value = null ) {
		if ( func_num_args() === 1 ) {
			$value = true;

			$operator = '=';
		}

		if ( func_num_args() === 2 ) {
			$value = $operator;

			$operator = '=';
		}

		return function ( $item ) use ( $key, $operator, $value ) {
			$retrieved = data_get( $item, $key );

			$strings = array_filter(
				[ $retrieved, $value ],
				function ( $value ) {
					return is_string( $value ) || ( is_object( $value ) && method_exists( $value, '__toString' ) );
				}
			);

			if ( count( $strings ) < 2 && count( array_filter( [ $retrieved, $value ], 'is_object' ) ) == 1 ) {
				return in_array( $operator, [ '!=', '<>', '!==' ] );
			}

			switch ( $operator ) {
				default:
				case '=':
				case '==':
					return $retrieved == $value;
				case '!=':
				case '<>':
					return $retrieved != $value;
				case '<':
					return $retrieved < $value;
				case '>':
					return $retrieved > $value;
				case '<=':
					return $retrieved <= $value;
				case '>=':
					return $retrieved >= $value;
				case '===':
					return $retrieved === $value;
				case '!==':
					return $retrieved !== $value;
			}
		};
	}

	/**
	 * Determine if the given value is callable, but not a string.
	 *
	 * @param  mixed $value
	 * @return bool
	 */
	protected function use_as_callable( $value ) {
		return ! is_string( $value ) && is_callable( $value );
	}

	/**
	 * Get a value retrieving callback.
	 *
	 * @param  callable|string|null $value
	 * @return callable
	 */
	protected function value_retriever( $value ) {
		if ( $this->use_as_callable( $value ) ) {
			return $value;
		}

		return function ( $item ) use ( $value ) {
			return data_get( $item, $value );
		};
	}

	/**
	 * Make a function to check an item's equality.
	 *
	 * @param  mixed $value
	 * @return \Closure
	 */
	protected function equality( $value ) {
		return function ( $item ) use ( $value ) {
			return $item === $value;
		};
	}

	/**
	 * Make a function using another function, by negating its result.
	 *
	 * @param  \Closure $callback
	 * @return \Closure
	 */
	protected function negate( Closure $callback ) {
		return function ( ...$params ) use ( $callback ) {
			return ! $callback( ...$params );
		};
	}
}
