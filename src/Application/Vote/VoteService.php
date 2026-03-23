<?php

declare(strict_types=1);

namespace App\Application\Vote;

use App\Infrastructure\Repositories\Vote\PgVoteRepository;

class VoteService
{
    public function __construct(private PgVoteRepository $repository)
    {
    }

    public function getOverview(?string $electionId = null): ?array
    {
        $election = $this->resolveElection($electionId);
        if (!$election) {
            return null;
        }

        $meta = $this->repository->getMeta();
        $districtsCount = (int)($meta['districts_count']['en'] ?? 0);

        return [
            'meta' => [
                'data_status' => $meta['data_status'] ?? ['en' => null, 'bn' => null],
                'last_updated' => $meta['last_updated']['en'] ?? $election['updated_at'],
                'districts_count' => $districtsCount,
            ],
            'election' => $election,
            'elections' => $this->repository->getElections(),
            'hero_stats' => [
                [
                    'id' => 'turnout',
                    'label' => ['en' => 'Turnout', 'bn' => 'ভোটার উপস্থিতি'],
                    'value' => $election['turnout_percent'] ?? 0,
                    'type' => 'percent',
                ],
                [
                    'id' => 'seats',
                    'label' => ['en' => 'Seats declared', 'bn' => 'ঘোষিত আসন'],
                    'value' => $election['total_seats'] ?? 0,
                    'type' => 'count',
                ],
                [
                    'id' => 'districts',
                    'label' => ['en' => 'District summaries', 'bn' => 'জেলা সারসংক্ষেপ'],
                    'value' => $districtsCount,
                    'type' => 'count',
                ],
            ],
            'highlights' => $this->repository->getHighlights(),
            'parties' => $this->repository->getPartiesByElection($election['id']),
            'regions' => $this->repository->getRegionsByElection($election['id']),
            'timeline' => $this->repository->getTimelineByElection($election['id']),
            'candidates' => $this->repository->getCandidatesByElection($election['id']),
            'resources' => $this->repository->getResourcesByElection($election['id']),
            'methodology' => [
                'points' => $this->repository->getMethodologyPoints(),
                'sources' => $this->repository->getSources(),
            ],
        ];
    }

    public function getElections(): array
    {
        return $this->repository->getElections();
    }

    public function getParties(?string $electionId = null): ?array
    {
        $election = $this->resolveElection($electionId);
        if (!$election) {
            return null;
        }

        return [
            'election' => $election,
            'items' => $this->repository->getPartiesByElection($election['id']),
        ];
    }

    public function getRegions(?string $electionId = null): ?array
    {
        $election = $this->resolveElection($electionId);
        if (!$election) {
            return null;
        }

        return [
            'election' => $election,
            'items' => $this->repository->getRegionsByElection($election['id']),
        ];
    }

    public function getTimeline(?string $electionId = null): ?array
    {
        $election = $this->resolveElection($electionId);
        if (!$election) {
            return null;
        }

        return [
            'election' => $election,
            'items' => $this->repository->getTimelineByElection($election['id']),
        ];
    }

    public function getCandidates(?string $electionId = null): ?array
    {
        $election = $this->resolveElection($electionId);
        if (!$election) {
            return null;
        }

        return [
            'election' => $election,
            'items' => $this->repository->getCandidatesByElection($election['id']),
        ];
    }

    public function getResources(?string $electionId = null): ?array
    {
        $election = $this->resolveElection($electionId);
        if (!$election) {
            return null;
        }

        return [
            'election' => $election,
            'items' => $this->repository->getResourcesByElection($election['id']),
        ];
    }

    public function getMethodology(): array
    {
        return [
            'points' => $this->repository->getMethodologyPoints(),
            'sources' => $this->repository->getSources(),
        ];
    }

    private function resolveElection(?string $electionId): ?array
    {
        if ($electionId) {
            return $this->repository->getElectionById($electionId);
        }

        return $this->repository->getLatestElection();
    }
}
