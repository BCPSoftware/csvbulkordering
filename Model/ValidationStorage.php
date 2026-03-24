<?php

declare(strict_types=1);

namespace Oporteo\OrderUpload\Model;

use Magento\Framework\App\CacheInterface;
use Magento\Framework\Serialize\SerializerInterface;

class ValidationStorage
{
    private const CACHE_PREFIX = 'oporteo_orderupload_validation_';
    private const LIFETIME = 1800;

    public function __construct(
        private readonly CacheInterface $cache,
        private readonly SerializerInterface $serializer
    ) {
    }

    /**
     * @param array<string, mixed> $payload
     */
    public function store(array $payload): string
    {
        $token = bin2hex(random_bytes(16));
        $this->cache->save(
            $this->serializer->serialize($payload),
            $this->getCacheKey($token),
            [],
            self::LIFETIME
        );

        return $token;
    }

    /**
     * @return array<string, mixed>
     */
    public function consume(string $token): array
    {
        $cacheKey = $this->getCacheKey($token);
        $payload = (string)$this->cache->load($cacheKey);
        $this->cache->remove($cacheKey);

        return $payload !== '' ? (array)$this->serializer->unserialize($payload) : [];
    }

    private function getCacheKey(string $token): string
    {
        return self::CACHE_PREFIX . $token;
    }
}
