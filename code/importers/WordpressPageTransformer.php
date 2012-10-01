<?php
/**
 * Transforms a remote wordpress page into a local {@link WordpressPage} instance.
 *
 * @package silverstripe-wordpressconnector
 */
class WordpressPageTransformer implements ExternalContentTransformer {

	protected $importer;

	public function transform($item, $parent, $strategy) {
		$page   = new WordpressPage();
		$params = $this->importer->getParams();

		$exists = DataObject::get_one('WordpressPage', sprintf(
			'"WordpressID" = %d AND "ParentID" = %d', $item->WordpressID, $parent->ID
		));

		if ($exists) switch ($strategy) {
			case ExternalContentTransformer::DS_OVERWRITE:
				$page = $exists;
				break;
			case ExternalContentTransformer::DS_DUPLICATE:
				break;
			case ExternalContentTransformer::DS_SKIP:
				return;
		}

		$page->Title           = $item->Title;
		$page->MenuTitle       = $item->Title;
		$page->Content         = $item->Description;

		$page->Content = HTTP::urlRewriter($page->Content, ' WordpressPageTransformer::transform_url($URL) ' );

		$page->URLSegment      = $item->Slug;
		$page->ParentID        = $parent->ID;
		$page->ProvideComments = $item->AllowComments;

		$page->WordpressID  = $item->WordpressID;
		$properties = $item->getRemoteProperties();
		$page->OriginalData = serialize($properties);
		$page->OriginalLink = isset($properties['Link']) ? $properties['Link'] : null;
		$page->write();

		if (isset($params['ImportMedia'])) {
			$this->importMedia($item, $page);
		}

		return new TransformResult($page, $item->stageChildren());
	}

	/**
	 * Transform a Wordpress URL by looking up OriginalLink values in the database.
	 * If no such transformation exists, the unmodified URL is returned.
	 * 
	 * @param  string $url Original URL from the site
	 * @return string      New URL
	 */
	static function transform_url($url) {
		if($match = WordpressPage::get()->filter("OriginalLink", $url)->First()) {
			return $match->Link();
		}
		if($match = WordpressPost::get()->filter("OriginalLink", $url)->First()) {
			return $match->Link();
		}
		return $url;
	}

	public function setImporter($importer) {
		$this->importer = $importer;
	}

	protected function importMedia($item, $page) {
		$source  = $item->getSource();
		$params  = $this->importer->getParams();
		$folder  = $params['AssetsPath'];
		$content = $item->Content;

		if ($folder) $folderId = Folder::find_or_make($folder)->ID;

		$url = trim(preg_replace('~^[a-z]+://~', null, $source->BaseUrl), '/');
		$pattern = sprintf(
			'~[a-z]+://%s/wp-content/uploads/[^"]+~', $url
		);

		if (!preg_match_all($pattern, $page->Content, $matches)) return;

		foreach ($matches[0] as $match) {
			if (!$contents = @file_get_contents($match)) continue;

			$name = basename($match);
			$path = Controller::join_links(ASSETS_PATH, $folder, $name);
			$link = Controller::join_links(ASSETS_DIR, $folder, $name);

			file_put_contents($path, $contents);
			$page->Content = str_replace($match, $link, $page->Content);
		}

		Filesystem::sync($folderId);
		$page->write();
	}

}