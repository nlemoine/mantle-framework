<?php
/**
 * Relation class file.
 *
 * @package Mantle
 */

namespace Mantle\Framework\Database\Model\Relations;

use Mantle\Framework\Database\Model\Model;
use Mantle\Framework\Database\Query\Builder;
use Mantle\Framework\Support\Forward_Calls;

/**
 * Relation base class.
 */
abstract class Relation {
	use Forward_Calls;

	/**
	 * Internal taxonomy for post-to-post relationships.
	 *
	 * @var string
	 */
	public const RELATION_TAXONOMY = 'mantle_relationship';

	/**
	 * Query Builder instance.
	 *
	 * @var Builder
	 */
	protected $query;

	/**
	 * Flag if the relation uses terms.
	 *
	 * @var bool|null
	 */
	protected $uses_terms;

	/**
	 * Model's relationship name.
	 *
	 * @var string|null
	 */
	protected $relationship;

	/**
	 * Create a new relation instance.
	 *
	 * @param Builder   $query Query builder instance.
	 * @param Model     $parent Model instance.
	 * @param bool|null $uses_terms Flag if the relation uses terms.
	 * @param string    $relationship Relationship name, optional.
	 */
	public function __construct( Builder $query, Model $parent, ?bool $uses_terms = null, string $relationship = null ) {
		$this->query   = $query;
		$this->parent  = $parent;
		$this->related = $query->get_model();

		if ( ! is_null( $uses_terms ) ) {
			$this->uses_terms( $uses_terms );
		}

		$this->relationship = $relationship ?: $this->guess_relationship();
	}

	/**
	 * Set the query constraints to apply to the query.
	 */
	abstract public function add_constraints();

	/**
	 * Get the results of the relationship.
	 *
	 * @return mixed
	 */
	abstract public function get_results();

	/**
	 * Handle dynamic method calls to the relationship.
	 *
	 * @param string $method Method name.
	 * @param array  $parameters Method arguments.
	 * @return mixed
	 */
	public function __call( string $method, array $parameters ) {
		$this->add_constraints();

		$result = $this->forward_call_to( $this->query, $method, $parameters );

		if ( $this->query === $result ) {
			return $this;
		}

		return $result;
	}

	/**
	 * Flag if the relation uses terms.
	 *
	 * @param bool $uses Flag if the relation uses or doesn't use terms.
	 * @return static
	 */
	public function uses_terms( bool $uses = true ) {
		$this->uses_terms = $uses;
		return $this;
	}

	/**
	 * Guess the name of the relationship.
	 *
	 * @return string|null
	 */
	protected function guess_relationship() : ?string {
		$trace = debug_backtrace( DEBUG_BACKTRACE_IGNORE_ARGS, 5 ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_debug_backtrace

		foreach ( $trace as $item ) {
			if ( is_subclass_of( $item['class'], Model::class ) ) {
				return $item['function'];
			}
		}

		return null;
	}
}
