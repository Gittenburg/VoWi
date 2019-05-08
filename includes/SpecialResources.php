<?php
class SpecialResources extends SpecialPage {
	# Just for backward compatibility.

	function __construct() {
		parent::__construct( 'Resources' );
	}

	function execute ($arg){
		$this->setHeaders();
		$out = $this->getOutput();

		if (empty($arg)){
			$out->addWikiText(wfMessage('notargettext'));
			$out->setPageTitle(wfMessage('notargettitle'));
			return;
		}

		try {
			$title = Title::newFromTextThrow($arg);
		} catch (MalformedTitleException $e){
			$out->setPageTitle(wfMessage('invalidtitle'));
			return;
		}

		$this->getOutput()->setPageTitle(wfMessage('resourceoverview', $arg));
		$this->getOutput()->redirect($title->getFullURL().'#'.wfMessage('attachments'));
	}
}
