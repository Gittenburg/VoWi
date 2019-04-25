<?php

class SpecialAddLVA extends IncludableSpecialPage {
	function __construct() {
		parent::__construct( 'AddLVA' );
	}

	function execute( $par ) {
		global $wgUniNamespaces;
		$this->setHeaders();
		$out = $this->getOutput();

		$form = new HTMLForm([
			'Namespace'=> [
				'label' => wfMessage('addlva-university'),
				'type' => 'select',
				'required' => true,
				'options' => array_combine(array_values($wgUniNamespaces), array_values($wgUniNamespaces))
			],
			'Name' => [
				'label' => wfMessage('addlva-name'),
				'type' => 'text',
				'required' => true
			],
			'Type' => [
				'label' => wfMessage('addlva-type'),
				'type' => 'select',
				'options' => array_combine(VoWi::LVA_TYPES, VoWi::LVA_TYPES)
			],
			'Teachers' => [
				'label' => wfMessage('addlva-teachers'),
				'type' => 'text',
				'required' => true,
				'size' => 15
			],
			'OptionalInfo' => [
				'label' => wfMessage('addlva-optional-info'),
				'type' => 'text',
				'size' => 15
			]
		], $this->getContext());

		$form->setSubmitText(wfMessage('addlva-submit'));
		$form->setSubmitCallback([$this, 'submit']);
		$form->show();
	}

	function submit($data){
		if ($data['OptionalInfo'])
			$data['OptionalInfo'] = ' '.$data['OptionalInfo'];
		$title = Title::newFromText("$data[Namespace]:$data[Name] $data[Type] ($data[Teachers]$data[OptionalInfo])");
		if ($title == null)
			return wfMessage('invalidtitle');
		$this->getOutput()->redirect($title->getFullURL(['action'=>'edit', 'redlink'=>1]));
	}
}
