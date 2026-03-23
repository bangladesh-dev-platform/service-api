-- Migration: 008_create_vote_schema.sql
-- Description: Initialize schema and tables for vote.banglade.sh

CREATE SCHEMA IF NOT EXISTS vote;

CREATE TABLE IF NOT EXISTS vote.elections (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    slug VARCHAR(80) UNIQUE NOT NULL,
    type_en VARCHAR(120) NOT NULL,
    type_bn VARCHAR(120) NOT NULL,
    title_en VARCHAR(200) NOT NULL,
    title_bn VARCHAR(200) NOT NULL,
    status_en VARCHAR(80) NOT NULL,
    status_bn VARCHAR(80) NOT NULL,
    election_date DATE NOT NULL,
    summary_en TEXT,
    summary_bn TEXT,
    turnout_percent NUMERIC(5,2),
    total_seats INTEGER,
    registered BIGINT,
    votes_cast BIGINT,
    valid_votes BIGINT,
    rejected_votes BIGINT,
    created_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX IF NOT EXISTS idx_vote_elections_date ON vote.elections (election_date DESC);

CREATE TRIGGER update_vote_elections_updated_at
    BEFORE UPDATE ON vote.elections
    FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();

CREATE TABLE IF NOT EXISTS vote.parties (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    slug VARCHAR(80) UNIQUE NOT NULL,
    name_en VARCHAR(150) NOT NULL,
    name_bn VARCHAR(150) NOT NULL,
    symbol_en VARCHAR(120),
    symbol_bn VARCHAR(120),
    leader_en VARCHAR(150),
    leader_bn VARCHAR(150),
    founded INTEGER,
    color VARCHAR(16),
    created_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS vote.election_parties (
    election_id UUID NOT NULL REFERENCES vote.elections(id) ON DELETE CASCADE,
    party_id UUID NOT NULL REFERENCES vote.parties(id) ON DELETE CASCADE,
    seats INTEGER NOT NULL DEFAULT 0,
    vote_share NUMERIC(5,2) NOT NULL DEFAULT 0,
    PRIMARY KEY (election_id, party_id)
);

CREATE TABLE IF NOT EXISTS vote.regions (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    slug VARCHAR(80) UNIQUE NOT NULL,
    name_en VARCHAR(150) NOT NULL,
    name_bn VARCHAR(150) NOT NULL
);

CREATE TABLE IF NOT EXISTS vote.region_results (
    election_id UUID NOT NULL REFERENCES vote.elections(id) ON DELETE CASCADE,
    region_id UUID NOT NULL REFERENCES vote.regions(id) ON DELETE CASCADE,
    turnout_percent NUMERIC(5,2),
    leading_party_id UUID REFERENCES vote.parties(id),
    PRIMARY KEY (election_id, region_id)
);

CREATE TABLE IF NOT EXISTS vote.timeline_events (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    election_id UUID NOT NULL REFERENCES vote.elections(id) ON DELETE CASCADE,
    event_date DATE NOT NULL,
    title_en VARCHAR(200) NOT NULL,
    title_bn VARCHAR(200) NOT NULL,
    note_en TEXT,
    note_bn TEXT,
    created_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS vote.candidates (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    election_id UUID NOT NULL REFERENCES vote.elections(id) ON DELETE CASCADE,
    party_id UUID REFERENCES vote.parties(id),
    name_en VARCHAR(150) NOT NULL,
    name_bn VARCHAR(150) NOT NULL,
    constituency_en VARCHAR(120),
    constituency_bn VARCHAR(120),
    profile_en TEXT,
    profile_bn TEXT,
    created_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS vote.resources (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    election_id UUID NOT NULL REFERENCES vote.elections(id) ON DELETE CASCADE,
    type_en VARCHAR(80) NOT NULL,
    type_bn VARCHAR(80) NOT NULL,
    title_en VARCHAR(200) NOT NULL,
    title_bn VARCHAR(200) NOT NULL,
    resource_date DATE,
    url TEXT
);

CREATE TABLE IF NOT EXISTS vote.methodology_points (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    title_en VARCHAR(200) NOT NULL,
    title_bn VARCHAR(200) NOT NULL,
    body_en TEXT,
    body_bn TEXT,
    sort_order INTEGER NOT NULL DEFAULT 0
);

CREATE TABLE IF NOT EXISTS vote.sources (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    name_en VARCHAR(200) NOT NULL,
    name_bn VARCHAR(200) NOT NULL,
    detail_en TEXT,
    detail_bn TEXT,
    sort_order INTEGER NOT NULL DEFAULT 0
);

CREATE TABLE IF NOT EXISTS vote.highlights (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    title_en VARCHAR(160) NOT NULL,
    title_bn VARCHAR(160) NOT NULL,
    note_en TEXT,
    note_bn TEXT,
    value NUMERIC(10,2) NOT NULL DEFAULT 0,
    value_type VARCHAR(20) NOT NULL DEFAULT 'count',
    sort_order INTEGER NOT NULL DEFAULT 0
);

CREATE TABLE IF NOT EXISTS vote.meta (
    key VARCHAR(80) PRIMARY KEY,
    value_en TEXT,
    value_bn TEXT
);

-- Seed data
INSERT INTO vote.elections (
    id, slug, type_en, type_bn, title_en, title_bn, status_en, status_bn, election_date,
    summary_en, summary_bn, turnout_percent, total_seats, registered, votes_cast, valid_votes, rejected_votes
) VALUES
    (
        '11111111-1111-1111-1111-111111111111',
        'ge-2026',
        'Parliamentary',
        'জাতীয় সংসদ',
        'Parliamentary General Election 2026',
        'জাতীয় সংসদ নির্বাচন ২০২৬',
        'Final',
        'চূড়ান্ত',
        '2026-02-12',
        'National turnout reached 74.2% with 300 seats certified.',
        'জাতীয় ভোটার উপস্থিতি ৭৪.২% এবং ৩০০ আসনের ফল নিশ্চিত হয়েছে।',
        74.20,
        300,
        124500000,
        92400000,
        90100000,
        2300000
    ),
    (
        '22222222-2222-2222-2222-222222222222',
        'ge-2018',
        'Parliamentary',
        'জাতীয় সংসদ',
        'Parliamentary General Election 2018',
        'জাতীয় সংসদ নির্বাচন ২০১৮',
        'Archived',
        'আর্কাইভ',
        '2018-12-30',
        'Recorded turnout of 79.4% with updated seat balance.',
        '৭৯.৪% ভোটার উপস্থিতি এবং আসন বণ্টনের হালনাগাদ।',
        79.40,
        300,
        104200000,
        82700000,
        81300000,
        1400000
    ),
    (
        '33333333-3333-3333-3333-333333333333',
        'local-2024',
        'Local Government',
        'স্থানীয় সরকার',
        'Local Government Election 2024',
        'স্থানীয় সরকার নির্বাচন ২০২৪',
        'Archived',
        'আর্কাইভ',
        '2024-11-18',
        'Municipal voting across 332 councils and 4,800 wards.',
        '৩৩২টি পৌরসভা ও ৪,৮০০ ওয়ার্ডে ভোট অনুষ্ঠিত।',
        63.10,
        4800,
        59300000,
        37400000,
        36100000,
        1300000
    )
ON CONFLICT (slug) DO NOTHING;

INSERT INTO vote.parties (
    id, slug, name_en, name_bn, symbol_en, symbol_bn, leader_en, leader_bn, founded, color
) VALUES
    (
        'aaaaaaaa-aaaa-aaaa-aaaa-aaaaaaaaaaaa',
        'people-union',
        'People''s Union',
        'জনতার ঐক্য',
        'Lotus',
        'পদ্ম',
        'Arif Rahman',
        'আরিফ রহমান',
        1972,
        '#1b5e3f'
    ),
    (
        'bbbbbbbb-bbbb-bbbb-bbbb-bbbbbbbbbbbb',
        'national-reform',
        'National Reform Front',
        'জাতীয় সংস্কার ফ্রন্ট',
        'Torch',
        'মশাল',
        'Sabina Haque',
        'সাবিনা হক',
        1984,
        '#9c2f2f'
    ),
    (
        'cccccccc-cccc-cccc-cccc-cccccccccccc',
        'green-crescent',
        'Green Crescent Party',
        'সবুজ অর্ধচন্দ্র দল',
        'Crescent',
        'অর্ধচন্দ্র',
        'Kamal Uddin',
        'কামাল উদ্দিন',
        1996,
        '#2f7d53'
    ),
    (
        'dddddddd-dddd-dddd-dddd-dddddddddddd',
        'justice-labor',
        'Justice & Labor Alliance',
        'ন্যায় ও শ্রম জোট',
        'Scale',
        'দাঁড়িপাল্লা',
        'Farah Noor',
        'ফারাহ নূর',
        2003,
        '#c06b2c'
    ),
    (
        'eeeeeeee-eeee-eeee-eeee-eeeeeeeeeeee',
        'independents',
        'Independent Network',
        'স্বতন্ত্র নেটওয়ার্ক',
        'Lantern',
        'লণ্ঠন',
        'Various',
        'বিভিন্ন',
        2010,
        '#55606c'
    )
ON CONFLICT (slug) DO NOTHING;

INSERT INTO vote.election_parties (election_id, party_id, seats, vote_share) VALUES
    ('11111111-1111-1111-1111-111111111111', 'aaaaaaaa-aaaa-aaaa-aaaa-aaaaaaaaaaaa', 162, 44.70),
    ('11111111-1111-1111-1111-111111111111', 'bbbbbbbb-bbbb-bbbb-bbbb-bbbbbbbbbbbb', 92, 31.40),
    ('11111111-1111-1111-1111-111111111111', 'cccccccc-cccc-cccc-cccc-cccccccccccc', 24, 8.60),
    ('11111111-1111-1111-1111-111111111111', 'dddddddd-dddd-dddd-dddd-dddddddddddd', 12, 6.10),
    ('11111111-1111-1111-1111-111111111111', 'eeeeeeee-eeee-eeee-eeee-eeeeeeeeeeee', 10, 4.20)
ON CONFLICT DO NOTHING;

INSERT INTO vote.regions (id, slug, name_en, name_bn) VALUES
    ('aaaaaaaa-1111-1111-1111-111111111111', 'dhaka', 'Dhaka', 'ঢাকা'),
    ('bbbbbbbb-2222-2222-2222-222222222222', 'chattogram', 'Chattogram', 'চট্টগ্রাম'),
    ('cccccccc-3333-3333-3333-333333333333', 'rajshahi', 'Rajshahi', 'রাজশাহী'),
    ('dddddddd-4444-4444-4444-444444444444', 'khulna', 'Khulna', 'খুলনা'),
    ('eeeeeeee-5555-5555-5555-555555555555', 'barishal', 'Barishal', 'বরিশাল'),
    ('ffffffff-6666-6666-6666-666666666666', 'sylhet', 'Sylhet', 'সিলেট'),
    ('12345678-7777-7777-7777-777777777777', 'rangpur', 'Rangpur', 'রংপুর'),
    ('87654321-8888-8888-8888-888888888888', 'mymensingh', 'Mymensingh', 'ময়মনসিংহ')
ON CONFLICT (slug) DO NOTHING;

INSERT INTO vote.region_results (election_id, region_id, turnout_percent, leading_party_id) VALUES
    ('11111111-1111-1111-1111-111111111111', 'aaaaaaaa-1111-1111-1111-111111111111', 76.20, 'aaaaaaaa-aaaa-aaaa-aaaa-aaaaaaaaaaaa'),
    ('11111111-1111-1111-1111-111111111111', 'bbbbbbbb-2222-2222-2222-222222222222', 72.50, 'bbbbbbbb-bbbb-bbbb-bbbb-bbbbbbbbbbbb'),
    ('11111111-1111-1111-1111-111111111111', 'cccccccc-3333-3333-3333-333333333333', 73.40, 'aaaaaaaa-aaaa-aaaa-aaaa-aaaaaaaaaaaa'),
    ('11111111-1111-1111-1111-111111111111', 'dddddddd-4444-4444-4444-444444444444', 71.10, 'aaaaaaaa-aaaa-aaaa-aaaa-aaaaaaaaaaaa'),
    ('11111111-1111-1111-1111-111111111111', 'eeeeeeee-5555-5555-5555-555555555555', 70.30, 'cccccccc-cccc-cccc-cccc-cccccccccccc'),
    ('11111111-1111-1111-1111-111111111111', 'ffffffff-6666-6666-6666-666666666666', 74.90, 'bbbbbbbb-bbbb-bbbb-bbbb-bbbbbbbbbbbb'),
    ('11111111-1111-1111-1111-111111111111', '12345678-7777-7777-7777-777777777777', 75.60, 'aaaaaaaa-aaaa-aaaa-aaaa-aaaaaaaaaaaa'),
    ('11111111-1111-1111-1111-111111111111', '87654321-8888-8888-8888-888888888888', 73.80, 'dddddddd-dddd-dddd-dddd-dddddddddddd')
ON CONFLICT DO NOTHING;

INSERT INTO vote.timeline_events (id, election_id, event_date, title_en, title_bn, note_en, note_bn) VALUES
    ('11111111-aaaa-1111-aaaa-111111111111', '11111111-1111-1111-1111-111111111111', '2025-11-05', 'Election announced', 'নির্বাচন ঘোষণা', 'Commission releases national schedule', 'কমিশন জাতীয় তফসিল প্রকাশ করে'),
    ('22222222-bbbb-2222-bbbb-222222222222', '11111111-1111-1111-1111-111111111111', '2025-11-22', 'Nomination window', 'মনোনয়ন সময়', 'Candidate filings open across constituencies', 'আসনভিত্তিক প্রার্থী দাখিল শুরু'),
    ('33333333-cccc-3333-cccc-333333333333', '11111111-1111-1111-1111-111111111111', '2026-01-20', 'Campaign silence', 'প্রচারণা বিরতি', '48-hour quiet period begins', '৪৮ ঘণ্টার নীরবতা শুরু'),
    ('44444444-dddd-4444-dddd-444444444444', '11111111-1111-1111-1111-111111111111', '2026-02-12', 'Polling day', 'ভোট গ্রহণ', 'Polling and counting across 300 seats', '৩০০ আসনে ভোট ও গণনা'),
    ('55555555-eeee-5555-eeee-555555555555', '11111111-1111-1111-1111-111111111111', '2026-02-13', 'Results certified', 'ফলাফল নিশ্চিত', 'Final results published', 'চূড়ান্ত ফল প্রকাশ')
ON CONFLICT DO NOTHING;

INSERT INTO vote.candidates (id, election_id, party_id, name_en, name_bn, constituency_en, constituency_bn, profile_en, profile_bn) VALUES
    ('11111111-aaaa-1111-aaaa-111111111112', '11111111-1111-1111-1111-111111111111', 'aaaaaaaa-aaaa-aaaa-aaaa-aaaaaaaaaaaa', 'Nusrat Islam', 'নুসরাত ইসলাম', 'Dhaka-7', 'ঢাকা-৭', 'Former educator focused on youth policy and civic innovation.', 'যুব নীতি ও নাগরিক উদ্ভাবনে কাজ করা সাবেক শিক্ষক।'),
    ('22222222-bbbb-2222-bbbb-222222222223', '11111111-1111-1111-1111-111111111111', 'bbbbbbbb-bbbb-bbbb-bbbb-bbbbbbbbbbbb', 'Rafid Khan', 'রাফিদ খান', 'Chattogram-4', 'চট্টগ্রাম-৪', 'Policy researcher specializing in economic resilience.', 'অর্থনৈতিক স্থিতিশীলতা নিয়ে গবেষণাকারী।'),
    ('33333333-cccc-3333-cccc-333333333334', '11111111-1111-1111-1111-111111111111', 'cccccccc-cccc-cccc-cccc-cccccccccccc', 'Mariya Sultana', 'মারিয়া সুলতানা', 'Barishal-2', 'বরিশাল-২', 'Climate action advocate and coastal resilience leader.', 'জলবায়ু কর্মী ও উপকূলীয় স্থিতি উদ্যোগের নেতা।'),
    ('44444444-dddd-4444-dddd-444444444445', '11111111-1111-1111-1111-111111111111', 'dddddddd-dddd-dddd-dddd-dddddddddddd', 'Fahim Rashid', 'ফাহিম রশিদ', 'Mymensingh-1', 'ময়মনসিংহ-১', 'Labor organizer focused on worker welfare reforms.', 'শ্রমিক কল্যাণ সংস্কারে নিবেদিত শ্রম সংগঠক।'),
    ('55555555-eeee-5555-eeee-555555555556', '11111111-1111-1111-1111-111111111111', 'aaaaaaaa-aaaa-aaaa-aaaa-aaaaaaaaaaaa', 'Lamia Chowdhury', 'লামিয়া চৌধুরী', 'Rajshahi-3', 'রাজশাহী-৩', 'Public health specialist and district volunteer leader.', 'জনস্বাস্থ্য বিশেষজ্ঞ ও স্বেচ্ছাসেবক নেতা।'),
    ('66666666-ffff-6666-ffff-666666666667', '11111111-1111-1111-1111-111111111111', 'eeeeeeee-eeee-eeee-eeee-eeeeeeeeeeee', 'Imran Jalal', 'ইমরান জালাল', 'Sylhet-5', 'সিলেট-৫', 'Independent community planner with local development focus.', 'স্থানীয় উন্নয়ন নিয়ে কাজ করা স্বাধীন পরিকল্পক।')
ON CONFLICT DO NOTHING;

INSERT INTO vote.resources (id, election_id, type_en, type_bn, title_en, title_bn, resource_date, url) VALUES
    ('11111111-aaaa-1111-aaaa-111111111118', '11111111-1111-1111-1111-111111111111', 'Report', 'রিপোর্ট', 'National results summary (PDF)', 'জাতীয় ফলাফলের সারসংক্ষেপ (PDF)', '2026-02-13', '#'),
    ('22222222-bbbb-2222-bbbb-222222222229', '11111111-1111-1111-1111-111111111111', 'Dataset', 'ডেটাসেট', 'Seat-by-seat dataset (CSV)', 'আসনভিত্তিক ডেটাসেট (CSV)', '2026-02-13', '#'),
    ('33333333-cccc-3333-cccc-333333333330', '11111111-1111-1111-1111-111111111111', 'Map', 'মানচিত্র', 'Division map pack (SVG)', 'বিভাগ মানচিত্র প্যাক (SVG)', '2026-02-12', '#'),
    ('44444444-dddd-4444-dddd-444444444441', '11111111-1111-1111-1111-111111111111', 'Gazette', 'গেজেট', 'Official gazette notice', 'সরকারি গেজেট বিজ্ঞপ্তি', '2026-02-13', '#')
ON CONFLICT DO NOTHING;

INSERT INTO vote.methodology_points (id, title_en, title_bn, body_en, body_bn, sort_order) VALUES
    ('11111111-aaaa-1111-aaaa-111111111119', 'Source-first ingestion', 'উৎস-ভিত্তিক ইনজেশন', 'Every update is tied to a published source and stored as a snapshot.', 'প্রতিটি আপডেট প্রকাশিত উৎসের সাথে যুক্ত এবং স্ন্যাপশট হিসেবে সংরক্ষিত।', 1),
    ('22222222-bbbb-2222-bbbb-222222222220', 'Dual-language verification', 'দ্বিভাষিক যাচাই', 'English and Bangla summaries are verified side-by-side.', 'ইংরেজি ও বাংলার সারাংশ পাশাপাশি যাচাই করা হয়।', 2),
    ('33333333-cccc-3333-cccc-333333333331', 'Transparent audit trail', 'স্বচ্ছ অডিট ট্রেইল', 'Raw payloads remain available for long-term review.', 'দীর্ঘমেয়াদি পর্যালোচনার জন্য কাঁচা তথ্য সংরক্ষিত থাকে।', 3),
    ('44444444-dddd-4444-dddd-444444444442', 'Archival permanence', 'আর্কাইভ স্থায়িত্ব', 'Election cycles remain accessible with stable URLs and downloads.', 'স্থিতিশীল URL ও ডাউনলোডসহ নির্বাচন চক্র সংরক্ষিত থাকে।', 4)
ON CONFLICT DO NOTHING;

INSERT INTO vote.sources (id, name_en, name_bn, detail_en, detail_bn, sort_order) VALUES
    ('11111111-aaaa-1111-aaaa-111111111120', 'Bangladesh Election Commission Releases', 'বাংলাদেশ নির্বাচন কমিশনের বিজ্ঞপ্তি', 'Official schedule, turnout, and seat confirmations.', 'সরকারি তফসিল, উপস্থিতি ও আসন নিশ্চিতকরণ।', 1),
    ('22222222-bbbb-2222-bbbb-222222222221', 'Accredited Monitoring Partners', 'স্বীকৃত পর্যবেক্ষক সংস্থা', 'Turnout spot checks and incident logs.', 'ভোটার উপস্থিতি যাচাই ও ঘটনাপঞ্জি।', 2),
    ('33333333-cccc-3333-cccc-333333333332', 'District Returning Officer Reports', 'জেলা রিটার্নিং অফিসারের রিপোর্ট', 'Constituency-level tabulations and verification notes.', 'আসনভিত্তিক ট্যাবুলেশন ও যাচাই নোট।', 3)
ON CONFLICT DO NOTHING;

INSERT INTO vote.highlights (id, title_en, title_bn, note_en, note_bn, value, value_type, sort_order) VALUES
    ('11111111-aaaa-1111-aaaa-111111111121', 'Youth participation', 'যুব অংশগ্রহণ', '18-29 turnout reached a new high', '১৮-২৯ বয়সীদের উপস্থিতি রেকর্ড', 61.00, 'percent', 1),
    ('22222222-bbbb-2222-bbbb-222222222222', 'Women candidates', 'নারী প্রার্থী', 'Highest share on record', 'এ পর্যন্ত সর্বোচ্চ অংশ', 18.00, 'percent', 2),
    ('33333333-cccc-3333-cccc-333333333333', 'Polling centers monitored', 'পর্যবেক্ষিত কেন্দ্র', 'Joint monitoring coverage', 'যৌথ পর্যবেক্ষণ কভারেজ', 42000, 'count', 3)
ON CONFLICT DO NOTHING;

INSERT INTO vote.meta (key, value_en, value_bn) VALUES
    ('data_status', 'Mock data for design preview', 'ডিজাইন প্রিভিউর জন্য নমুনা তথ্য'),
    ('last_updated', '2026-02-13', '2026-02-13'),
    ('districts_count', '64', '64')
ON CONFLICT (key) DO NOTHING;
