<?php
# Copyright (C) 2016 Valerio Bozzolan
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
# along with this program.  If not, see <http://www.gnu.org/licenses/>.

class TreeResults {
	/**
	 * @type DynamicQuery
	 */
	private $q;

	private $table;
	private $className;
	private $column_ID;
	private $column_uid;
	private $column_parent;
	private $column_order;
	private $columns_select;

	private $parentQuery;
	private $parametrizedSQLEntries  = null;

	/**
	 * A cache. Assoc array of entries.
	 * entry_ID => query_row()
	 */
	private $entry = [];

	/**
	 * A cache. Assoc array of entry_IDs.
	 * entry_uid => entry_ID, ..
	 */
	private $entry_uid = [];

	/**
	 * A cache. Assoc array to retrieve entry childs.
	 * entry_ID => [entry_ID, ..], ..
	 */
	private $entry_childs = [];

	function __construct($table, $class_name, $args = []) {
		$args = array_replace( [
			'ID'        => "{$table}_ID",
			'uid'       => "{$table}_uid",
			'parent'    => "{$table}_parent",
			'order'     => "{$table}_order",
			'select'    => [],
			'from'      => $table,
			'condition' => null
		], $args );

		$this->table          = $table;
		$this->className      = $class_name;
		$this->column_ID      = $args['ID'];
		$this->column_uid     = $args['uid'];
		$this->column_parent  = $args['parent'];
		$this->column_order   = $args['order'];
		$this->columns_select = $args['select'];

		$this->parentQuery = new DynamicQuery();
		$this->parentQuery->useTable( $args['from'] );
		$this->parentQuery->selectField( $this->columns_select );

		if( $args['condition'] ) {
			$this->parentQuery->appendCondition( $args['condition'] );
		}
	}

	private         $queryEntryUidQuery = null;
	private function queryEntryUid($entry_uid) {
		if( $this->queryEntryUidQuery === null ) {
			$this->queryEntryUidQuery = $this->parentQuery;
			$this->queryEntryUidQuery->appendCondition("`{$this->column_uid}` = '%s'");
			$this->queryEntryUidQuery = $this->queryEntryUidQuery->getQuery();
		}
		return query_row(
			sprintf(
				$this->queryEntryUidQuery,
				esc_sql($entry_identifier)
			),
			$this->className
		);
	}

	private         $queryEntryIDQuery = null;
	private function queryEntryID($entry_ID) {
		if( $this->queryEntryIDQuery === null ) {
			$this->queryEntryIDQuery = $this->parentQuery;
			$this->queryEntryIDQuery->appendCondition("`{$this->column_ID}` = '%d'");
			$this->queryEntryIDQuery = $this->queryEntryIDQuery->getQuery();
		}
		return query_row(
			sprintf(
				$this->queryEntryIDQuery,
				(int) $entry_ID
			),
			$this->className
		);
	}

	private         $queryEntriesParentQuery = null;
	private function queryEntriesParent($entry_parent) {
		if( $this->queryEntriesParentQuery === null ) {
			$this->queryEntriesParentQuery = $this->parentQuery;
			$this->queryEntriesParentQuery->appendCondition("`{$this->column_parent}` = '%d'");
			$this->queryEntriesParentQuery = $this->queryEntriesParentQuery->getQuery();
		}
		return get_results(
			sprintf(
				$this->queryEntriesParentQuery,
				(int) $entry_parent
			),
			$this->className
		);
	}

	private function queryEntries() {
		$q = $this->parentQuery;
		$q->appendOrderBy( $this->column_order );
		return $q->getResults( $this->className );
	}

