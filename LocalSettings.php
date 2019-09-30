<?php
## Uploads
$wgEnableUploads = true;
$wgGroupPermissions['*']['upload'] = true;
$wgGroupPermissions['*']['reupload'] = true;
$wgCountCategorizedImagesAsUsed = true;

## University namespaces
function defineNS($const, $id, $text){
	global $wgExtraNamespaces;
	define($const, $id);
	$wgExtraNamespaces[$id] = $text;
	define($const . '_TALK', $id + 1);
	$wgExtraNamespaces[$id+1] = $text . '_Diskussion';
}

$vowiUniNamespaces = []; # id => text

function defineUniNS($const, $id, $text){
	global $wgNamespacesWithSubpages;
	global $vowiUniNamespaces;
	$vowiUniNamespaces[$id] = $text;
	$text = str_replace(' ', '_', $text);
	defineNS($const, $id, $text);
	defineNS($const.'_NAV', $id + 1000, $text.'_Nav');
	$wgNamespacesWithSubpages[$id] = 1;
	$wgNamespacesWithSubpages[$id + 1000] = 1;
}

defineUniNS('NS_TU_WIEN',  3000, 'TU Wien');
defineUniNS('NS_UNI_WIEN', 3002, 'Uni Wien');
defineUniNS('NS_MU_WIEN',  3004, 'MU Wien');
defineUniNS('NS_SONSTIGE', 3006, 'Sonstige');

## Soft dependencies
wfLoadExtension( 'MobileFrontend' );
wfLoadSkin( 'MinervaNeue' );
$wgMFDefaultSkinClass = 'SkinMinerva';

## Hard dependencies
wfLoadExtension( 'Attachments' );
$wgAttachmentsNamespaces = $vowiUniNamespaces;

wfLoadExtension( 'FlexiblePrefix' );
$wgFlexiblePrefixNamespaces = array_keys($vowiUniNamespaces);

## Load this extension
wfLoadExtension( 'VoWi' );
$wgUniNamespaces = $vowiUniNamespaces;
$wgSearchType = 'VoWiSearch';
