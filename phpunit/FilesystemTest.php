<?php
// use the phpunit framework
use PHPUnit\Framework\TestCase;

/**
 * Test Query class
 */
final class FilesystemTest extends TestCase {

	/**
	 * Test the append_dir() function
	 */
	public function testAppendDir1() {
		$this->assertEquals(
			append_dir( 'asd', 'dsa' ),
			'asd/dsa'
		);
	}

	/**
	 * Test the append_dir() function
	 */
	public function testAppendDir2() {
		$this->assertEquals(
			append_dir( 'asd/', 'dsa' ),
			'asd/dsa'
		);
	}

	/**
	 * Test the append_dir() function
	 */
	public function testAppendDir3() {
		$this->assertEquals(
			append_dir( 'asd', '/dsa' ),
			'asd/dsa'
		);
	}

	/**
	 * Test the append_dir() function
	 */
	public function testAppendDir4() {
		$this->assertEquals(
			append_dir( 'asd/', '/dsa' ),
			'asd/dsa'
		);
	}

	/**
	 * Test the append_dir() function with a custom glue
	 */
	public function testAppendDirCustomGlue() {
		$this->assertEquals(
			append_dir( 'asd\\', '\\dsa', '\\' ),
			'asd\\dsa' // intended as "asd\dsa"
		);
	}

}
