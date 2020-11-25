<?php

namespace test\Provider;

/**
 * Interface DataProviderInterface
 * @package test\Provider
 */
interface DataProviderInterface
{
    /**
     * @param array $request
     * @return array
     */
    public function get(array $request): array;
}