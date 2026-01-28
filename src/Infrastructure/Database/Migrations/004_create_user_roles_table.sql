-- Migration: 004_create_user_roles_table.sql
-- Description: Create user_roles table for role-based access control

CREATE TABLE IF NOT EXISTS user_roles (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    user_id UUID NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    role VARCHAR(50) NOT NULL, -- admin, editor, author, subscriber
    assigned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    assigned_by UUID REFERENCES users(id),
    UNIQUE(user_id, role)
);

-- Indexes for performance
CREATE INDEX IF NOT EXISTS idx_user_roles_user_id ON user_roles(user_id);
CREATE INDEX IF NOT EXISTS idx_user_roles_role ON user_roles(role);

-- Add some comments for documentation
COMMENT ON TABLE user_roles IS 'Stores user role assignments for RBAC';
COMMENT ON COLUMN user_roles.role IS 'Possible values: admin, editor, author, subscriber';
