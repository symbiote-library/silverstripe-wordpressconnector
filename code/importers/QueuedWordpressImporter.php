<?php
/**
 * A queued version of the wordpress importer.
 *
 * @package silverstripe-wordpressconnector
 */
class QueuedWordpressImporter extends QueuedExternalContentImporter
{

    public function init()
    {
        $page = new WordpressPageTransformer();
        $page->setImporter($this);

        $post = new WordpressPostTransformer();
        $post->setImporter($this);

        $this->contentTransforms['page'] = $page;
        $this->contentTransforms['post'] = $post;
    }

    public function getExternalType($item)
    {
        switch ($item->class) {
            case 'WordpressPageContentItem': return 'page';
            case 'WordpressPostContentItem': return 'post';
        }
    }
}
