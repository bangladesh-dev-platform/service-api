<?php

declare(strict_types=1);

namespace App\Domain\Video;

interface VideoRepository
{
    public function findById(string $id): ?Video;

    public function findBySource(string $sourceType, string $sourceRef): ?Video;

    public function save(Video $video): Video;

    /**
     * @return Video[]
     */
    public function getFeed(?string $categorySlug, int $limit, int $offset): array;

    /**
     * @return Video[]
     */
    public function search(?string $query, int $limit, int $offset): array;
}
