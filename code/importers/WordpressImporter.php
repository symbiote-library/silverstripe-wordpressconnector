<?php
/**
 * @package silverstripe-wordpressimporter
 */
class WordpressImporter extends ExternalContentImporter {

	public function __construct() {
		$post = new WordpressPostTransformer();
		$post->setImporter($this);

		$this->contentTransforms['page'] = new WordpressPageTransformer();
		$this->contentTransforms['post'] = $post;
	}

	public function getExternalType($item) {
		switch ($item->class) {
			case 'WordpressPageContentItem': return 'page';
			case 'WordpressPostContentItem': return 'post';
		}
	}

}