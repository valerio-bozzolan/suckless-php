<?php
# Copyright (C) 2018 Valerio Bozzolan
#
# This program is free software: you can redistribute it and/or modify
# it under the terms of the GNU General Public License as published by
# the Free Software Foundation, either version 3 of the License, or
# (at your option) any later version.
#
# This program is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.
#
# You should have received a copy of the GNU General Public License
# along with this program. If not, see <http://www.gnu.org/licenses/>.

/**
 * To split a result of a query in pages
 */
abstract class QueryPager {

	/**
	 * Name of the page argument
	 */
	const ARG_PAGE = 'p';

	/**
	 * Name of the order by argument
	 */
	const ARG_ORDER_BY = 'order-by';

	/**
	 * Name of the order by direction argument
	 */
	const ARG_DIRECTION = 'dir';

	/**
	 * Page lister arguments
	 *
	 * @var array
	 */
	private $args;

	/**
	 * Number of elements per page
	 *
	 * @var int
	 */
	private $elementsPerPage = 20;

	/**
	 * Argument used as default order by
	 *
	 * @var string
	 */
	private $defaultOrderBy;

	/**
	 * Default order direction
	 *
	 * @var string
	 */
	private $defaultDirection = 'ASC';

	/**
	 * Count cache
	 *
	 * @var int
	 */
	private $_countElements;

	/**
	 * Constructor
	 *
	 * @param $args array Arguments
	 */
	public function __construct( $args = [] ) {
		$this->setArgs( $args ? $args : $_GET );
	}

	/**
	 * Get all the arguments
	 *
	 * return array
	 */
	public function getArgs() {
		return $this->args;
	}

	/**
	 * Set the default order by
	 *
	 * @param $order_by string Order by field
	 * @param $direction string ASC|DESC
	 */
	public function setDefaultOrderBy( $order_by, $direction = 'ASC' ) {
		$this->defaultOrderBy   = $order_by;
		$this->defaultDirection = $direction;
		return $this;
	}

	/**
	 * Get the number of elements per page
	 *
	 * @return int
	 */
	public function getElementsPerPage() {
		return $this->elementsPerPage;
	}

	/**
	 * Set the number of elements per page
	 *
	 * @return self
	 */
	public function setElementsPerPage( $elements ) {
		$this->elementsPerPage = (int) $elements;
		return $this;
	}

	/**
	 * Set the arguments
	 *
	 * @param $args array Arguments
	 * @return self
	 */
	public function setArgs( $args ) {
		foreach( $args as $arg => $value ) {
			$this->setArg( $arg, $value );
		}
		return $this;
	}

	/**
	 * Get a single argument
	 *
	 * @param $arg string Argument name
	 * @param $default string Default value when unexisting
	 * @return string
	 */
	public function getArg( $arg, $default = null ) {
		return isset( $this->args[ $arg ] ) ? $this->args[ $arg ] : $default;
	}

	/**
	 * Check if a certain argument exists
	 *
	 * @param $arg string Argument name
	 * @return mixed
	 */
	public function hasArg( $arg ) {
		return $this->getArg( $arg, false );
	}

	/**
	 * Set a single argument
	 *
	 * @param $arg string Argument name
	 * @param $value string Argument value
	 * @return self
	 */
	public function setArg( $arg, $value ) {
		if( !$value ) {
			$value = null;
		}
		$this->args[ $arg ] = $value;
		return $this;
	}

	/**
	 * Get the current page number
	 *
	 * @return int
	 */
	public function getPage() {
		$p = $this->getArg( static::ARG_PAGE, 1 );
		$p = (int) min( $p, $this->countPages() );
		if( $p < 1 ) {
			$p = 1;
		}
		return $p;
	}

	/**
	 * Check if the current page is the first one
	 *
	 * @return bool
	 */
	public function isFirstPage() {
		return 1 === $this->getPage();
	}

	/**
	 * Check if the current page is the last one
	 *
	 * @return boolo

	 */
	public function isLastPage() {
		return $this->getPage() === $this->countPages();
	}

	/**
	 * Count all the pages
	 *
	 * @return int
	 */
	public function countPages() {
		return (int) ceil( $this->countElements() / $this->getElementsPerPage() );
	}

	/**
	 * Get the name of the ORDER BY field as understood by the API
	 *
	 * @param $default string Default order by
	 * @return string
	 */
	public function getOrderBy() {
		return $this->getArg( static::ARG_ORDER_BY, $this->getDefaultOrderBy() );
	}

	/**
	 * Get the order direction
	 *
	 * @param $default string Default direction
	 * @return string ASC|DESC
	 */
	public function getDirection( $default = null ) {
		if( ! $default && $this->isDefaultOrderBy() ) {
			$default = $this->getDefaultDirection();
		}
		$dir = $this->getArg( static::ARG_DIRECTION, $default );
		return Query::filterDirection( $dir );
	}

