<?php
/**
 * @package silverstripe-wordpressimporter
 */
class WordpressImporter extends ExternalContentImporter {

	public function __construct() {
		$this->contentTransforms['page'] = new WordpressPageTransformer();
	}

	public function getExternalType($item) {
		return 'page';
	}

}