<?php

class VoWiSearch extends SearchEngine {
	protected function completionSearchBackend( $search ) {
		$backend = new VoWiTitlePrefixSearch;
		$results =  $backend->defaultSearchBackend( $this->namespaces, $search, $this->limit, $this->offset );
		return SearchSuggestionSet::fromTitles( $results );
	}
}

class VoWiTitlePrefixSearch extends TitlePrefixSearch {
	// The code of this function was copied from PrefixSearch::defaultSearchBackend(),
	// just the third condition was added to exclude subpages directly in the SQL query.

	public function defaultSearchBackend( $namespaces, $search, $limit, $offset ) {
		// Backwards compatability with old code. Default to NS_MAIN if no namespaces provided.
		if ( $namespaces === null ) {
			$namespaces = [];
		}
		if ( !$namespaces ) {
			$namespaces[] = NS_MAIN;
		}

		// Construct suitable prefix for each namespace. They differ in cases where
		// some namespaces always capitalize and some don't.
		$prefixes = [];
		foreach ( $namespaces as $namespace ) {
			// For now, if special is included, ignore the other namespaces
			if ( $namespace == NS_SPECIAL ) {
				return $this->specialSearch( $search, $limit, $offset );
			}

			$title = Title::makeTitleSafe( $namespace, $search );
			// Why does the prefix default to empty?
			$prefix = $title ? $title->getDBkey() : '';
			$prefixes[$prefix][] = $namespace;
		}

		$dbr = wfGetDB( DB_REPLICA );
		// Often there is only one prefix that applies to all requested namespaces,
		// but sometimes there are two if some namespaces do not always capitalize.
		$conds = [];
		foreach ( $prefixes as $prefix => $namespaces ) {
			$condition = [
				'page_namespace' => $namespaces,
				'page_title' . $dbr->buildLike( $prefix, $dbr->anyString() ),
				'NOT page_title' . $dbr->buildLike( $dbr->anyString(), '/', $dbr->anyString())
			];
			$conds[] = $dbr->makeList( $condition, LIST_AND );
		}

		$table = 'page';
		$fields = [ 'page_id', 'page_namespace', 'page_title' ];
		$conds = $dbr->makeList( $conds, LIST_OR );
		$options = [
			'LIMIT' => $limit,
			'ORDER BY' => [ 'page_title', 'page_namespace' ],
			'OFFSET' => $offset
		];

		$res = $dbr->select( $table, $fields, $conds, __METHOD__, $options );

		return iterator_to_array( TitleArray::newFromResult( $res ) );
	}
}
