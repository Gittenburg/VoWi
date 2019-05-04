<?php
class VoWiHooks {
	static function onBeforeSortAttachments(&$links){
		$umlaute = ['Ä'=>'Ae', 'Ö'=>'Oe', 'Ü' => 'Ue'];
		foreach ($links as $key => $link)
			if (array_key_exists(mb_substr($key, 0, 1), $umlaute)){
				$links[$umlaute[mb_substr($key, 0, 1)].substr($key,1)] = $link;
				unset($links[$key]);
			}
		uksort($links, function ($a, $b){
			$pattern = '/(.+)(\d{4}.+)/';
			if (preg_match($pattern, $a, $match_a))
				if (preg_match($pattern, $b, $match_b) && $match_a[1] == $match_b[1])
					return $match_a[2] < $match_b[2];
			return $a > $b;
		});
		return false;
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
		$txt = "$count ".wfMessage('resources');
		if ($count > 0){
			$title->setFragment('#'.wfMessage('attachments-noun'));
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
		$suffixRegex = '/ (' . implode('|', VoWi::LVA_TYPES) . ').*/';
		$title = $out->getTitle();
		if (VoWi::isLVA($title) && $out->getRequest()->getText('action', 'view') == 'view'){
			$strippedTitle = Title::newFromText(preg_replace($suffixRegex, '', $title->getPrefixedText()));

			$specialFlexPrefix = new SpecialFlexiblePrefix();

			$titles =  $specialFlexPrefix->getTitles($strippedTitle);
			if ($titles->count() === 1)
				# only found current page
				return;

			$out->prependHTML(Linker::linkKnown(SpecialPage::getTitleFor(
				'Resources', $strippedTitle), wfMessage('resources-above-pages')));

			$out->prependHTML($specialFlexPrefix->makeList($specialFlexPrefix->addDetails($titles), $title));
		}
	}

	# the following messages aren't provided by this extension
	static function onEditFormPreloadText(&$text, $title){
		if (VoWi::isLVA($title))
			$text = wfMessage('editformpreload-lva')->plain();
		elseif (VoWi::isBeispiel($title))
			$text = wfMessage('editformpreload-beispiel')->plain();
	}

	static function onEditPage_showEditForm_initial(&$editor, &$out){
		if (VoWi::isLVA($out->getTitle()))
			$out->addWikiText(wfMessage('editformhint-lva')->plain());
		elseif (VoWi::isBeispiel($out->getTitle()))
			$out->addWikiText(wfMessage('editformhint-beispiel')->plain());
	}
}
