<?php
/**
 * @package silverstripe-wordpressconnector
 */

use Zend\XmlRpc\Value\Struct;

/**
 * Transforms a remote wordpress post into a local {@link WordpressPost}.
 *
 * @package silverstripe-wordpressconnector
 */
class WordpressPostTransformer extends WordpressPageTransformer
{

    /**
     * Accepts matches array from preg_replace_callback in wpautop() or a string.
     *
     * Ensures that the contents of a <<pre>>...<</pre>> HTML block are not
     * converted into paragraphs or line-breaks.
     *
     * Source originally from Wordpress.
     * 
     * @param array|string $matches The array or string
     * @return string The pre block without paragraph/line-break conversion.
     */
    public function clean_pre($matches)
    {
        if (is_array($matches)) {
            $text = $matches[1] . $matches[2] . "</pre>";
        } else {
            $text = $matches;
        }

        $text = str_replace('<br />', '', $text);
        $text = str_replace('<p>', "\n", $text);
        $text = str_replace('</p>', '', $text);

        return $text;
    }



    /**
     * Newline preservation help function for wpautop.
     * Source originally from Wordpress.
     *
     * @param array $matches preg_replace_callback matches array
     * @return string
     */
    public function _autop_newline_preservation_helper($matches)
    {
        return str_replace("\n", "<WPPreserveNewline />", $matches[0]);
    }

    /**
     * Replaces double line-breaks with paragraph elements.
     *
     * A group of regex replaces used to identify text formatted with newlines and
     * replace double line-breaks with HTML paragraph tags. The remaining
     * line-breaks after conversion become <<br />> tags, unless $br is set to '0'
     * or 'false'.
     *
     * Source originally from Wordpress.
     *
     * @param string $pee The text which has to be formatted.
     * @param int|bool $br Optional. If set, this will convert all remaining line-breaks after paragraphing. Default true.
     * @return string Text which has been converted into correct paragraph tags.
     */
    public function wpautop($pee, $br = 1)
    {
        if (trim($pee) === '') {
            return '';
        }
        $pee = $pee . "\n"; // just to make things a little easier, pad the end
        $pee = preg_replace('|<br />\s*<br />|', "\n\n", $pee);
        // Space things out a little
        $allblocks = '(?:table|thead|tfoot|caption|col|colgroup|tbody|tr|td|th|div|dl|dd|dt|ul|ol|li|pre|select|option|form|map|area|blockquote|address|math|style|input|p|h[1-6]|hr|fieldset|legend|section|article|aside|hgroup|header|footer|nav|figure|figcaption|details|menu|summary)';
        $pee = preg_replace('!(<' . $allblocks . '[^>]*>)!', "\n$1", $pee);
        $pee = preg_replace('!(</' . $allblocks . '>)!', "$1\n\n", $pee);
        $pee = str_replace(array("\r\n", "\r"), "\n", $pee); // cross-platform newlines
        if (strpos($pee, '<object') !== false) {
            $pee = preg_replace('|\s*<param([^>]*)>\s*|', "<param$1>", $pee); // no pee inside object/embed
            $pee = preg_replace('|\s*</embed>\s*|', '</embed>', $pee);
        }
        $pee = preg_replace("/\n\n+/", "\n\n", $pee); // take care of duplicates
        // make paragraphs, including one at the end
        $pees = preg_split('/\n\s*\n/', $pee, -1, PREG_SPLIT_NO_EMPTY);
        $pee = '';
        foreach ($pees as $tinkle) {
            $pee .= '<p>' . trim($tinkle, "\n") . "</p>\n";
        }
        $pee = preg_replace('|<p>\s*</p>|', '', $pee); // under certain strange conditions it could create a P of entirely whitespace
        $pee = preg_replace('!<p>([^<]+)</(div|address|form)>!', "<p>$1</p></$2>", $pee);
        $pee = preg_replace('!<p>\s*(</?' . $allblocks . '[^>]*>)\s*</p>!', "$1", $pee); // don't pee all over a tag
        $pee = preg_replace("|<p>(<li.+?)</p>|", "$1", $pee); // problem with nested lists
        $pee = preg_replace('|<p><blockquote([^>]*)>|i', "<blockquote$1><p>", $pee);
        $pee = str_replace('</blockquote></p>', '</p></blockquote>', $pee);
        $pee = preg_replace('!<p>\s*(</?' . $allblocks . '[^>]*>)!', "$1", $pee);
        $pee = preg_replace('!(</?' . $allblocks . '[^>]*>)\s*</p>!', "$1", $pee);
        if ($br) {
            $pee = preg_replace_callback('/<(script|style).*?<\/\\1>/s', array(&$this, '_autop_newline_preservation_helper'), $pee);
            $pee = preg_replace('|(?<!<br />)\s*\n|', "<br />\n", $pee); // optionally make line breaks
            $pee = str_replace('<WPPreserveNewline />', "\n", $pee);
        }
        $pee = preg_replace('!(</?' . $allblocks . '[^>]*>)\s*<br />!', "$1", $pee);
        $pee = preg_replace('!<br />(\s*</?(?:p|li|div|dl|dd|dt|th|pre|td|ul|ol)[^>]*>)!', '$1', $pee);
        if (strpos($pee, '<pre') !== false) {
            //$pee = preg_replace_callback('!(<pre[^>]*>)(.*?)</pre>!is', 'clean_pre', $pee );
        $pee = preg_replace_callback('!(<pre[^>]*>)(.*?)</pre>!is', array(&$this, 'clean_pre'), $pee);
        }
      
        $pee = preg_replace("|\n</p>$|", '</p>', $pee);

        return $pee;
    }

