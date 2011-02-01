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
		$source = $item->getSource();
		$client = $source->getClient();

		if (!isset($params['ImportComments'])) return;

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

	public function setImporter($importer) {
		$this->importer = $importer;
	}

}