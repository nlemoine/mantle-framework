<?php
/**
 * Boot_Manager interface file
 *
 * @package Mantle
 */

namespace Mantle\Contracts\Framework;

use Closure;

/**
 * Boot Manager
 *
 * Used to instantiate the application and load the framework.
 */
interface Boot_Manager {
	/**
	 * Boot the application given the current context.
	 *
	 * @return static
	 */
	public function boot(): static;

	/**
	 * Bind to the container before booting.
	 *
	 * @param string              $abstract Abstract to bind.
	 * @param Closure|string|null $concrete Concrete to bind.
	 * @return static
	 */
	public function bind( string $abstract, Closure|string|null $concrete ): static;
}