    public function transform($item, $parent, $strategy)
    {
        $post   = new WordpressPost();
        $params = $this->importer->getParams();

        $exists = DataObject::get_one('WordpressPost', sprintf(
            '"WordpressID" = %d AND "ParentID" = %d', $item->PostID, $parent->ID
        ));

        if ($exists) {
            switch ($strategy) {
            case ExternalContentTransformer::DS_OVERWRITE:
                $post = $exists;
                break;
            case ExternalContentTransformer::DS_DUPLICATE:
                break;
            case ExternalContentTransformer::DS_SKIP:
                return;
        }
        }

        $post->Title           = $item->Title;
        $post->MenuTitle       = $item->Title;

        $post->Content         = $this->wpautop($item->Description . $item->TextMore);
    
        $post->Date            = date('Y-m-d H:i:s', $item->CreatedAt);
        $post->Author          = $item->AuthorName;
        $post->Tags            = implode(', ', $item->Categories->map('Name', 'Name'));
        $post->URLSegment      = $item->Slug;
        $post->ParentID        = $parent->ID;
        $post->ProvideComments = $item->AllowComments;
        $post->MetaKeywords    = $item->Keywords;

        $post->WordpressID  = $item->PostID;
        $properties = $item->getRemoteProperties();
        $post->OriginalData = serialize($properties);
        $post->OriginalLink = isset($properties['Link']) ? $properties['Link'] : null;
        $post->write();

        // Import comments across from the wordpress site.
        if (isset($params['ImportComments'])) {
            $this->importComments($item, $post);
        }

        // Scan the post for media files, and import them as well.
        if (isset($params['ImportMedia'])) {
            $this->importMedia($item, $post);
        }
    }

    protected function importComments($item, $post)
    {
        $source = $item->getSource();
        $client = $source->getClient();

        $struct = new Struct(array(
            'post_id' => $item->PostID,
            'number'  => 999999
        ));

        $comments = $client->call('wp.getComments', array(
            $source->BlogId, $source->Username, $source->Password, $struct
        ));

        if ($comments) {
            foreach ($comments as $data) {
                $comment = new Comment();
                $comment->BaseClass = "SiteTree";
                $comment->Name         = $data['author'];
                $comment->Comment      = $data['content'];
                $comment->CommenterURL = $data['author_url'];
                $comment->ParentID     = $post->ID;
                $comment->write();

                $comment->Created = date('Y-m-d H:i:s', strtotime($data['date_created_gmt']));
                $comment->write();
            }
        }
    }
}
