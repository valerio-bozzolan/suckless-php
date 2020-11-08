<?php
// use the phpunit framework
use PHPUnit\Framework\TestCase;

/**
 * Test Query class
 */
final class QueryTest extends TestCase {

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

	/**
	 * Test the FROM with an alias
	 */
	public function testFromWithAlias() {
		$query = new Query( new DBDummy() );

		$query->fromAlias( 'one', 'asd' );

		$this->assertEquals(
			'SELECT * FROM `one` AS `asd`',
			$query->getQuery()
		);
	}

	/**
	 * Allow to have a query without the FROM
	 */
	public function testEmptyFrom() {

		$query = new Query( new DBDummy() );

		$query->select( '1' );

		$this->assertEquals(
			'SELECT 1',
			$query->getQuery()
		);
	}

	/**
	 * SELECT with a custom AS argument
	 */
	public function testSelectAs() {

		$query = new Query( new DBDummy() );

		$query->selectAs( '1', 'ciao' );

		$this->assertEquals(
			'SELECT ( 1 ) ciao',
			$query->getQuery()
		);
	}

	/**
	 * Test the SELECT with the empty AS argument
	 */
	public function testSelectAsEmpty() {

		$query = new Query( new DBDummy() );

		$query->selectAs( '1', null );

		$this->assertEquals(
			'SELECT ( 1 )',
			$query->getQuery()
		);
	}

	/**
	 * Test the SELECT with a NOT EXISTS constraint
	 */
	public function testSelectWhereNotExists() {

		$query = new Query( new DBDummy() );
		$sub   = new Query( new DBDummy() );

		$sub->select( 1 );

		$query->whereNotExists( $sub );

		$this->assertEquals(
			'SELECT * WHERE NOT EXISTS (SELECT 1)',
			$query->getQuery()
		);
	}

	/**
	 * Test the SELECT with a NOT EXISTS constraint
	 */
	public function testSelectExists() {

		$query = new Query( new DBDummy() );
		$sub   = new Query( new DBDummy() );

		$sub->select( 1 );

		$query->selectExists( $sub )
		      ->selectNotExists( $sub, 'asd' );

		$this->assertEquals(
			'SELECT ( EXISTS( SELECT 1 ) ), ( NOT EXISTS( SELECT 1 ) ) asd',
			$query->getQuery()
		);
	}
}