	/**
	 * Check if a certain order by is actual
	 *
	 * @param $order_by string Order by field
	 * @param $default_order_by Default order by field
	 * @return bool
	 */
	public function isActualOrderBy( $order_by ) {
		return $order_by === $this->getOrderBy();
	}

	/**
	 * Get all the arguments for an order by toggler
	 *
	 * @param $order_by string Order by field
	 * @param $default_direction Default order direction
	 * @return array Arguments
	 */
	public function getOrderTogglerArgs( $order_by, $default_direction = null ) {
		$args = $this->getArgs();
		if( $this->isActualOrderBy( $order_by ) ) {
			$dir = $this->getDirection( $default_direction );
			$args[ static::ARG_DIRECTION ] = $dir === 'ASC' ? 'DESC' : 'ASC';
		} else {
			$args[ static::ARG_DIRECTION ] = $default_direction;
		}
		$args[ static::ARG_ORDER_BY ] = $order_by;
		unset( $args[ static::ARG_PAGE ] ); // reset actual page
		return $args;
	}

	/**
	 * Get an URL for an URL toggler
	 *
	 * @param $order_by string Order by field
	 * @param $default_direction string Default order direction ASC|DESC
	 * @return string Relative URL
	 */
	public function getOrderTogglerURL( $order_by, $default_direction = null ) {
		return self::argsURL( $this->getOrderTogglerArgs( $order_by, $default_direction ) );
	}

	/**
	 * Print an order toggler link
	 *
	 * @param $label string Label for the link
	 * @param $order_by string Order by field
	 * @param $default_Direction string Default order direction ASC|DESC
	 */
	public function printOrderToggler( $label, $order_by, $default_direction = null ) {
		$arrow = '';
		if( $this->isActualOrderBy( $order_by ) ) {
			$arrow = $this->getDirection( $default_direction ) === 'ASC' ? '↓ ' : '↑ ';
		}
		echo HTML::a(
			$this->getOrderTogglerURL( $order_by, $default_direction ),
			$arrow . $label
		);
	}

	/**
	 * Get all the arguments for a page navigation
	 *
	 * @param $p int Page number
	 * @return array Arguments
	 */
	public function getSpecificPageArgs( $p ) {
		$args = $this->getArgs();
		$args[ static::ARG_PAGE ] = (int) $p;
		return $args;
	}

	/**
	 * Get the URL for a specific page
	 *
	 * @param $p int Page number
	 * @return string Relative URL
	 */
	public function getSpecificPageURL( $p ) {
		return self::argsURL( $this->getSpecificPageArgs( $p ) );
	}

	/**
	 * Count all the elements
	 *
	 * @return int
	 */
	public function countElements() {
		if( ! $this->_countElements ) {
			$this->_countElements = (int)
				$this->createQuery()
					->select( 'COUNT(*) as count' )
			        ->queryValue( 'count' );
		}
		return $this->_countElements;
	}

	/**
	 * Create a Query to match the records in this page scope
	 *
	 * @param $default_order string
	 * @param $default_direction string
	 * @return Query
	 */
	public function createPagedQuery() {
		$n = $this->getElementsPerPage();
		$p = $this->getPage() - 1;
		$q = $this->createQuery();
		$this->applyOrder( $q, $this->getOrderBy(), $this->getDirection() );
		return $q->limit( $n, $n * $p );
	}

	/**
	 * Get an URL to this page from arguments
	 *
	 * @param $args array Arguments
	 * @return string Relative URL
	 */
	public static function argsURL( $args ) {
		return $_SERVER[ 'SCRIPT_URL' ] . '?' . http_build_query( $args );
	}

	/**
	 * Get the name of the default order by argument
	 *
	 * @return null|string
	 */
	protected function getDefaultOrderBy() {
		return $this->defaultOrderBy;
	}

	/**
	 * Check if the actual order by is the default
	 */
	protected function isDefaultOrderBy() {
		return $this->getOrderBy() === $this->getDefaultOrderBy();
	}

	/**
	 * Get the default direction for the default order by
	 *
	 * @return DESC|ASC
	 */
	protected function getDefaultDirection() {
		return $this->defaultDirection;
	}

	/**
	 * Create a Query to match all the records
	 *
	 * @return Query
	 */
	protected abstract function createQuery();

	/**
	 * Apply a certain order to a query
	 *
	 * It should not apply an unknown order.
	 *
	 * If an unknown $order_by is found it returns false to
	 * indicate that the default should be applied.
	 *
	 * In this case, returning void (or true) it means to
	 * don't care about any default ordering, and none will be
	 * applied.
	 *
	 * @param $query Query Object to be modified directly
	 * @param $order_by string
	 * @param $direction string
	 */
	protected abstract function applyOrder( & $query, $order_by, $direction );
}