	/**
	 * Works well with entry_ID or entry_uid
	 * @param mixed $entry_identifier
	 */
	public function getEntry($entry_identifier) {
		if( is_numeric($entry_identifier) ) {
			$entry_ID = (int) $entry_identifier;
			if( $entry_ID <= 0 ) {
				return false;
			}
		} else {
			if( @$this->entry_uid[ $entry_identifier ] ) {
				$entry_ID = $this->entry_uid[ $entry_identifier ];
			} else {
				// Get entry from entry_uid
				$entry = $this->queryEntryUid($entry_identifier);

				if( ! $entry ) {
					$this->entry_uid[ $entry_identifier ] = false;
					return false;
				}

				$this->cacheEntry( $entry );

				return $entry;
			}
		}

		// Now exists $entry_ID
		if( isset( $this->entry[ $entry_ID ] ) ) {
			return $this->entry[ $entry_ID ];
		}

		$entry = $this->queryEntryID( $entry_ID );

		if( ! $entry ) {
			$this->entry[ $entry_ID ] = false;
			return false;
		}

		$entry_ID = $entry->{$this->column_ID};

		$this->entry_uid[ $entry->{$this->column_uid} ] = $entry_ID;
		$this->entry[ $entry_ID ] = $entry;

		return $entry;
	}

	/**
	 * Get the entry_ID from both entry_ID and the entry_uid.
	 * (Using a entry_ID is useful to know it it exists!)
	 */
	public function getEntry_ID($entry_identifier) {
		$entry = $this->getEntry($entry_identifier);
		if($entry) {
			return $entry->{$this->column_ID};
		}
		return false;
	}

	/**
	 * Get one-level child entries.
	 *
	 * @param $entry_identifier The entry_ID or the entry_uid
	 */
	public function getChildEntries($entry_identifier) {
		// Force having a valid entry_ID
		$entry_parent_ID = $this->getEntry_ID( $entry_identifier );

		if( ! $entry_parent_ID ) {
			return false;
		}

		// Get from the cache
		if( isset( $this->entry_childs[ $entry_parent_ID ] ) ) {
			$entry_childs = [];

			$n = count( $this->entry_childs[ $entry_parent_ID ] );
			for($i=0; $i<$n; $i++) {
				$entry_child = $this->getEntry( $this->entry_childs[ $entry_parent_ID ][$i] );

				// A entry is removed during a user-page-load? :^)
				if($entry_child) {
					//if($status === false || $entry_child->{$this->column_status} === $status) {
						$entry_childs[] = $entry_child;
					//}
				}
			}

			return $entry_childs;
		} else {
			echo "Not found!\n";
		}

		// or get from the DB and fill the cache
		$entry_childs = $this->queryEntriesParent( $entry_parent_ID );

		$n = count( $entry_childs );
		for($i=0; $i<$n; $i++) {
			$this->cacheEntry( $entry_childs[$i] );

			//if($status !== false && $entry_childs[$i]->{$this->column_status} !== $status) {
				unset( $entry_childs[$i] );
			//$status}
		}

		return $entry_childs;
	}

	/**
	 * Drammatically grows up performances filling the cache with all the entries.
	 * Use it before calling recursively getChildEntries().
	 *
	 * @see getChildEntries()
	 */
	public function loadEntries() {
		// Reset cache
		$this->entry_childs = [];

		$entries = $this->queryEntries();

		$n = count($entries);
		for($i=0; $i<$n; $i++) {
			$this->cacheEntry( $entries[$i] );
		}
	}

	/**
	 * Insert the entry in the cache.
	 */
	private function cacheEntry($entry) {
		$entry_ID = (int) $entry->{$this->column_ID};
		$entry_parent = (int) $entry->{$this->column_parent};

		$this->entry_uid[ $entry->{$this->column_uid} ] = $entry_ID;
		$this->entry[ $entry_ID ] = $entry;

		// For me this don't have child.
		if( ! isset( $this->entry_childs[ $entry_ID ] ) ) {
			$this->entry_childs[ $entry_ID ] = [];
		}

		if( ! isset( $this->entry_childs[ $entry_parent ]  ) ) {
			$this->entry_childs[ $entry_parent ] = [];
		}

		// OK, this have parents
		if( ! in_array($entry_ID, $this->entry_childs[ $entry_parent ]) ) {
			$this->entry_childs[ $entry_parent ][] = $entry_ID;
		}
	}
}
