<?php

namespace test\Manager;

use Exception;
use DateInterval;
use test\Provider\DataProviderInterface;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Log\LoggerInterface;

/**
 * Class DataManager
 * @package test\Manager
 */
class DataManager
{
    const CACHE_KEY = 'manager.response_data';
    /**
     * @var DataProviderInterface
     */
    public $dataProvider;
    /**
     * @var CacheItemPoolInterface
     */
    public $cache;
    /**
     * @var LoggerInterface
     */
    public $logger;

    /**
     * @param DataProviderInterface $dataProvider
     * @param CacheItemPoolInterface $cache
     * @param LoggerInterface $logger
     */
    public function __construct(DataProviderInterface $dataProvider, CacheItemPoolInterface $cache, LoggerInterface $logger)
    {
        $this->dataProvider = $dataProvider;
        $this->cache = $cache;
        $this->logger = $logger;
    }

    /**
     * @param array $input
     * @return array
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function getResponse(array $input): array
    {
        try {
            $cacheItem = $this->cache->getItem(self::CACHE_KEY);
            if ($cacheItem->isHit()) {
                return $cacheItem->get();
            }

            $result = $this->dataProvider->get($input);
            $cacheItem->set($result)->expiresAfter(DateInterval::createFromDateString('1 day'));
            $this->cache->save($cacheItem);

            return $result;
        } catch (Exception $e) {
            $this->logger->critical('Error');
        }

        return [];
    }
}