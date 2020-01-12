<?php
define("TOSSAPI", "https://toss.fsinf.at/api");

class VoWiHooks {
	public static function onParserFirstCallInit( Parser $parser ) {
		$parser->setFunctionHook('abbreviation', [ self::class, 'renderAbbreviation'], SFH_NO_HASH);
		$parser->setFunctionHook('toss', [ self::class, 'renderTOSS']);
		$parser->setFunctionHook( 'navdisplaytitle', [ self::class, 'renderNavDisplayTitle' ]);
		$parser->setHook( 'searchinput', [ self::class, 'renderSearchInput' ]);
	}

	public static function renderNavDisplayTitle($parser, $title){
		if (substr($parser->getTitle()->getNsText(), -4) == '_Nav')
			$parser->getOutput()->setDisplayTitle($title);
	}

	public static function currentSemester(){
		$year = date('Y');
		if (date('m') < 3)
			return $year - 1 . "W";
		elseif (date('m') < 10)
			return "{$year}S";
		else
			return "{$year}W";
	}

	public static function extractParams( array $options ) {
		$results = [];
		foreach ( $options as $option ) {
			$pair = array_map( 'trim', explode( '=', $option, 2 ) );
			if ( count( $pair ) === 2 ) {
				$results[ $pair[0] ] = $pair[1];
			}
			if ( count( $pair ) === 1 ) {
				$results[ $pair[0] ] = true;
			}
		}
		return $results;
	}

	public static function renderTOSS(Parser $parser, $code){
		$semester = self::currentSemester();
		$current_doc = @file_get_contents(TOSSAPI . "/courses/$code-$semester");
		$courses_doc = @file_get_contents(TOSSAPI . "/courses?code=$code");

		if ($courses_doc === false){
			return 'Could not contact TOSS API.';
		}
		$courses = json_decode($courses_doc, true);
		if (empty($courses)){
			return "TOSS couldn't find this course.";
		}

		if ($current_doc === false){
			$course = $courses[0];
		} else {
			$course = json_decode($current_doc, true);
		}

		$lecturers = json_decode(file_get_contents(TOSSAPI . $course['machine']['lecturers']), true);
		$instanceof = json_decode(file_get_contents(TOSSAPI . $course['machine']['instanceof']), true);

		$params = self::extractParams(array_slice( func_get_args(), 2 ));

		$lecturers_wiki = $params['vortragende'] ?? join(', ', array_map(function($lecturer){
			return "[[tiss.person:{$lecturer['tiss_id']}|{$lecturer['firstname']} {$lecturer['lastname']}]]";
		}, $lecturers));
		unset($params['vortragende']);

		$modules_wiki = join("\n", array_map(function($instance){
			$code = 'E' . str_replace(' ', '', $instance['catalog_code']);
			$name = strstr($instance['group_name'], ' ');
			$wahl = $instance['semester'] ? '' : 'wahl=1';
			return "{{Zuordnung|$code|$name|$wahl}}";
		}, $instanceof));

		$homepage = $params['homepage'] ?? $course['human']['homepage'] ?? '';
		unset($params['homepage']);

		$ects = $params['ects'] ?? $course['ects'];
		unset($params['ects']);

		$sprache = $params['sprache'] ?? $course['language'];
		unset($params['sprache']);

		$args = '';
		foreach ($params as $key => $val)
			$args .= "|$key=$val";
		return ["{{LVA-Daten
|id=$code
|ects=$ects
|vortragende=$lecturers_wiki
|homepage=$homepage
|sprache=$sprache
|zuordnungen=$modules_wiki
$args
}}", 'noparse'=>false];
	}

	public static function renderAbbreviation( Parser $parser, $abbr) {
		$parser->getOutput()->setProperty("abbreviation", strtolower($abbr));
	}

	public static function renderSearchInput( $input, $args, $parser) {
		global $wgScript;
		$label = wfMessage('search');
		// the mediawiki.searchSuggest module enables suggestions for inputs with the mw-searchInput class
		return "<form action='$wgScript'><input type=hidden name=title value=Special:Search>
			<input autofocus name=search class=mw-searchInput> <button>$label</button></form>";
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
