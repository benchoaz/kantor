-- =====================================================
-- MANUAL INSERT SURAT SM/001/2026 TO API
-- Based on screenshot data
-- =====================================================

USE sidiksae_api;

-- Generate UUID for this surat (deterministic based on nomor)
SET @uuid_surat = 'be1963a3-8f1b-5ac5-908f-5a04c841d151';

-- Insert surat SM/001/2026
INSERT INTO surat (
    uuid,
    nomor_surat,
    tanggal_surat,
    perihal,
    pengirim,
    file_path,
    source_app,
    external_id,
    metadata,
    created_at,
    updated_at
) VALUES (
    @uuid_surat,
    '134',
    '2026-01-06',
    'UNDANGAN Kepada: Sdr. Kepala Perangkat Daerah/Kepala',
    'BADAN PERENCANAAN',
    'https://suratqu.sidiksae.my.id/storage/surat/2026/be1963a3-8f1b-5ac5-908f-5a04c841d151.pdf',
    'suratqu',
    'SM/001/2026',
    JSON_OBJECT(
        'no_agenda', 'SM/001/2026',
        'nomor_surat', '134',
        'asal_surat', 'BADAN PERENCANAAN',
        'tanggal_surat', '2026-01-06',
        'perihal', 'UNDANGAN Kepada: Sdr. Kepala Perangkat Daerah/Kepala',
        'sifat', 'penting',
        'klasifikasi', 'undangan'
    ),
    '2026-01-10 13:20:00',
    NOW()
)
ON DUPLICATE KEY UPDATE
    updated_at = NOW();

-- Verify insert
SELECT 
    uuid,
    nomor_surat,
    perihal,
    pengirim as asal_surat,
    file_path,
    source_app
FROM surat 
WHERE uuid = @uuid_surat;

SELECT CONCAT('Surat SM/001/2026 inserted. Total surat: ', COUNT(*)) as status
FROM surat WHERE source_app = 'suratqu';
