# Video Portal Service

Multi-source video catalog + engagement APIs that power the StreamVibe (service-youtube) experience and future Bangla-first media apps.

## Capabilities

- Canonical video catalog (`video_portal.videos`) with cached metadata for YouTube, uploads, and partner feeds
- Category taxonomy + tagging for recommendations
- User bookmarks and watch history (resume position, context)
- Ingest job tracking for future upload/transcode pipeline

## API Endpoints

| Method | Endpoint | Description | Auth |
| --- | --- | --- | --- |
| GET | `/api/v1/video/feed?category=&page=&limit=` | Paginated feed (cached) | No |
| GET | `/api/v1/video/search?q=&page=&limit=` | Search catalog | No |
| GET | `/api/v1/video/{id}` | Fetch video by UUID | No |
| POST | `/api/v1/video/catalog` | Upsert video metadata (ingest) | Yes (admin) |
| GET | `/api/v1/video/bookmarks` | Current user's bookmarks | Yes |
| POST | `/api/v1/video/bookmarks` | Create bookmark (`video_id`, optional `notes`) | Yes |
| DELETE | `/api/v1/video/bookmarks/{videoId}` | Remove bookmark | Yes |
| GET | `/api/v1/video/history` | List watch history | Yes |
| POST | `/api/v1/video/history` | Record playback (`video_id`, `position_seconds`, `context`) | Yes |
| GET | `/api/v1/video/{id}/comments` | Paginated comments for a video | No |
| POST | `/api/v1/video/{id}/comments` | Add a comment/reply (`text`, optional `parent_id`) | Yes |
| DELETE | `/api/v1/video/comments/{commentId}` | Remove own comment (admin can remove any) | Yes |

> Authenticated endpoints require a standard Banglade.sh access token. Upload/admin operations are guarded by the `admin` role (creator roles coming soon).

### Bookmark Request Example

```bash
curl -X POST http://localhost:8080/api/v1/video/bookmarks \
  -H "Authorization: Bearer <access_token>" \
  -H "Content-Type: application/json" \
  -d '{
    "video_id": "f4b7e71c-...",
    "notes": "Watch later"
  }'
```

### History Request Example

```bash
curl -X POST http://localhost:8080/api/v1/video/history \
  -H "Authorization: Bearer <access_token>" \
  -H "Content-Type: application/json" \
  -d '{
    "video_id": "f4b7e71c-...",
    "position_seconds": 120,
    "context": {"source": "home_feed"}
  }'
```

## Schema Overview (`video_portal`)

- `videos` – canonical metadata (`source_type`, `source_ref`, `tags`, `metadata`, `cached_at`).
- `video_assets` – storage/transcode rows for uploads.
- `video_ingest_jobs` – ingestion/transcode queue with retries + audit timestamps.
- `video_categories` + `video_category_assignments` – localized taxonomy.
- `user_video_bookmarks` – per-user watchlist entries.
- `user_video_history` – resume state + watch counts.
- `video_comments` – threaded comments per video with soft-delete + like counters.
- `video_comment_likes` – (future) per-user reactions.

See `src/Infrastructure/Database/Migrations/006_create_video_portal_schema.sql` for full DDL.

## Future Roadmap

- Upload/transcode pipeline backed by MinIO/S3
- Recommendation service driven by bookmarks/history data
- Additional source adapters (partner feeds, curated playlists)
