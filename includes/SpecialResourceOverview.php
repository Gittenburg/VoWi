<?php
class SpecialResourceOverview extends SpecialFlexiblePrefix {
	const MAX_RESULTS = 10; # against brute force attacks

	function __construct() {
		SpecialPage::__construct( 'ResourceOverview' );
	}

	function execute ($arg){
		parent::execute($arg);
		$this->getOutput()->setPageTitle(wfMessage('resourceoverview', $arg));
	}

	function makeList($items, $currentTitle=null){
		$html = '';
		foreach ($items as $i => $item){
			$pages = Attachments::getPages($item['title']);
			$files = Attachments::getFiles($item['title']);
			$listHTML = Attachments::makeList($item['title'], $pages, $files, $this->getContext());
			$html .= '<h2>'.Linker::linkKnown($item['title']).'</h2>' . $listHTML;
			if ($i > self::MAX_RESULTS)
				break;
		}
		return $html;
	}
}
