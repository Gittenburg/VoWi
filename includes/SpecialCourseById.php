<?php
use \SMW\Query\PrintRequest as PrintRequest;

class SpecialCourseById extends SpecialPage {
	const USAGE = "URL parameters:\n"
		. "* <code>ns</code> ... the name of the university namespace\n"
		. "* <code>id</code> ... a [[Property:Hat Kurs-ID]] value\n"
		."[{{fullurl:Special:CourseById|ns=TU_Wien&id=123456}} Example]";

	function __construct() {
		parent::__construct( 'CourseById' );
	}

	function execute( $arg ) {
		$this->setHeaders();
		$out = $this->getOutput();
		$req = $this->getRequest();

		$ns = $this->getRequest()->getText('ns');
		$id = $this->getRequest()->getText('id');

		if (empty($id)){
			$out->addWikiText(self::USAGE);
			return;
		}

		$store = \SMW\StoreFactory::getStore();
		$params = SMWQueryProcessor::getProcessedParams(['format'=>'ul']);
		$query = SMWQueryProcessor::createQuery("[[$ns:+]][[Hat Kurs-ID::$id]]",
			$params, SMWQueryProcessor::SPECIAL_PAGE, "ul", [new PrintRequest(PrintRequest::PRINT_THIS, '')]);
		$res = $store->getQueryResult($query);

		if ($res->getCount() == 1){
			$out->redirect(
				$res->getResults()[0]->getTitle()->getFullURL()
			);
		} else if ($res->getCount() > 1){
			$out->setPageTitle(wfMessage('coursebyid-multiple-results'));
			$out->addWikiText(SMWQueryProcessor::getResultPrinter('ul')->getResult($res, $params, SMW_OUTPUT_WIKI));

		} else {
			$out->setPageTitle(wfMessage('coursebyid-not-found'));
			$out->addWikiText(wfMessage('coursebyid-not-found-text')->text());
		}
	}
}
