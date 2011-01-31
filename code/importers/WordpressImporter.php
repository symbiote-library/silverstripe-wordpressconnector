<?php
/**
 * @package silverstripe-wordpressimporter
 */
class WordpressImporter extends ExternalContentImporter {

	public function __construct() {
		$this->contentTransforms['page'] = new WordpressPageTransformer();
		$this->contentTransforms['post'] = new WordpressPostTransformer();
	}

	public function getExternalType($item) {
		switch ($item->class) {
			case 'WordpressPageContentItem': return 'page';
			case 'WordpressPostContentItem': return 'post';
		}
	}

}