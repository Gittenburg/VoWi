<?php
class VoWiHooks {

	// We are using this hook, even though it is deprecated,
	// because otherwise we would need to override SearchEngine, which we can't
	// do because there are multiple SearchEngines for different database engines.

	// The code of this function was copied from PrefixSearch::defaultSearchBackend(),
	// just the third condition was added to exclude subpages directly in the SQL query.
	public static function onPrefixSearchBackend( $namespaces, $search, $limit, &$results, $offset ) {
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

		$results =  iterator_to_array( TitleArray::newFromResult( $res ) );
		return false; // for this hook false means ok
	}

	static function onBeforeSortAttachments(&$links){
		$umlaute = ['Ä'=>'Ae', 'Ö'=>'Oe', 'Ü' => 'Ue'];
		foreach ($links as $key => $link)
			if (array_key_exists(mb_substr($key, 0, 1), $umlaute)){
				$links[$umlaute[mb_substr($key, 0, 1)].substr($key,1)] = $link;
				unset($links[$key]);
			}
		uksort($links, function ($a, $b){
			$pattern = '/(.+)(\d{4})(.+)/';
			if (preg_match($pattern, $a, $match_a))
				if (preg_match($pattern, $b, $match_b) && $match_a[1] == $match_b[1])
					if ($match_a[2] != $match_b[2])
						return $match_a[2] < $match_b[2];
			return $a > $b;
		});
		return false;
	}

	static function onShowEmptyAttachmentsSection($title){
		return VoWi::isLVA($title);
	}

	static function onFlexiblePrefixDetails($title, &$details){
		global $wgOutdatedLVACategory;

		# not using $title->getParentCategories() because
		# it adds a language-specific namespace prefix
		$dbr = wfGetDB( DB_REPLICA );

		$res = $dbr->select(
			'categorylinks',
			'cl_to',
			[ 'cl_from' => $title->getArticleID() ],
			__METHOD__
		);
		foreach($res as $row){
			if ($row->cl_to === $wgOutdatedLVACategory){
				$details['veraltet'] = 'veraltet';
				break;
			}
		}

		$count = Attachments::countAttachments($title);
		$txt = "$count ".wfMessage('resources', $count);
		if ($count > 0){
			$title->setFragment('#'.wfMessage('attachments'));
			$txt = Linker::linkKnown($title, $txt);
		}
		$details['attachmentCount'] = $txt;
	}

	static function onFlexiblePrefixBeforeDisplay(&$items){
		usort($items, function ($a, $b){
			return ((int)array_key_exists('veraltet', $a['details'])) . $a['title']
				 > ((int)array_key_exists('veraltet', $b['details'])) . $b['title'];
		});
	}

	static function onBeforePageDisplay(OutputPage $out, Skin $skin) {
		global $wgLVATypes;
		$suffixRegex = '/ (' . implode('|', $wgLVATypes) . ').*/';
		$title = $out->getTitle();
		if (VoWi::isLVA($title) && $out->getRequest()->getText('action', 'view') == 'view'){
			$prefix = preg_replace($suffixRegex, '', $title->getText());

			$specialFlexPrefix = new SpecialFlexiblePrefix();

			$titles =  $specialFlexPrefix->getTitles($prefix);
			if ($titles->count() <= 1)
				# found none or just current page
				return;

			$out->prependHTML($specialFlexPrefix->makeList($specialFlexPrefix->addDetails($titles), $title));
			$out->prependHTML(wfMessage('similarly-named-lvas').' ('
		.Linker::linkKnown(SpecialPage::getTitleFor(
				'ResourceOverview', $prefix), wfMessage('resources')).'):'
			);
		}
	}

	static function onEditFormPreloadText(&$text, $title){
		# these messages aren't provided by this extension
		if (VoWi::isLVA($title))
			$text = wfMessage('editformpreload-lva')->plain();
		elseif (VoWi::isBeispiel($title))
			$text = wfMessage('editformpreload-beispiel')->plain();
	}
}
