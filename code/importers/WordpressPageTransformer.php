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
		$page->URLSegment      = $item->Slug;
		$page->ParentID        = $parent->ID;
		$page->ProvideComments = $item->AllowComments;

		$page->WordpressID  = $item->WordpressID;
		$page->OriginalData = serialize($item->getRemoteProperties());
		$page->write();

		if (isset($params['ImportMedia'])) {
			$this->importMedia($item, $page);
		}

		return new TransformResult($page, $item->stageChildren());
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