<?php
/**
 * Copyright © 2014, Ambroise Maupate and Julien Blanchet
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is furnished
 * to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS
 * OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL
 * THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS
 * IN THE SOFTWARE.
 *
 * Except as contained in this notice, the name of the ROADIZ shall not
 * be used in advertising or otherwise to promote the sale, use or other dealings
 * in this Software without prior written authorization from Ambroise Maupate and Julien Blanchet.
 *
 * @file VimeoEmbedFinder.php
 * @author Ambroise Maupate
 */
namespace RZ\Roadiz\Utils\MediaFinders;

/**
 * Vimeo tools class.
 *
 * Manage a Vimeo video feed
 */
class VimeoEmbedFinder extends AbstractEmbedFinder
{
    protected static $platform = 'vimeo';

    /**
     * Create a new Vimeo video handler with its embed id.
     *
     * @param string $embedId Vimeo video identifier
     */
    public function __construct($embedId)
    {
        $this->embedId = $embedId;
    }

    /**
     * {@inheritdoc}
     */
    public function getMediaTitle()
    {
        return $this->getFeed()[0]['title'];
    }
    /**
     * {@inheritdoc}
     */
    public function getMediaDescription()
    {
        return $this->getFeed()[0]['description'];
    }
    /**
     * {@inheritdoc}
     */
    public function getMediaCopyright()
    {
        return "";
    }
    /**
     * {@inheritdoc}
     */
    public function getThumbnailURL()
    {
        return $this->getFeed()[0]['thumbnail_large'];
    }


    /**
     * {@inheritdoc}
     */
    public function getSearchFeed($searchTerm, $author, $maxResults = 15)
    {
        $url = "http://gdata.youtube.com/feeds/api/videos/?q=".$searchTerm."&v=2&alt=json&max-results=".$maxResults;
        if (!empty($author)) {
            $url .= '&author='.$author;
        }

        return $this->downloadFeedFromAPI($url);
    }

    /**
     * {@inheritdoc}
     */
    public function getMediaFeed($search = null)
    {
        // http://gdata.youtube.com/feeds/api/videos/<Code de la vidéo>?v=2&alt=json ---> JSON
        //
        $url = "http://vimeo.com/api/v2/video/".$this->embedId.".json";

        return $this->downloadFeedFromAPI($url);
    }

    /**
     * {@inheritdoc}
     *
     * Additional attributes for Vimeo
     *
     * * displayTitle
     * * byline
     * * portrait
     * * color
     * * loop
     */
    public function getSource(&$args = [])
    {
        $uri = 'https://player.vimeo.com/video/'.$this->embedId.'?api=1';

        if (array_key_exists($args['displayTitle'])) {
            $uri .= '&title='.(int) $args['displayTitle'];
        }
        if (array_key_exists($args['byline'])) {
            $uri .= '&byline='.(int) $args['byline'];
        }
        if (array_key_exists($args['portrait'])) {
            $uri .= '&portrait='.(int) $args['portrait'];
        }
        if (isset($args['color'])) {
            $uri .= '&color='.$args['color'];
        }
        if (isset($args['id'])) {
            $uri .= '&player_id='.$args['id'];
        }
        if (array_key_exists($args['loop'])) {
            $uri .= '&loop='.(int) $args['loop'];
        }

        return $uri;
    }
}
