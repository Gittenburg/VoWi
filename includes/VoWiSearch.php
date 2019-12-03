<?php

class_alias(SearchEngineFactory::getSearchEngineClass(wfGetDB(DB_REPLICA)), 'DatabaseSearch');

class VoWiSearch extends DatabaseSearch {
	protected function completionSearchBackend( $search ) {
		$backend = new VoWiTitlePrefixSearch;
		$results =  $backend->defaultSearchBackend( $this->namespaces, $search, $this->limit, $this->offset );
		return SearchSuggestionSet::fromTitles( $results );
	}
}

class VoWiTitlePrefixSearch extends TitlePrefixSearch {
	// The code of this function was initially copied from PrefixSearch::defaultSearchBackend().

	public function defaultSearchBackend( $namespaces, $search, $limit, $offset ) {
		global $wgContLang;
		global $wgOutdatedLVACategory;
		global $wgUniNamespaces;
		// Backwards compatability with old code. Default to NS_MAIN if no namespaces provided.
		if ( $namespaces === null ) {
			$namespaces = [];
		}
		if ( !$namespaces ) {
			$namespaces[] = NS_MAIN;
		}

		$prefix = $wgContLang->caseFold( $search );
		$dbr = wfGetDB( DB_REPLICA );
		$conds = [];

		foreach ( $namespaces as $namespace ) {
			// For now, if special is included, ignore the other namespaces
			if ( $namespace == NS_SPECIAL ) {
				return $this->specialSearch( $search, $limit, $offset );
			}

			$condition = [
				'page_namespace' => $namespace,

				// use Extension:TitleKey for case-insensitive searches
				$dbr->makeList([
					'tk_key'   . $dbr->buildLike($dbr->anyString(), $prefix, $dbr->anyString()),
					'pp_value' . $dbr->buildLike($dbr->anyString(), strtolower($search), $dbr->anyString())
				], LIST_OR)
			];

			if (strpos($search, '/') === false)
				// exclude subpages by default because we have so many
				$condition[] = 'NOT page_title' . $dbr->buildLike( $dbr->anyString(), '/', $dbr->anyString());

			$conds[] = $dbr->makeList( $condition, LIST_AND );
		}

		$uniNamespaceIds = join(',', array_keys($wgUniNamespaces));
		$NS_FILE = NS_FILE;

		$table = ['page', 'outdated' => 'categorylinks', 'tk' => 'titlekey', 'pp' => 'page_props'];
		$fields = [ 'page_id', 'page_namespace', 'page_title'];
		$conds = $dbr->makeList( $conds, LIST_OR );
		$options = [
			'LIMIT' => $limit,
			'ORDER BY' => [
				'if(cl_from is NULL,0,1)', // up to date courses first
				"CASE
					WHEN page_namespace in ($uniNamespaceIds) THEN 20
					WHEN page_namespace = $NS_FILE THEN 30
					ELSE page_namespace
				END", // prefer course pages over files
				"CASE
					WHEN tk_key {$dbr->buildLike($prefix, $dbr->anyString())} THEN 0
					ELSE 1
				END", // prefix matches first
				'page_title',
				'page_namespace'
			],
			'OFFSET' => $offset
		];
		$join_conds = [
			'outdated' => ['LEFT JOIN', ['page_id=cl_from', 'cl_to'=>$wgOutdatedLVACategory]],
			'tk' => ['JOIN', ['tk_page=page_id']],
			'pp' => ['LEFT JOIN', ['page_id=pp_page', 'pp_propname="abbreviation"']]
		];

		$res = $dbr->select( $table, $fields, $conds, __METHOD__, $options, $join_conds );

		return iterator_to_array( TitleArray::newFromResult( $res ) );
	}
}
