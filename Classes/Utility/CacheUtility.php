<?php

/**
 * data
 *
 * @category   Tollwerk
 * @package    Tollwerk\TwLucenesearch
 * @subpackage Tollwerk\TwLucenesearch\Utility
 * @author     Joschi Kuphal <joschi@tollwerk.de> / @jkphl
 * @copyright  Copyright © 2020 Joschi Kuphal <joschi@tollwerk.de> / @jkphl
 * @license    http://opensource.org/licenses/MIT The MIT License (MIT)
 */

/***********************************************************************************
 *  The MIT License (MIT)
 *
 *  Copyright © 2020 Joschi Kuphal <joschi@tollwerk.de>
 *
 *  Permission is hereby granted, free of charge, to any person obtaining a copy of
 *  this software and associated documentation files (the "Software"), to deal in
 *  the Software without restriction, including without limitation the rights to
 *  use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of
 *  the Software, and to permit persons to whom the Software is furnished to do so,
 *  subject to the following conditions:
 *
 *  The above copyright notice and this permission notice shall be included in all
 *  copies or substantial portions of the Software.
 *
 *  THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 *  IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS
 *  FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR
 *  COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER
 *  IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN
 *  CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
 ***********************************************************************************/

namespace Tollwerk\TwLucenesearch\Utility;

use TYPO3\CMS\Core\Cache\Frontend\FrontendInterface;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\SingletonInterface;

/**
 * Cache Utility
 *
 * @package    Tollwerk\TwLucenesearch
 * @subpackage Tollwerk\TwLucenesearch\Utility
 */
class CacheUtility implements SingletonInterface
{
    /**
     * Cache Interface
     *
     * @var FrontendInterface
     */
    protected $cache;

    /**
     * Constructor
     *
     * @param FrontendInterface $cache Cache Interface
     */
    public function __construct(FrontendInterface $cache)
    {
        $this->cache = $cache;
    }

    /**
     * Check whether a document needs to be indexed
     *
     * @param string $type       Document type
     * @param string $identifier Document identifier
     *
     * @return bool Needs indexing
     */
    public function needsIndexing(string $type, string $identifier): bool
    {
        return !$this->cache->has(md5($type.'|'.$identifier));
    }

    /**
     * Register a document as indexed
     *
     * @param string $type       Document type
     * @param string $identifier Document identifier
     * @param int[] $uids        Document IDs
     * @param string $checksum   Checksum
     */
    public function registerIndexed(string $type, string $identifier, array $uids, string $checksum): void
    {
        $this->cache->set(
            md5($type.'|'.$identifier),
            $checksum,
            array_map(function($uid) use ($type) {
                return $type.'_'.$uid;
            }, array_filter($uids))
        );
    }

    /**
     * Unregister a cached document
     *
     * @param array $params            Parameter
     * @param DataHandler|null $dataHandler Data handler
     */
    public function unregisterIndexed(array $params, DataHandler $dataHandler = null): void
    {
        if (!empty($params['table'])) {
            if ($params['table'] == 'pages') {
                $this->cache->flushByTag(Indexer::PAGE.'_'.$params['uid']);
            } elseif (!empty($params['uid_page'])) {
                $this->cache->flushByTag(Indexer::PAGE.'_'.$params['uid_page']);
            }
        }
    }
}
