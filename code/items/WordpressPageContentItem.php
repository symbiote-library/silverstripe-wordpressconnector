<?php
/**
 * A content item that represents a wordpress page.
 *
 * @package silverstripe-wordpressconnector
 */
class WordpressPageContentItem extends ExternalContentItem {

	/**
	 * @param  array $data
	 * @return WordpressPageContentItem
	 */
	public static function factory($source, $data) {
		$item = new self($source, $data['page_id']);

		$item->WordpressID   = $data['page_id'];
		$item->CreatedAt     = strtotime($data['dateCreated']);
		$item->UserID        = $data['userid'];
		$item->Status        = $data['page_status'];
		$item->Description   = $data['description'];
		$item->Title         = html_entity_decode($data['title']);
		$item->Link          = $data['link'];
		$item->Permalink     = $data['permaLink'];
		$item->Excerpt       = $data['excerpt'];
		$item->TextMore      = $data['text_more'];
		$item->AllowComments = $data['mt_allow_comments'];
		$item->AllowPings    = $data['mt_allow_pings'];
		$item->Slug          = $data['wp_slug'];
		$item->Password      = $data['wp_password'];
		$item->Author        = $data['wp_author'];
		$item->ParentID      = $data['wp_page_parent_id'];
		$item->ParentTitle   = $data['wp_page_parent_title'];
		$item->Order         = $data['wp_page_order'];
		$item->AuthorID      = $data['wp_author_id'];
		$item->AuthorName    = $data['wp_author_display_name'];
		$item->Template      = $data['wp_page_template'];

		$categories = new DataObjectSet();
		foreach ($data['categories'] as $category) {
			$categories->push(new ArrayData(array(
				'Name' => $category
			)));
		}
		$item->Categories = $categories;

		$custom = new DataObjectSet();
		foreach ($data['custom_fields'] as $field) {
			$custom->push(new ArrayData(array(
				'ID'    => $field['id'],
				'Key'   => $field['key'],
				'Value' => $field['value']
			)));
		}
		$item->CustomFields = $custom;

		return $item;
	}

	public function getCMSFields() {
		$fields = parent::getCMSFields();

		$fields->fieldByName('Root.Details')->getChildren()->changeFieldOrder(array(
			'Title', 'Status', 'CreatedAt', 'ShowContentInMenu', 'Description',
			'Excerpt', 'TextMore', 'ExternalContentItem_Alert'
		));

		$fields->addFieldToTab('Root.Details', new ReadonlyField(
			'CategoryList', 'Categories',
			implode(', ', $this->Categories->map('Name', 'Name'))
		));

		$fields->addFieldsToTab('Root.Users', array(
			new ReadonlyField('UserID', 'User ID', $this->UserID),
			new ReadonlyField('Author', null, $this->Author),
			new ReadonlyField('AuthorID', 'Author ID', $this->AuthorID),
			new ReadonlyField('AuthorName', 'Author Name', $this->AuthorName)
		));

		$fields->addFieldsToTab('Root.Metadata', array(
			new ReadonlyField('Slug', null, $this->Slug),
			new ReadonlyField('Link', null, $this->Link),
			new ReadonlyField('Permalink', null, $this->Permalink)
		));

		$fields->addFieldsToTab('Root.Behaviour', array(
			new ReadonlyField('ParentID', 'Parent ID', $this->ParentID),
			new ReadonlyField('ParentTitle', 'Parent Title', $this->ParentTitle),
			new ReadonlyField('AllowComments', 'Allow Comments', $this->AllowComments),
			new ReadonlyField('AllowPings', 'Allow Pings', $this->AllowPings),
			new ReadonlyField('Password', null, $this->Password),
			new ReadonlyField('Template', null, $this->Template),
			new ReadonlyField('Order', null, $this->Order)
		));

		$custom = new TableListField('CustomFields', null, array(
			'ID'    => 'ID',
			'Key'   => 'Key',
			'Value' => 'Value'
		));
		$custom->setCustomSourceItems($this->CustomFields);

		$fields->addFieldToTab(
			'Root.CustomFields', $custom->performReadonlyTransformation()
		);

		return $fields;
	}

	public function stageChildren() {
		return $this->source->getPagesByParentId($this->externalId);
	}

}