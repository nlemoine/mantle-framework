<?php
namespace Mantle\Tests\Database\Model;

use Mantle\Framework\Database\Model\Attachment;
use WP_UnitTestCase;

/**
 * @todo Replace with the Mantle Testing Framework
 */
class Test_Attachment extends WP_UnitTestCase {
	/**
	 * @var int
	 */
	protected $attachment_id;

	public function setUp() {
		parent::setUp();
		$this->attachment_id = $this->factory->attachment->create_upload_object( DIR_TESTDATA . '/images/test-image.png', 0 );
	}

	public function tearDown() {
		parent::tearDown();
		wp_delete_post( $this->attachment_id );
	}

	public function test_attachment_image_urls() {
		$attachment = Attachment::find( $this->attachment_id );

		$this->assertNotEmpty( $attachment->id() );
		$this->assertNotEmpty( $attachment->url() );
		$this->assertNotEmpty( $attachment->image_url( 'thumbnail' ) );
	}
}
