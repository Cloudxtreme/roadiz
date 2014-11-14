<?php
/**
 * Copyright © 2014, REZO ZERO
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
 * Except as contained in this notice, the name of the REZO ZERO shall not
 * be used in advertising or otherwise to promote the sale, use or other dealings
 * in this Software without prior written authorization from the REZO ZERO SARL.
 *
 * @file AbstractEmbedFinder.php
 * @copyright REZO ZERO 2014
 * @author Ambroise Maupate
 */
namespace RZ\Renzo\Core\Utils;

use RZ\Renzo\Core\Entities\Document;
use RZ\Renzo\Core\Entities\DocumentTranslation;
use RZ\Renzo\Core\Exceptions\EntityAlreadyExistsException;
use RZ\Renzo\Core\Exceptions\APINeedsAuthentificationException;
use Symfony\Component\HttpFoundation\Response;
use Pimple\Container;

/**
 * abstract class to handle external media via their Json API.
 */
abstract class AbstractEmbedFinder
{
    protected $feed = null;
    protected $embedId;

    protected static $platform = 'abstract';

    /**
     * Tell if embed media exists after its API feed.
     *
     * @return boolean
     */
    public function exists()
    {
        if ($this->getFeed() !== false) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Crawl and parse an API json feed for current embedID.
     *
     * @return array | false
     */
    public function getFeed()
    {
        if (null === $this->feed) {
            $this->feed = $this->getMediaFeed();
            if (false !== $this->feed) {
                $this->feed = json_decode($this->feed, true);
            }
        }
        // var_dump($this->feed);
        // exit();
        return $this->feed;
    }

    /**
     * Get embed media source URL.
     *
     * @param array $args
     *
     * @return string
     */
    abstract public function getSource($args = array());

    /**
     * Crawl an embed API to get a Json feed.
     *
     * @param string | null $search
     *
     * @return string
     */
    abstract public function getMediaFeed($search = null);

    /**
     * Crawl an embed API to get a Json feed against a search query.
     *
     * @param string  $searchTerm
     * @param string  $author
     * @param integer $maxResults
     *
     * @return string
     */
    abstract public function getSearchFeed($searchTerm, $author, $maxResults = 15);

    /**
     * Compose an HTML iframe for viewing embed media.
     *
     * * width
     * * height
     * * title
     * * id
     *
     * @param  array | null $args
     *
     * @return string
     */
    public function getIFrame(&$args = null)
    {
        $attributes = array();

        $attributes['src'] = $this->getSource($this->embedId);

        if (isset($args['width'])) {
            $attributes['width'] = $args['width'];

            /*
             * Default height is defined to 16:10
             */
            if (!isset($args['height'])) {
                $attributes['height'] = (int)(($args['width']*10)/16);
            }
        }
        if (isset($args['height'])) {
            $attributes['height'] = $args['height'];
        }
        if (isset($args['title'])) {
            $attributes['title'] = $args['title'];
        }
        if (isset($args['id'])) {
            $attributes['id'] = $args['id'];
        }

        if (isset($args['autoplay']) && $args['autoplay'] == true) {
            $attributes['src'] .= '&autoplay=1';
        }
        if (isset($args['controls']) && $args['controls'] == false) {
            $attributes['src'] .= '&controls=0';
        }

        $attributes['frameborder'] = "0";
        $attributes['webkitAllowFullScreen'] = "1";
        $attributes['mozallowfullscreen'] = "1";
        $attributes['allowFullScreen'] = "1";

        $htmlTag = '<iframe';
        foreach ($attributes as $key => $value) {
            if ($value == '') {
                $htmlTag .= ' '.$key;
            } else {
                $htmlTag .= ' '.$key.'="'.addslashes($value).'"';
            }
        }
        $htmlTag .= ' ></iframe>';

        return $htmlTag;
    }
    /**
     * Create a Document from an embed media
     *
     * @param Pimple\Container $container description
     *
     * @return Document
     */
    public function createDocumentFromFeed(Container $container)
    {
        $url = $this->downloadThumbnail();

        if (!$this->exists()) {
            throw new \Exception('no.embed.document.found');
        }

        if (false !== $url) {
            $existingDocument = $container['em']->getRepository('RZ\Renzo\Core\Entities\Document')
                                                ->findOneBy(array('filename'=>$url));
        } else {
            $existingDocument = $container['em']->getRepository('RZ\Renzo\Core\Entities\Document')
                                                ->findOneBy(array(
                                                    'embedId'=>$this->embedId,
                                                    'embedPlatform'=>static::$platform,
                                                ));
        }

        if (null !== $existingDocument) {
            throw new EntityAlreadyExistsException('embed.document.already_exists');
        }


        $document = new Document();
        $document->setEmbedId($this->embedId);
        $document->setEmbedPlatform(static::$platform);

        if (false !== $url) {
            /*
             * Move file from documents file root to its folder.
             */
            $document->setFilename($url);
            $document->setMimeType('image/jpeg');
            if (!file_exists(Document::getFilesFolder().'/'.$document->getFolder())) {
                mkdir(Document::getFilesFolder().'/'.$document->getFolder());
            }
            rename(Document::getFilesFolder().'/'.$url, $document->getAbsolutePath());
        }
        $container['em']->persist($document);

        /*
         * Create document metas
         * for each translation
         */
        $translations = $container['em']
                            ->getRepository('RZ\Renzo\Core\Entities\Translation')
                            ->findAll();

        foreach ($translations as $translation) {
            $documentTr = new DocumentTranslation();
            $documentTr->setDocument($document);
            $documentTr->setTranslation($translation);
            $documentTr->setName($this->getMediaTitle());
            $documentTr->setDescription($this->getMediaDescription());
            $documentTr->setCopyright($this->getMediaCopyright());

            $container['em']->persist($documentTr);
        }


        $container['em']->flush();

        return $document;
    }

    /**
     * Get media title from feed.
     *
     * @return string
     */
    abstract public function getMediaTitle();

    /**
     * Get media description from feed.
     *
     * @return string
     */
    abstract public function getMediaDescription();

    /**
     * Get media copyright from feed.
     *
     * @return string
     */
    abstract public function getMediaCopyright();

    /**
     * Get media thumbnail external URL from its feed.
     *
     * @return string
     */
    abstract public function getThumbnailURL();

    /**
     * Send a CURL request and get its string output.
     *
     * @param string $url
     *
     * @return string|false
     */
    public function downloadFeedFromAPI($url)
    {
        try {
            $client = new \GuzzleHttp\Client();
            $response = $client->get($url);

            if (Response::HTTP_OK == $response->getStatusCode()) {
                return $response->getBody();
            } else {
                return false;
            }
        } catch (\GuzzleHttp\Exception\RequestException $e) {
            return false;
        }
    }

    /**
     * Download a picture from the embed media platform
     * to get a thumbnail.
     *
     * @return string|false File URL in document files folder.
     */
    public function downloadThumbnail()
    {
        $url = $this->getThumbnailURL();

        if (false !== $url &&
            '' !== $url) {

            $pathinfo = basename($url);

            if ($pathinfo != "") {
                $thumbnailName = $this->embedId.'_'.$pathinfo;

                try {
                    $original = \GuzzleHttp\Stream\Stream::factory(fopen($url, 'r'));
                    $local = \GuzzleHttp\Stream\Stream::factory(fopen(Document::getFilesFolder().'/'.$thumbnailName, 'w'));
                    $local->write($original->getContents());

                    if (file_exists(Document::getFilesFolder().'/'.$thumbnailName) &&
                        filesize(Document::getFilesFolder().'/'.$thumbnailName) > 0) {

                        return $thumbnailName;
                    } else {
                        return false;
                    }

                } catch (\GuzzleHttp\Exception\RequestException $e) {
                    return false;
                }
            }
        }

        return false;
    }
}
