# VoWi extension

This extension is specially tailored for https://vowi.fsinf.at/, depends on [Attachments](https://github.com/Gittenburg/Attachments) and [FlexiblePrefix](https://github.com/Gittenburg/FlexiblePrefix) and does the following:

* hooks into FlexiblePrefix to display attachment counts and if an LVA is outdated
* prepends FlexiblePrefix to every LVA page (the prefix is everything up to the LVA type)
* provides `Special:AddLVA` to create LVA pages
* provides `Special:Resources` (like `Special:FlexiblePrefix` but shows attachments)
* customizes attachments sorting (up to four digits ascending, then descending)
* hooks into the edit form to preload configurable text when creating LVAs and Beispiel pages
* hooks into the edit form to provide configurable hints when editing LVA and Beispiel pages

For an examplary configuration, refer to `LocalSettings.php`.

## Credits

This extension replaces [SimilarNamedArticlesHeader](https://fs.fsinf.at/wiki/SimilarNamedArticlesHeader), [CreatePage](https://fs.fsinf.at/wiki/CreatePage) and [NewArticleTemplate](https://www.mediawiki.org/wiki/Extension:NewArticleTemplate) by Mathias Ertl.
