<?php
/**
 * Queue Helpers
 *
 * @package Mantle
 */

namespace Mantle\Queue;

if ( ! function_exists( 'dispatch' ) ) {
	/**
	 * Dispatch a job to the queue.
	 *
	 * @param \Mantle\Contracts\Queue\Job $job Job instance.
	 * @return Pending_Dispatch
	 */
	function dispatch( $job ): Pending_Dispatch {
		return new Pending_Dispatch( $job );
	}
}
