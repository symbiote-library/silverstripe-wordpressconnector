<?php
/**
 * Transforms a remote wordpress page into a local {@link WordpressPage} instance.
 *
 * @package silverstripe-wordpressconnector
 */
class WordpressPageTransformer implements ExternalContentTransformer {

	public function transform($item, $parent, $strategy) {
		$page = new WordpressPage();

		$exists = DataObject::get_one('WordpressPage', sprintf(
			'"WordpressID" = %d AND "ParentID" = %d', $item->ID, $parent->ID
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

		$page->WordpressID  = $item->ID;
		$page->OriginalData = serialize($item->getRemoteProperties());

		$page->write();
		return new TransformResult($page, $item->stageChildren());
	}

}