-- =====================================================
-- CREATE: event_log table for audit trail
-- Stores all events in event-driven architecture
-- =====================================================

USE sidiksae_api;

-- Create event_log table if not exists
CREATE TABLE IF NOT EXISTS event_log (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    event_type VARCHAR(50) NOT NULL COMMENT 'Type of event (KIRIM_KE_CAMAT, CREATE_DISPOSISI, etc)',
    entity_type VARCHAR(50) NOT NULL COMMENT 'Entity affected (surat, disposisi, etc)',
    entity_uuid CHAR(36) NOT NULL COMMENT 'UUID of affected entity',
    actor_uuid CHAR(36) NULL COMMENT 'UUID of user who triggered event',
    payload JSON NULL COMMENT 'Event data payload',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_event_type (event_type),
    INDEX idx_entity_uuid (entity_uuid),
    INDEX idx_actor_uuid (actor_uuid),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Event log for audit trail';

-- Add status column to surat table if not exists
ALTER TABLE surat 
ADD COLUMN IF NOT EXISTS status VARCHAR(50) DEFAULT 'DRAFT' 
COMMENT 'Status: DRAFT, FINAL, DIKIRIM_KE_CAMAT, DISPOSISI';

-- Verify
SHOW COLUMNS FROM event_log;
SHOW COLUMNS FROM surat LIKE 'status';

SELECT 'Event log table ready for audit trail' as message;
