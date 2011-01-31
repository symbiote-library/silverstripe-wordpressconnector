<?php
/**
 * Transforms a remote wordpress post into a local {@link WordpressPost}.
 *
 * @package silverstripe-wordpressconnector
 */
class WordpressPostTransformer implements ExternalContentTransformer {

	public function transform($item, $parent, $strategy) {
		$post = new WordpressPost();

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
	}

}