# VoWi extension

This extension is specially tailored for https://vowi.fsinf.at/, depends on [Attachments](https://github.com/Gittenburg/Attachments) and [FlexiblePrefix](https://github.com/Gittenburg/FlexiblePrefix) and does the following:

* hooks into FlexiblePrefix to display attachment counts and if an LVA is outdated
* prepends FlexiblePrefix to every LVA page (the prefix is everything up to the LVA type)
* provides `Special:AddLVA` to create LVA pages
* provides `Special:Resources` (like `Special:FlexiblePrefix` but shows attachments)
* customizes attachments sorting, so that correctly formatted dates (`2020`, `2020W`, `2020S`, `2020-01-28`) are sorted newest to oldest
* hooks into the edit form to preload configurable text when creating LVAs (`MediaWiki:editformpreload-lva`) and Beispiel pages (`MediaWiki:editformpreload-beispiel`)
* provides `Special:CourseById` which redirects you to a LVA page given its course id and namespace (requires [Semantic MediaWiki](https://www.semantic-mediawiki.org/)).
* provides vastly improved search autocompletion (`$wgSearchType = 'VoWiSearch';`), requires [Extension:TitleKey](https://www.mediawiki.org/wiki/Extension:TitleKey)
	* subpages are excluded (except if the search query includes a slash)
	* case-insensitive due to Extension:TitleKey
	* pages in `$wgOutdatedLVACategory` are demoted
	* pages in `$wgUniNamespaces` are preferred over files
	* supports matches in the middle of titles (except for files)
	* indexes abbreviations set with `{{ABBREVIATION:<text>}}`
* provides a `<searchinput>` tag to put a search input with autocompletion on a page
* provides a `#toss` parser function
* provides a `#navdisplaytitle` parser function, that only works on `_Nav` namespaces

## Setup

For an examplary configuration, refer to `ExampleLocalSettings.php`.

## Credits

This extension replaces [SimilarNamedArticlesHeader](https://fs.fsinf.at/wiki/SimilarNamedArticlesHeader), [CreatePage](https://fs.fsinf.at/wiki/CreatePage) and [NewArticleTemplate](https://www.mediawiki.org/wiki/Extension:NewArticleTemplate) by Mathias Ertl.
