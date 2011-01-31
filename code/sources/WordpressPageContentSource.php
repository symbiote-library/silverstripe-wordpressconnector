<?php
/**
 * A content source that displays all the pages attached to a wordpress blog.
 *
 * @package silverstripe-wordpressconnector
 */
class WordpressPageContentSource extends WordpressContentSource {

	public static $icon = 'wordpressconnector/images/wordpresspagesource';

	public function getRoot() {
		return $this;
	}

	public function getObject($id) {
		$client = $this->getClient($id);
		$id     = $this->decodeId($id);

		$page = $client->call('wp.getPage', array(
			$this->BlogId, $id, $this->Username, $this->Password
		));

		if ($page) {
			return WordpressPageContentItem::factory($this, $page);
		}
	}

	public function stageChildren() {
		return $this->getPagesByParentId(0);
	}

	public function allowedImportTargets() {
		return array('sitetree' => true);
	}

	/**
	 * Gets all the page content items that sit under a parent ID.
	 *
	 * @param  int $parent
	 * @return DataObjectSet
	 */
	public function getPagesByParentId($parent) {
		$result = new DataObjectSet();

		if (!$this->isValid()) {
			return $result;
		}

		try {
			$client = $this->getClient();
			$pages  = $client->call('wp.getPages', array(
				$this->BlogId, $this->Username, $this->Password
			));
		} catch (Zend_Exception $exception) {
			SS_Log::log($exception, SS_Log::ERR);
			return new DataObjectSet();
		}

		foreach ($pages as $page) {
			if ($page['wp_page_parent_id'] == $parent) {
				$result->push(WordpressPageContentItem::factory($this, $page));
			}
		}

		return $result;
	}

	public function canCreate() {
		return true;
	}

}