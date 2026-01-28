-- Migration: 005_create_user_permissions_table.sql
-- Description: Create user_permissions table for fine-grained permissions

CREATE TABLE IF NOT EXISTS user_permissions (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    user_id UUID NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    permission VARCHAR(100) NOT NULL, -- posts.create, files.delete, etc.
    resource_type VARCHAR(50), -- posts, files, comments (for micro-apps)
    granted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    granted_by UUID REFERENCES users(id),
    UNIQUE(user_id, permission, resource_type)
);

-- Indexes for performance
CREATE INDEX IF NOT EXISTS idx_user_permissions_user_id ON user_permissions(user_id);
CREATE INDEX IF NOT EXISTS idx_user_permissions_permission ON user_permissions(permission);
CREATE INDEX IF NOT EXISTS idx_user_permissions_resource_type ON user_permissions(resource_type);

-- Add comments for documentation
COMMENT ON TABLE user_permissions IS 'Stores fine-grained user permissions for micro-app integration';
COMMENT ON COLUMN user_permissions.permission IS 'Format: resource.action (e.g., posts.create, files.delete)';
COMMENT ON COLUMN user_permissions.resource_type IS 'The micro-app/resource type this permission applies to';
