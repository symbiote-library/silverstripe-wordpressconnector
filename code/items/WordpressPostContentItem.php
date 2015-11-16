<?php
/**
 * A content item that represents an external wordpress post.
 *
 * @package silverstripe-wordpressconnector
 */
class WordpressPostContentItem extends ExternalContentItem {

	public static function factory($source, $data) {
		$item = new self($source, $data['postid']);

		$item->UserID        = $data['userid'];
		$item->PostID        = $data['postid'];
		$item->CreatedAt     = strtotime($data['dateCreated']);
		$item->Title         = html_entity_decode($data['title']);
		$item->Description   = $data['description'];
		$item->Link          = $data['link'];
		$item->Permalink     = $data['permaLink'];
		$item->Excerpt       = $data['mt_excerpt'];
		$item->TextMore      = $data['mt_text_more'];
		$item->AllowComments = $data['mt_allow_comments'];
		$item->AllowPings    = $data['mt_allow_pings'];
		$item->Keywords      = $data['mt_keywords'];
		$item->Slug          = $data['wp_slug'];
		$item->Password      = $data['wp_password'];
		$item->AuthorID      = $data['wp_author_id'];
		$item->AuthorName    = $data['wp_author_display_name'];
		$item->Status        = $data['post_status'];
		$item->PostFormat    = isset($data['wp_post_format']) ? $data['wp_post_format'] : '';

		if (isset($data['sticky'])) {
			$item->Sticky = $data['sticky'];
		}

		$categories = new ArrayList();
		foreach ($data['categories'] as $category) {
			$categories->push(new ArrayData(array(
				'Name' => $category
			)));
		}
		$item->Categories = $categories;

		$custom = new ArrayList();
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

		$fields->addFieldToTab('Root.Details', new ReadonlyField(
			'CategoryList', 'Categories',
			implode(', ', $this->Categories->map('Name', 'Name'))
		));

		$fields->addFieldsToTab('Root.Users', array(
			new ReadonlyField('UserID', 'User ID', $this->UserID),
			new ReadonlyField('AuthorID', 'Author ID', $this->AuthorID),
			new ReadonlyField('AuthorName', 'Author Name', $this->AuthorName)
		));

		$fields->addFieldsToTab('Root.Metadata', array(
			new ReadonlyField('Keywords', null, $this->Keywords),
			new ReadonlyField('Slug', null, $this->Slug),
			new ReadonlyField('Link', null, $this->Link),
			new ReadonlyField('Permalink', null, $this->Permalink)
		));

		$fields->addFieldsToTab('Root.Behaviour', array(
			new ReadonlyField('AllowComments', 'Allow Comments', $this->AllowComments),
			new ReadonlyField('AllowPings', 'Allow Pings', $this->AllowPings),
			new ReadonlyField('Password', null, $this->Password),
			new ReadonlyField('PostFormat', 'Post Format', $this->PostFormat),
			new ReadonlyField('Sticky', null, $this->Sticky)
		));

		$fields->addFieldToTab(
			'Root.CustomFields', ($custom = new GridField('CustomFields', null, $this->CustomFields))
		);
		$config = $custom->getConfig();
		$config->removeComponentsByType('GridFieldFilterHeader');
		$config->getComponentByType('GridFieldDataColumns')->setDisplayFields(array(
			'ID' => 'ID',
			'Key' => 'Key',
			'Value' => 'Value'
		));

		if (!class_exists('BlogEntry')) {
			$fields->addFieldToTab('Root.Import', new LiteralField(
				'RequiresBlogImport',
				'<p>The Wordpress connector requires the blog module to import posts.</p>'
			));
		}

		return $fields;
	}

	public function stageChildren($showAll = false) {
		return new ArrayList();
	}

	public function numChildren() {
		return 0;
	}

	public function canImport() {
		return class_exists('BlogEntry');
	}
	
	public function getType() {
		return 'WpPost';
	}

}