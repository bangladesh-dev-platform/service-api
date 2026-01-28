-- Migration: 006_create_video_portal_schema.sql
-- Description: Initialize dedicated schema and tables for the video portal service

CREATE SCHEMA IF NOT EXISTS video_portal;

CREATE TABLE IF NOT EXISTS video_portal.videos (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    source_type VARCHAR(32) NOT NULL,
    source_ref VARCHAR(255) NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    channel_name VARCHAR(255),
    duration_seconds INTEGER,
    thumbnail_url TEXT,
    status VARCHAR(32) NOT NULL DEFAULT 'published',
    visibility VARCHAR(32) NOT NULL DEFAULT 'public',
    tags JSONB NOT NULL DEFAULT '[]'::jsonb,
    metadata JSONB NOT NULL DEFAULT '{}'::jsonb,
    cached_at TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP,
    created_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE UNIQUE INDEX IF NOT EXISTS idx_video_portal_videos_source
    ON video_portal.videos (source_type, source_ref);

CREATE INDEX IF NOT EXISTS idx_video_portal_videos_cached_at
    ON video_portal.videos (cached_at DESC);

CREATE TRIGGER update_video_portal_videos_updated_at
    BEFORE UPDATE ON video_portal.videos
    FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();

CREATE TABLE IF NOT EXISTS video_portal.video_categories (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    slug VARCHAR(100) UNIQUE NOT NULL,
    name_en VARCHAR(150) NOT NULL,
    name_bn VARCHAR(150),
    created_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS video_portal.video_category_assignments (
    video_id UUID NOT NULL REFERENCES video_portal.videos(id) ON DELETE CASCADE,
    category_id UUID NOT NULL REFERENCES video_portal.video_categories(id) ON DELETE CASCADE,
    weight INTEGER DEFAULT 0,
    assigned_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (video_id, category_id)
);

CREATE TABLE IF NOT EXISTS video_portal.video_assets (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    video_id UUID NOT NULL REFERENCES video_portal.videos(id) ON DELETE CASCADE,
    storage_key TEXT NOT NULL,
    storage_bucket VARCHAR(120),
    storage_region VARCHAR(64),
    mime_type VARCHAR(64),
    resolution VARCHAR(32),
    bitrate INTEGER,
    size_bytes BIGINT,
    checksum VARCHAR(128),
    status VARCHAR(32) NOT NULL DEFAULT 'pending',
    metadata JSONB NOT NULL DEFAULT '{}'::jsonb,
    created_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TRIGGER update_video_portal_video_assets_updated_at
    BEFORE UPDATE ON video_portal.video_assets
    FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();

CREATE TABLE IF NOT EXISTS video_portal.video_ingest_jobs (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    source_type VARCHAR(32) NOT NULL,
    source_ref VARCHAR(255),
    payload JSONB NOT NULL DEFAULT '{}'::jsonb,
    status VARCHAR(32) NOT NULL DEFAULT 'pending',
    attempts INTEGER NOT NULL DEFAULT 0,
    last_error TEXT,
    scheduled_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP,
    started_at TIMESTAMPTZ,
    completed_at TIMESTAMPTZ,
    created_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX IF NOT EXISTS idx_video_portal_ingest_jobs_status
    ON video_portal.video_ingest_jobs (status, scheduled_at);

CREATE TRIGGER update_video_portal_video_ingest_jobs_updated_at
    BEFORE UPDATE ON video_portal.video_ingest_jobs
    FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();

CREATE TABLE IF NOT EXISTS video_portal.user_video_bookmarks (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    user_id UUID NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    video_id UUID NOT NULL REFERENCES video_portal.videos(id) ON DELETE CASCADE,
    notes TEXT,
    created_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE (user_id, video_id)
);

CREATE INDEX IF NOT EXISTS idx_video_portal_bookmarks_user
    ON video_portal.user_video_bookmarks (user_id, created_at DESC);

CREATE TABLE IF NOT EXISTS video_portal.user_video_history (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    user_id UUID NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    video_id UUID NOT NULL REFERENCES video_portal.videos(id) ON DELETE CASCADE,
    last_watched_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP,
    last_position_seconds INTEGER DEFAULT 0,
    watch_count INTEGER NOT NULL DEFAULT 1,
    context JSONB NOT NULL DEFAULT '{}'::jsonb,
    created_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE (user_id, video_id)
);

CREATE INDEX IF NOT EXISTS idx_video_portal_history_user
    ON video_portal.user_video_history (user_id, last_watched_at DESC);

CREATE TRIGGER update_video_portal_history_updated_at
    BEFORE UPDATE ON video_portal.user_video_history
    FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();
