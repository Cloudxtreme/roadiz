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
 * @file YoutubeEmbedFinder.php
 * @author Ambroise Maupate
 */
namespace RZ\Roadiz\Utils\MediaFinders;

use RZ\Roadiz\Core\Exceptions\APINeedsAuthentificationException;

/**
 * Youtube tools class.
 *
 * Manage a youtube video feed
 */
class YoutubeEmbedFinder extends AbstractEmbedFinder
{
    protected static $platform = 'youtube';

    /**
     * Create a new Youtube video handler with its embed id.
     *
     * @param string $embedId Youtube video identifier
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
        return $this->getFeed()['items'][0]['snippet']['title'];
    }
    /**
     * {@inheritdoc}
     */
    public function getMediaDescription()
    {
        return $this->getFeed()['items'][0]['snippet']['description'];
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
        return $this->getFeed()['items'][0]['snippet']['thumbnails']['high']['url'];
    }


    /**
     * {@inheritdoc}
     */
    public function getSearchFeed($searchTerm, $author, $maxResults = 15)
    {
        if ($this->getKey() != "") {
            $url = "https://www.googleapis.com/youtube/v3/search?q=".$searchTerm."&part=snippet&key=".$this->getKey()."&maxResults=".$maxResults;
            if (!empty($author)) {
                $url .= '&author='.$author;
            }
            return $this->downloadFeedFromAPI($url);
        } else {
            throw new APINeedsAuthentificationException("YoutubeEmbedFinder needs a Google server key, create a “google_server_id” setting.", 1);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getMediaFeed($search = null)
    {
        if ($this->getKey() != "") {
            $url = "https://www.googleapis.com/youtube/v3/videos?id=".$this->embedId."&part=snippet&key=".$this->getKey()."&maxResults=1";
            return $this->downloadFeedFromAPI($url);
        } else {
            throw new APINeedsAuthentificationException("YoutubeEmbedFinder needs a Google server key, create a “google_server_id” setting.", 1);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getSource(&$args = [])
    {
        return '//www.youtube.com/embed/'.$this->embedId.'?rel=0&html5=1&wmode=transparent';
    }
}
