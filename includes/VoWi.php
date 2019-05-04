<?php
class VoWi {
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
