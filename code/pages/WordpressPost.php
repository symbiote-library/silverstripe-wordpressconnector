<?php
/**
 * A blog post that was imported from a wordpress site.
 *
 * @package silverstripe-wordpressconnector
 */
class WordpressPost extends BlogEntry {

	private static $db = array(
		'WordpressID'  => 'Int',
		'OriginalData' => 'Text',
		'OriginalLink' => 'Varchar(255)',
	);

	public function getCMSFields() {
		$fields = parent::getCMSFields();

		$fields->addFieldsToTab('Root.Wordpress', array(
			new ReadonlyField('WordpressID', 'Wordpress Post ID'),
			new ReadonlyField('OriginalLink', 'Original Wordpress URL'),
			new ReadonlyField('OriginalData', 'Original Wordpress Data')
		));

		return $fields;
	}

}

class WordpressPost_Controller extends BlogEntry_Controller {
}