<?php

declare(strict_types=1);

namespace App\Infrastructure\Repositories\Vote;

use PDO;

class PgVoteRepository
{
    public function __construct(private PDO $pdo)
    {
    }

    public function getMeta(): array
    {
        $stmt = $this->pdo->query('SELECT key, value_en, value_bn FROM vote.meta');
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $meta = [];
        foreach ($rows as $row) {
            $meta[$row['key']] = [
                'en' => $row['value_en'],
                'bn' => $row['value_bn'],
            ];
        }

        return $meta;
    }

    public function getElections(): array
    {
        $stmt = $this->pdo->query('SELECT * FROM vote.elections ORDER BY election_date DESC');
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return array_map(fn(array $row) => $this->mapElection($row), $rows);
    }

    public function getLatestElection(): ?array
    {
        $stmt = $this->pdo->query('SELECT * FROM vote.elections ORDER BY election_date DESC LIMIT 1');
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ? $this->mapElection($row) : null;
    }

    public function getElectionById(string $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM vote.elections WHERE id = :id OR slug = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ? $this->mapElection($row) : null;
    }

    public function getPartiesByElection(string $electionId): array
    {
        $sql = 'SELECT p.*, ep.seats, ep.vote_share
                FROM vote.election_parties ep
                JOIN vote.parties p ON p.id = ep.party_id
                WHERE ep.election_id = :election_id
                ORDER BY ep.seats DESC';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['election_id' => $electionId]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return array_map(function(array $row) {
            return [
                'id' => $row['id'],
                'slug' => $row['slug'],
                'name' => ['en' => $row['name_en'], 'bn' => $row['name_bn']],
                'symbol' => ['en' => $row['symbol_en'], 'bn' => $row['symbol_bn']],
                'leader' => ['en' => $row['leader_en'], 'bn' => $row['leader_bn']],
                'founded' => isset($row['founded']) ? (int)$row['founded'] : null,
                'color' => $row['color'],
                'seats' => (int)$row['seats'],
                'vote_share' => isset($row['vote_share']) ? (float)$row['vote_share'] : 0.0,
            ];
        }, $rows);
    }

    public function getRegionsByElection(string $electionId): array
    {
        $sql = 'SELECT r.*, rr.turnout_percent, rr.leading_party_id
                FROM vote.region_results rr
                JOIN vote.regions r ON r.id = rr.region_id
                WHERE rr.election_id = :election_id
                ORDER BY r.name_en ASC';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['election_id' => $electionId]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return array_map(function(array $row) {
            return [
                'id' => $row['id'],
                'slug' => $row['slug'],
                'name' => ['en' => $row['name_en'], 'bn' => $row['name_bn']],
                'turnout_percent' => isset($row['turnout_percent']) ? (float)$row['turnout_percent'] : null,
                'leading_party_id' => $row['leading_party_id'],
            ];
        }, $rows);
    }

    public function getTimelineByElection(string $electionId): array
    {
        $sql = 'SELECT * FROM vote.timeline_events WHERE election_id = :election_id ORDER BY event_date ASC';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['election_id' => $electionId]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return array_map(function(array $row) {
            return [
                'id' => $row['id'],
                'date' => $row['event_date'],
                'title' => ['en' => $row['title_en'], 'bn' => $row['title_bn']],
                'note' => ['en' => $row['note_en'], 'bn' => $row['note_bn']],
            ];
        }, $rows);
    }

    public function getCandidatesByElection(string $electionId): array
    {
        $sql = 'SELECT * FROM vote.candidates WHERE election_id = :election_id ORDER BY name_en ASC';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['election_id' => $electionId]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return array_map(function(array $row) {
            return [
                'id' => $row['id'],
                'party_id' => $row['party_id'],
                'name' => ['en' => $row['name_en'], 'bn' => $row['name_bn']],
                'constituency' => ['en' => $row['constituency_en'], 'bn' => $row['constituency_bn']],
                'profile' => ['en' => $row['profile_en'], 'bn' => $row['profile_bn']],
            ];
        }, $rows);
    }

    public function getResourcesByElection(string $electionId): array
    {
        $sql = 'SELECT * FROM vote.resources WHERE election_id = :election_id ORDER BY resource_date DESC';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['election_id' => $electionId]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return array_map(function(array $row) {
            return [
                'id' => $row['id'],
                'type' => ['en' => $row['type_en'], 'bn' => $row['type_bn']],
                'title' => ['en' => $row['title_en'], 'bn' => $row['title_bn']],
                'date' => $row['resource_date'],
                'url' => $row['url'],
            ];
        }, $rows);
    }

    public function getMethodologyPoints(): array
    {
        $sql = 'SELECT * FROM vote.methodology_points ORDER BY sort_order ASC';
        $stmt = $this->pdo->query($sql);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return array_map(function(array $row) {
            return [
                'id' => $row['id'],
                'title' => ['en' => $row['title_en'], 'bn' => $row['title_bn']],
                'body' => ['en' => $row['body_en'], 'bn' => $row['body_bn']],
            ];
        }, $rows);
    }

    public function getSources(): array
    {
        $sql = 'SELECT * FROM vote.sources ORDER BY sort_order ASC';
        $stmt = $this->pdo->query($sql);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return array_map(function(array $row) {
            return [
                'id' => $row['id'],
                'name' => ['en' => $row['name_en'], 'bn' => $row['name_bn']],
                'detail' => ['en' => $row['detail_en'], 'bn' => $row['detail_bn']],
            ];
        }, $rows);
    }

    public function getHighlights(): array
    {
        $sql = 'SELECT * FROM vote.highlights ORDER BY sort_order ASC';
        $stmt = $this->pdo->query($sql);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return array_map(function(array $row) {
            return [
                'id' => $row['id'],
                'title' => ['en' => $row['title_en'], 'bn' => $row['title_bn']],
                'note' => ['en' => $row['note_en'], 'bn' => $row['note_bn']],
                'value' => isset($row['value']) ? (float)$row['value'] : 0.0,
                'value_type' => $row['value_type'],
            ];
        }, $rows);
    }

    private function mapElection(array $row): array
    {
        return [
            'id' => $row['id'],
            'slug' => $row['slug'],
            'type' => ['en' => $row['type_en'], 'bn' => $row['type_bn']],
            'title' => ['en' => $row['title_en'], 'bn' => $row['title_bn']],
            'status' => ['en' => $row['status_en'], 'bn' => $row['status_bn']],
            'date' => $row['election_date'],
            'summary' => ['en' => $row['summary_en'], 'bn' => $row['summary_bn']],
            'turnout_percent' => isset($row['turnout_percent']) ? (float)$row['turnout_percent'] : null,
            'total_seats' => isset($row['total_seats']) ? (int)$row['total_seats'] : null,
            'registered' => isset($row['registered']) ? (int)$row['registered'] : null,
            'votes_cast' => isset($row['votes_cast']) ? (int)$row['votes_cast'] : null,
            'valid_votes' => isset($row['valid_votes']) ? (int)$row['valid_votes'] : null,
            'rejected_votes' => isset($row['rejected_votes']) ? (int)$row['rejected_votes'] : null,
            'updated_at' => $row['updated_at'],
        ];
    }
}
