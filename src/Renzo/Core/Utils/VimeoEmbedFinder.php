<?php
/*
 * Copyright REZO ZERO 2014
 *
 *
 *
 * @file VimeoFinder.php
 * @copyright REZO ZERO 2014
 * @author Ambroise Maupate
 */
namespace RZ\Core\Utils;

/**
 * Vimeo tools class.
 *
 * Manage a youtube video feed
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
        return $this->getFeed()['title'];
    }
    /**
     * {@inheritdoc}
     */
    public function getMediaDescription()
    {
        return $this->getFeed()['description'];
    }
    /**
     * {@inheritdoc}
     */
    public function getThumbnailURL()
    {
        return $this->getFeed()['thumbnail_large'];
    }


    /**
     * {@inheritdoc}
     */
    public function getSearchFeed( $searchTerm, $author, $maxResults=15 )
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
    public function getVideoFeed($search = null)
    {
        // http://gdata.youtube.com/feeds/api/videos/<Code de la vidéo>?v=2&alt=json ---> JSON
        //
        $url = "http://vimeo.com/api/v2/video/".$this->embedId.".json";

        return $this->downloadFeedFromAPI($url);
    }

    /**
     * {@inheritdoc}
     */
    public function getSource($args = array())
    {
        $uri = '://player.vimeo.com/video/'.$this->embedId.'?api=1';

        if(isset($args['displayTitle'])) $uri .= '&title='.$args['displayTitle'];
        if(isset($args['byline'])) $uri .= '&byline='.$args['byline'];
        if(isset($args['portrait'])) $uri .= '&portrait='.$args['portrait'];
        if(isset($args['color'])) $uri .= '&color='.$args['color'];

        return $uri;
    }
}
