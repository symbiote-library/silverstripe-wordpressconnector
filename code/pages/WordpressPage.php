<?php
/**
 * A page that was imported from a remote wordpress installation.
 *
 * @package silverstripe-wordpressconnector
 */
class WordpressPage extends Page {

	public static $db = array(
		'WordpressID'  => 'Int',
		'OriginalData' => 'Text'
	);

	public function getCMSFields() {
		$fields = parent::getCMSFields();

		$fields->addFieldsToTab('Root.Wordpress', array(
			new ReadonlyField('WordpressID', 'Wordpress Page ID'),
			new ReadonlyField('OriginalData', 'Original Wordpress Data')
		));

		return $fields;
	}

}

class WordpressPage_Controller extends Page_Controller {
}