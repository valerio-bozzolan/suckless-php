<?php
// use the phpunit framework
use PHPUnit\Framework\TestCase;

/**
 * Test Query class
 */
final class TestQuery extends TestCase {

	/**
	 * Test the bracket and glue
	 */
	public function testBracketsAndGlue() {

		// create a dummy query builders
		$query  = new Query( new DBDummy() );

		$query->select( 'field' );
		$query->from( 'table'  );

		$query->where( 'condition1' );

		$query->openBracket()
		      ->setGlue( 'OR' );

			$query->where( 'condition2' );

				$query->openBracket();

				$query->where( 'condition3' );
				$query->where( 'condition4' );

				$query->closeBracket();

			$query->where( 'condition5' );

		$query->closeBracket()
		      ->setGlue( 'AND' );

		$query->where( 'condition6' );
		$query->where( 'condition7' );

		$this->assertEquals(
			'SELECT field FROM `table` AS `table` WHERE condition1 AND (condition2 OR (condition3 OR condition4) OR condition5) AND condition6 AND condition7',
			$query->getQuery()
		);
	}

	/**
	 * Test the EXISTS
	 */
	public function testExistsCondition() {

		// create a couple for dummy query builders
		$query  = new Query( new DBDummy() );
		$query2 = new Query( new DBDummy() );

		$query->from( 'one' );

		$query2->from( 'two' )
		       ->whereInt( 'two_field', 2 )
		       ->compare(  'one.a', '<', 'two.b' );

		$query->whereExists( $query2 );

		$this->assertEquals(
			'SELECT * FROM `one` AS `one` WHERE EXISTS (SELECT * FROM `two` AS `two` WHERE two_field = 2 AND one.a < two.b)',
			$query->getQuery()
		);
	}

}
