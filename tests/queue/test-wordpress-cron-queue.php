<?php
namespace Mantle\Tests\Queue;

use Mantle\Testing\Framework_Test_Case;
use Mantle\Contracts\Queue\Can_Queue;
use Mantle\Contracts\Queue\Job;
use Mantle\Queue\Dispatchable;
use Mantle\Queue\Queueable;
use Mantle\Queue\Wp_Cron_Scheduler;
use Mantle\Testing\Concerns\Refresh_Database;

class Test_WordPress_Cron_Queue extends Framework_Test_Case {
	use Refresh_Database;

	public function test_wordpress_queue_job_from_class_async() {
		$_SERVER['__example_job'] = false;

		Example_Job::dispatch();

		$this->assertInCronQueue( Example_Job::class );
		$this->assertFalse( $_SERVER['__example_job'] );

		// Force the cron to be dispatched which will execute
		// the queued job.
		$this->dispatch_cron( Wp_Cron_Scheduler::EVENT );

		$this->assertTrue( $_SERVER['__example_job'] );
	}

	public function test_wordpress_queue_job_from_class_sync() {
		$this->assertNotInCronQueue( Example_Job::class );

		$_SERVER['__example_job'] = false;

		Example_Job::dispatch_now();

		$this->assertTrue( $_SERVER['__example_job'] );
		$this->assertNotInCronQueue( Example_Job::class );
	}

	// public function test_wordpress_queue_job_from_closure_async() {}
	// public function test_wordpress_queue_job_from_closure_sync() {}
}

class Example_Job implements Job, Can_Queue {
	use Queueable, Dispatchable;

	public function handle() {
		$_SERVER['__example_job'] = true;
	}
}
