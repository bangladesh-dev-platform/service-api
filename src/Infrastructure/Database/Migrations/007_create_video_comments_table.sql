-- Migration: 007_create_video_comments_table.sql
-- Description: Add video comments + likes for the video portal

CREATE TABLE IF NOT EXISTS video_portal.video_comments (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    video_id UUID NOT NULL REFERENCES video_portal.videos(id) ON DELETE CASCADE,
    user_id UUID NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    parent_id UUID REFERENCES video_portal.video_comments(id) ON DELETE CASCADE,
    body TEXT NOT NULL,
    like_count INTEGER NOT NULL DEFAULT 0,
    is_deleted BOOLEAN NOT NULL DEFAULT FALSE,
    created_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX IF NOT EXISTS idx_video_comments_video ON video_portal.video_comments (video_id, created_at DESC);
CREATE INDEX IF NOT EXISTS idx_video_comments_parent ON video_portal.video_comments (parent_id, created_at ASC);

CREATE TRIGGER update_video_comments_updated_at
    BEFORE UPDATE ON video_portal.video_comments
    FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();

CREATE TABLE IF NOT EXISTS video_portal.video_comment_likes (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    comment_id UUID NOT NULL REFERENCES video_portal.video_comments(id) ON DELETE CASCADE,
    user_id UUID NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    created_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE (comment_id, user_id)
);

CREATE INDEX IF NOT EXISTS idx_video_comment_likes_user ON video_portal.video_comment_likes (user_id);
