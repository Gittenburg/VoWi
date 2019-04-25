<?php
class SpecialResources extends SpecialFlexiblePrefix {
	const MAX_RESULTS = 10; # against brute force attacks

	function __construct() {
		SpecialPage::__construct( 'resources' );
	}

	function makeList($titles, $currentTitle=null){
		$html = '';
		foreach ($titles as $i => $title){
			$listHTML = Attachments::makeList($title, $this->getContext());
			if ($listHTML)
				$html .= '<h2>'.Linker::linkKnown($title).'</h2>' . $listHTML;
			if ($i > self::MAX_RESULTS)
				return $html;
		}
		return $html;
	}
}
