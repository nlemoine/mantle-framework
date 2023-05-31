<?php

namespace Mantle\Contracts\Framework;

/**
 * Boot Manager
 *
 * Used to instantiate the application and load the framework.
 */
interface Boot_Manager {
	/**
	 * Boot the application given the current context.
	 *
	 * @return void
	 */
	public function boot(): void;
}
