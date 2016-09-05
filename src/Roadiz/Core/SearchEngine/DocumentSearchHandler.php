<?php
/**
 * Copyright (c) 2016. Ambroise Maupate and Julien Blanchet
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is furnished
 * to do so, subject to the following conditions:
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
 * @file DocumentSearchHandler.php
 * @author Ambroise Maupate <ambroise@rezo-zero.com>
 */
namespace RZ\Roadiz\Core\SearchEngine;

use RZ\Roadiz\Core\Entities\Folder;
use RZ\Roadiz\Core\Entities\Translation;
use Solarium\Core\Query\Helper;

/**
 * Class DocumentSearchHandler
 * @package RZ\Roadiz\Core\SearchEngine
 */
class DocumentSearchHandler extends AbstractSearchHandler
{
    /**
     * @param string  $q
     * @param array   $args
     * @param integer $rows
     * @param boolean $searchTags
     * @param integer $proximity Proximity matching: Lucene supports finding words are a within a specific distance away.
     * @param integer $page
     *
     * @return array
     */
    protected function nativeSearch($q, $args = [], $rows = 20, $searchTags = false, $proximity = 10000000, $page = 1)
    {
        if (!empty($q)) {
            $query = $this->client->createSelect();

            $q = trim($q);
            $qHelper = new Helper();
            $q = $qHelper->escapeTerm($q);

            $singleWord = strpos($q, ' ') === false ? true : false;

            /*
             * Search in node-sources tags name…
             */
            if ($searchTags) {
                /*
                 * @see http://www.solrtutorial.com/solr-query-syntax.html
                 */
                if ($singleWord) {
                    $queryTxt = sprintf('(title:%s*)^10 (collection_txt:%s*) (folders_txt:*%s*)', $q, $q, $q);
                } else {
                    $queryTxt = sprintf('(title:"%s"~%d)^10 (collection_txt:"%s"~%d) (folders_txt:"%s"~%d)', $q, $proximity, $q, $proximity, $q, $proximity);
                }
            } else {
                if ($singleWord) {
                    $queryTxt = sprintf('(title:%s*)^5 (collection_txt:%s*)', $q, $q);
                } else {
                    $queryTxt = sprintf('(title:"%s"~%d)^5 (collection_txt:"%s"~%d)', $q, $proximity, $q, $proximity);
                }
            }


            $filterQueries = [];
            $query->setQuery($queryTxt);
            foreach ($args as $key => $value) {
                if (is_array($value)) {
                    foreach ($value as $k => $v) {
                        $filterQueries["fq" . $k] = $v;
                        $query->addFilterQuery([
                            "key" => "fq" . $k,
                            "query" => $v,
                        ]);
                    }
                } else {
                    $query->addParam($key, $value);
                }
            }
            $query->addSort('score', $query::SORT_DESC);
            /*
             * Only need these fields as Doctrine
             * will do the rest.
             */
            $query->addFields([
                'id',
                'document_type_s',
                SolariumDocumentTranslation::IDENTIFIER_KEY,
                'filename_s',
                'locale_s',
            ]);
            $query->setRows($rows);
            /**
             * Add start if not first page.
             */
            if ($page > 1) {
                $query->setStart(($page - 1) * $rows);
            }

            if (null !== $this->logger) {
                $this->logger->debug('[Solr] Request document search…', [
                    'query' => $queryTxt,
                    'filters' => $filterQueries,
                    'params' => $query->getParams(),
                ]);
            }

            $solrRequest = $this->client->select($query);
            return json_decode($solrRequest->getResponse()->getBody(), true);
        } else {
            return null;
        }
    }

    /**
     * @param $args
     * @return mixed
     */
    protected function argFqProcess(&$args)
    {
        if (!isset($args["fq"])) {
            $args["fq"] = [];
        }

        // filter by tag or tags
        if (!empty($args['folders'])) {
            if ($args['folders'] instanceof Folder) {
                $args["fq"][] = "tags_txt:" . $args['folders']->getTranslatedFolders()->first()->getName();
            } elseif (is_array($args['folders'])) {
                foreach ($args['folders'] as $tag) {
                    if ($tag instanceof Folder) {
                        $args["fq"][] = "tags_txt:" . $tag->getTranslatedFolders()->first()->getName();
                    }
                }
            }
            unset($args['folders']);
        }

        if (isset($args['mimeType'])) {
            $tmp = "mime_type_s:";
            if (!is_array($args['mimeType'])) {
                $tmp .= (string) $args['mimeType'];
            } else {
                $value = implode(' AND ', $args['mimeType']);
                $tmp .= '('.$value.')';
            }
            unset($args['mimeType']);
            $args["fq"][] = $tmp;
        }

        /*
         * Filter by translation
         */
        if (isset($args['translation']) && $args['translation'] instanceof Translation) {
            $args["fq"][] = "locale_s:" . $args['translation']->getLocale();
        }

        /*
         * Filter by filename
         */
        if (isset($args['filename'])) {
            $args["fq"][] = "filename_s:" . trim($args['filename']);
        }

        return $args;
    }

    /**
     * @return string
     */
    protected function getDocumentType()
    {
        return 'Document';
    }

    /**
     * @param $response
     * @return array|null
     */
    protected function parseSolrResponse($response)
    {
        if (null !== $response) {
            $doc = array_map(
                function ($n) use ($response) {
                    if (isset($response["highlighting"])) {
                        return [
                            "document" => $this->em->find(
                                'RZ\Roadiz\Core\Entities\DocumentTranslation',
                                (int) $n[SolariumDocumentTranslation::IDENTIFIER_KEY]
                            ),
                            "highlighting" => $response["highlighting"][$n['id']],
                        ];
                    }
                    return $this->em->find(
                        'RZ\Roadiz\Core\Entities\DocumentTranslation',
                        $n[SolariumDocumentTranslation::IDENTIFIER_KEY]
                    );
                },
                $response['response']['docs']
            );

            return $doc;
        }

        return null;
    }
}
