<?php
class VoWi {
	const LVA_TYPES = ['VU', 'VL', 'VO', 'VD', 'UE', 'SE', 'PS', 'PR', 'LU', 'EX', 'AU', 'AG'];

	static function isLVA($title){
		global $wgUniNamespaces;
		return array_key_exists($title->getNamespace(), $wgUniNamespaces) && !$title->isSubpage();
	}

	static function isBeispiel($title){
		global $wgUniNamespaces;
		return array_key_exists($title->getNamespace(), $wgUniNamespaces) && $title->isSubpage()
			&& strpos($title->getSubpageText(), 'Beispiel') === 0;
	}
}
