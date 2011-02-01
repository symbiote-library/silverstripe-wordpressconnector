<?php
/**
 * @package silverstripe-wordpressconnector
 */

require_once 'Zend/XmlRpc/Value/Struct.php';

/**
 * Transforms a remote wordpress post into a local {@link WordpressPost}.
 *
 * @package silverstripe-wordpressconnector
 */
class WordpressPostTransformer implements ExternalContentTransformer {

	protected $importer;

	public function transform($item, $parent, $strategy) {
		$post   = new WordpressPost();
		$params = $this->importer->getParams();

		$exists = DataObject::get_one('WordpressPost', sprintf(
			'"WordpressID" = %d AND "ParentID" = %d', $item->PostID, $parent->ID
		));

		if ($exists) switch ($strategy) {
			case ExternalContentTransformer::DS_OVERWRITE:
				$post = $exists;
				break;
			case ExternalContentTransformer::DS_DUPLICATE:
				break;
			case ExternalContentTransformer::DS_SKIP:
				return;
		}

		$post->Title           = $item->Title;
		$post->MenuTitle       = $item->Title;
		$post->Content         = $item->Description;
		$post->Date            = date('Y-m-d H:i:s', $item->CreatedAt);
		$post->Author          = $item->AuthorName;
		$post->Tags            = implode(', ', $item->Categories->map('Name', 'Name'));
		$post->URLSegment      = $item->Slug;
		$post->ParentID        = $parent->ID;
		$post->ProvideComments = $item->AllowComments;
		$post->MetaKeywords    = $item->Keywords;

		$post->WordpressID  = $item->PostID;
		$post->OriginalData = serialize($item->getRemoteProperties());
		$post->write();

		// Import comments across from the wordpress site.
		if (isset($params['ImportComments'])) {
			$this->importComments($item, $post);
		}

		// Scan the post for media files, and import them as well.
		if (isset($params['ImportMedia'])) {
			$this->importMedia($item, $post);
		}
	}

	public function setImporter($importer) {
		$this->importer = $importer;
	}

	protected function importComments($item, $post) {
		$source = $item->getSource();
		$client = $source->getClient();

		$struct = new Zend_XmlRpc_Value_Struct(array(
			'post_id' => $item->PostID,
			'number'  => 999999
		));

		$comments = $client->call('wp.getComments', array(
			$source->BlogId, $source->Username, $source->Password, $struct
		));

		if ($comments) foreach ($comments as $data) {
			$comment = new PageComment();
			$comment->Name         = $data['author'];
			$comment->Comment      = $data['content'];
			$comment->CommenterURL = $data['author_url'];
			$comment->ParentID     = $post->ID;
			$comment->write();

			$comment->Created = date('Y-m-d H:i:s', strtotime($data['date_created_gmt']));
			$comment->write();
		}
	}

	protected function importMedia($item, $post) {
		$source  = $item->getSource();
		$params  = $this->importer->getParams();
		$folder  = $params['AssetsPath'];
		$content = $item->Content;

		if ($folder) Folder::findOrMake($folder);

		$url = trim(preg_replace('~^[a-z]+://~', null, $source->BaseUrl), '/');
		$pattern = sprintf(
			'~[a-z]+://%s/wp-content/uploads/[^"]+~', $url
		);

		if (!preg_match_all($pattern, $post->Content, $matches)) return;

		foreach ($matches[0] as $match) {
			if (!$contents = @file_get_contents($match)) continue;

			$name = basename($match);
			$path = Controller::join_links(ASSETS_PATH, $folder, $name);
			$link = Controller::join_links(ASSETS_DIR, $folder, $name);

			file_put_contents($path, $contents);
			$post->Content = str_replace($match, $link, $post->Content);
		}

		$post->write();
	}

}