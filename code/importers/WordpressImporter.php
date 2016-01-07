<?php
/**
 * @package silverstripe-wordpressimporter
 */
class WordpressImporter extends ExternalContentImporter
{

    public function __construct()
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
