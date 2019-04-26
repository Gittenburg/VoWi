<?php
class SpecialResources extends SpecialFlexiblePrefix {
	const MAX_RESULTS = 10; # against brute force attacks

	function __construct() {
		SpecialPage::__construct( 'resources' );
	}

	function makeList($items, $currentTitle=null){
		$html = '';
		foreach ($items as $i => $item){
			$listHTML = Attachments::makeList($item['title'], $this->getContext());
			if ($listHTML)
				$html .= '<h2>'.Linker::linkKnown($item['title']).'</h2>' . $listHTML;
			if ($i > self::MAX_RESULTS)
				return $html;
		}
		return $html;
	}
}
