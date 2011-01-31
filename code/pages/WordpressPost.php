<?php
/**
 * A blog post that was imported from a wordpress site.
 *
 * @package silverstripe-wordpressconnector
 */
class WordpressPost extends BlogEntry {

	public static $db = array(
		'WordpressID'  => 'Int',
		'OriginalData' => 'Text'
	);

	public function getCMSFields() {
		$fields = parent::getCMSFields();

		$fields->addFieldsToTab('Root.Wordpress', array(
			new ReadonlyField('WordpressID', 'Wordpress Post ID'),
			new ReadonlyField('OriginalData', 'Original Wordpress Data')
		));

		return $fields;
	}

}

class WordpressPost_Controller extends BlogEntry_Controller {
}