<?php

class SpecialAddLVA extends IncludableSpecialPage {
	function __construct() {
		parent::__construct( 'AddLVA' );
	}

	function execute( $par ) {
		global $wgUniNamespaces, $wgLVATypes;
		$this->setHeaders();
		$out = $this->getOutput();

		$form = new HTMLForm([
			'Namespace'=> [
				'label-message' => 'addlva-university',
				'type' => 'select',
				'required' => true,
				'options' => array_combine(array_values($wgUniNamespaces), array_values($wgUniNamespaces))
			],
			'Name' => [
				'label-message' => 'addlva-name',
				'type' => 'text',
				'required' => true,
			],
			'Type' => [
				'label-message' => 'addlva-type',
				'type' => 'select',
				'options' => array_combine($wgLVATypes, $wgLVATypes)
			],
			'Teachers' => [
				'label-message' => 'addlva-teachers',
				'type' => 'text',
				'required' => true,
				'size' => 15,
				'placeholder-message' => 'addlva-teachers-example'
			]
		], $this->getContext());

		$form->setSubmitText(wfMessage('addlva-submit'));
		$form->setSubmitCallback([$this, 'submit']);
		$form->show();
	}

	const INVALID_CHARS = [':', '/', '(', ')'];

	function submit($data){
		foreach (self::INVALID_CHARS as $char)
			if (strpos($data['Name'].$data['Teachers'], $char) !== FALSE)
				return wfMessage('addlva-invalidchars', implode(self::INVALID_CHARS,', '));
		$title = Title::newFromText("$data[Namespace]:$data[Name] $data[Type] ($data[Teachers])");
		if ($title == null)
			return wfMessage('invalidtitle');
		$this->getOutput()->redirect($title->getFullURL(['action'=>'edit', 'redlink'=>1]));
	}
}
