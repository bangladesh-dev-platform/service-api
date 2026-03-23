# Vote API

Base path: `/api/v1/vote`

All endpoints are public and return JSON in the standard response envelope:

```json
{
  "success": true,
  "data": {}
}
```

## Endpoints

### GET `/overview`
Returns the full election hub payload for the latest election or a specific election.

Query params:
- `election_id` (optional) UUID or slug

Response payload:
- `meta`
- `election`
- `elections`
- `hero_stats`
- `highlights`
- `parties`
- `regions`
- `timeline`
- `candidates`
- `resources`
- `methodology`

### GET `/elections`
Returns all elections (newest first).

### GET `/parties`
Returns parties for the latest or selected election.

Query params:
- `election_id` (optional)

### GET `/regions`
Returns regions for the latest or selected election.

Query params:
- `election_id` (optional)

### GET `/timeline`
Returns timeline events for the latest or selected election.

Query params:
- `election_id` (optional)

### GET `/candidates`
Returns candidates for the latest or selected election.

Query params:
- `election_id` (optional)

### GET `/resources`
Returns external documents/resources for the latest or selected election.

Query params:
- `election_id` (optional)

### GET `/methodology`
Returns methodology points and sources.

## Example

```bash
curl https://api.banglade.sh/api/v1/vote/overview
```

## Data Source

Seed data is stored in `service-api/src/Infrastructure/Database/Migrations/008_create_vote_schema.sql`.
Update that migration or add new migrations to load official results.
