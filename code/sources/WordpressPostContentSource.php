<?php
/**
 * An external content source that pulls in wordpress posts.
 *
 * @package silverstripe-wordpressconnector
 */
class WordpressPostContentSource extends WordpressContentSource
{

    private static $icon = 'wordpressconnector/images/wordpresspostsource';

    public function getRoot()
    {
        return $this;
    }

    public function getObject($id)
    {
        $client = $this->getClient($id);
        $id     = $this->decodeId($id);

        $post = $client->call('metaWeblog.getPost', array(
            $id, $this->Username, $this->Password
        ));

        if ($post) {
            return WordpressPostContentItem::factory($this, $post);
        }
    }

    public function stageChildren($showAll = false)
    {
        $result = new ArrayList();

        if (!$this->isValid()) {
            return $result;
        }

        // The XML-RPC API has no way to pull all posts by default, so just
        // pass a huge number in as the limit.
        try {
            $client = $this->getClient();
            $posts  = $client->call('metaWeblog.getRecentPosts', array(
                $this->BlogId, $this->Username, $this->Password, 999999
            ));
        } catch (Zend_Exception $exception) {
            SS_Log::log($exception, SS_Log::ERR);
            return new Arraylist();
        }

        foreach ($posts as $post) {
            $result->push(WordpressPostContentItem::factory($this, $post));
        }

        return $result;
    }

    public function getCMSFields()
    {
        $fields = parent::getCMSFields();

        Requirements::javascript(THIRDPARTY_DIR . '/jquery/jquery.js');
        Requirements::javascript('wordpressconnector/javascript/WordpressPostContentSource.js');

        if (!class_exists('BlogEntry')) {
            $fields->addFieldToTab('Root.Import', new LiteralField(
                'RequiresBlogImport',
                '<p>The Wordpress connector requires the blog module to import posts.</p>'
            ));
        } else {
            $blogs = BlogHolder::get();
            $map = $blogs ? $blogs->map() : array();

            $fields->addFieldsToTab('Root.Import', array(
                new DropdownField('MigrationTarget', 'Blog to import into', $map),
                new CheckboxField('ImportComments',
                    'Import comments attached to the posts?', true),
                new CheckboxField('ImportMedia',
                    'Import and rewrite references to wordpress media?', true),
                new TextField('AssetsPath',
                    'Upload wordpress files to', 'Uploads/Wordpress')
            ));
        }

        return $fields;
    }

    public function canCreate($member = null)
    {
        return true;
    }

    public function canImport()
    {
        return class_exists('BlogEntry');
    }
}
