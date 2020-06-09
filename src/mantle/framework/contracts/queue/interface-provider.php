<?php
/**
 * Provider interface file.
 *
 * @package Mantle
 */

namespace Mantle\Framework\Contracts\Queue;

/**
 * Queue Provider Contract
 */
interface Provider {
	/**
	 * Push a job to the queue.
	 *
	 * @param mixed  $job Job instance.
	 * @param string $queue Queue name, optional.
	 * @param int    $delay Delay in seconds, optional.
	 * @return bool
	 */
	public function push( $job, string $queue = null, int $delay = null );

	/**
	 * Get the next job in the queue.
	 *
	 * @param string $queue Queue name, optional.
	 * @param int    $count Number of items to return.
	 * @return mixed|false
	 */
	public function pop( string $queue = null, int $count = 1 );
}
