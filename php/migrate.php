<?php
/**
 * CISC 332 Conference — Database Migration & Seed
 * Run once at startup to create schema and seed data if empty.
 * Errors are logged but do NOT abort startup.
 */
require_once __DIR__ . '/db.php';

$db = getDb();

function safeExec(PDO $db, string $sql, string $label): bool {
    try {
        $db->exec($sql);
        return true;
    } catch (Throwable $e) {
        echo "[migrate] ERROR in {$label}: " . $e->getMessage() . "\n";
        return false;
    }
}

// ── Schema ────────────────────────────────────────────────────────────────────

safeExec($db, "
CREATE TABLE IF NOT EXISTS hotelroom (
    roomnumber   INT PRIMARY KEY,
    numberofbeds INT NOT NULL DEFAULT 2
);", "hotelroom");

safeExec($db, "
CREATE TABLE IF NOT EXISTS subcommittee (
    committeeid   INT PRIMARY KEY,
    committeename TEXT NOT NULL
);", "subcommittee");

safeExec($db, "
CREATE TABLE IF NOT EXISTS committeemember (
    memberid  INT PRIMARY KEY,
    firstname TEXT NOT NULL,
    lastname  TEXT NOT NULL
);", "committeemember");

safeExec($db, "
CREATE TABLE IF NOT EXISTS memberofcommittee (
    committeeid INT NOT NULL REFERENCES subcommittee(committeeid) ON DELETE CASCADE,
    memberid    INT NOT NULL REFERENCES committeemember(memberid)  ON DELETE CASCADE,
    PRIMARY KEY (committeeid, memberid)
);", "memberofcommittee");

safeExec($db, "
CREATE TABLE IF NOT EXISTS company (
    companyid   INT PRIMARY KEY,
    companyname TEXT NOT NULL
);", "company");

safeExec($db, "
CREATE TABLE IF NOT EXISTS session (
    sessionid    INT PRIMARY KEY,
    sessionname  TEXT NOT NULL,
    date         DATE NOT NULL,
    starttime    TIME NOT NULL,
    endtime      TIME NOT NULL,
    roomlocation TEXT NOT NULL
);", "session");

safeExec($db, "
CREATE TABLE IF NOT EXISTS attendee (
    attendeeid   SERIAL PRIMARY KEY,
    firstname    TEXT NOT NULL,
    lastname     TEXT NOT NULL,
    email        TEXT NOT NULL,
    attendeetype TEXT NOT NULL CHECK (attendeetype IN ('Student','Professional','Sponsor')),
    fee          NUMERIC(8,2) NOT NULL DEFAULT 0
);", "attendee");

safeExec($db, "
CREATE OR REPLACE FUNCTION set_attendee_fee() RETURNS TRIGGER AS \$\$
BEGIN
    NEW.fee := CASE NEW.attendeetype
        WHEN 'Student'      THEN 50.00
        WHEN 'Professional' THEN 100.00
        ELSE 0.00
    END;
    RETURN NEW;
END;
\$\$ LANGUAGE plpgsql;

DROP TRIGGER IF EXISTS trg_attendee_fee ON attendee;
CREATE TRIGGER trg_attendee_fee
    BEFORE INSERT OR UPDATE OF attendeetype ON attendee
    FOR EACH ROW EXECUTE FUNCTION set_attendee_fee();
", "fee_trigger");

safeExec($db, "
CREATE TABLE IF NOT EXISTS student (
    attendeeid        INT PRIMARY KEY REFERENCES attendee(attendeeid) ON DELETE CASCADE,
    roomnumberstaysin INT REFERENCES hotelroom(roomnumber) ON DELETE SET NULL
);", "student");

safeExec($db, "
CREATE TABLE IF NOT EXISTS professional (
    attendeeid INT PRIMARY KEY REFERENCES attendee(attendeeid) ON DELETE CASCADE
);", "professional");

safeExec($db, "
CREATE TABLE IF NOT EXISTS sponsor (
    attendeeid   INT PRIMARY KEY REFERENCES attendee(attendeeid) ON DELETE CASCADE,
    sponsorlevel TEXT NOT NULL CHECK (sponsorlevel IN ('Platinum','Gold','Silver','Bronze')),
    companyid    INT NOT NULL REFERENCES company(companyid)
);", "sponsor");

safeExec($db, "
CREATE TABLE IF NOT EXISTS jobad (
    jobid             SERIAL PRIMARY KEY,
    jobtitle          TEXT NOT NULL,
    location          TEXT NOT NULL,
    city              TEXT NOT NULL,
    province          TEXT NOT NULL,
    payrate           NUMERIC(10,2) NOT NULL,
    postedbycompanyid INT NOT NULL REFERENCES company(companyid) ON DELETE CASCADE
);", "jobad");

echo "[migrate] Schema ready.\n";

// ── Seed (only if attendee table is empty) ────────────────────────────────────

$count = (int) $db->query("SELECT COUNT(*) FROM attendee")->fetchColumn();
if ($count > 0) {
    echo "[migrate] Data already present ({$count} attendees). Skipping seed.\n";
    exit(0);
}

echo "[migrate] Seeding data...\n";

safeExec($db, "
INSERT INTO hotelroom (roomnumber, numberofbeds) VALUES
    (101,1),(102,1),(103,2),(104,2),(105,2),
    (106,3),(107,3),(108,2),(109,1),(110,2)
ON CONFLICT (roomnumber) DO NOTHING;
", "seed hotelroom");

safeExec($db, "
INSERT INTO subcommittee (committeeid, committeename) VALUES
    (1,'Program Committee'),
    (2,'Finance Committee'),
    (3,'Logistics & Venues'),
    (4,'Sponsorship Committee'),
    (5,'Student Affairs'),
    (6,'Technical Review Board')
ON CONFLICT (committeeid) DO NOTHING;
", "seed subcommittee");

safeExec($db, "
INSERT INTO committeemember (memberid, firstname, lastname) VALUES
    (1,'Alice',  'Chen'),
    (2,'Bob',    'Tremblay'),
    (3,'Carol',  'Okafor'),
    (4,'David',  'Singh'),
    (5,'Emily',  'Larsson'),
    (6,'Frank',  'Bouchard'),
    (7,'Grace',  'Kim'),
    (8,'Hassan', 'Al-Rashid'),
    (9,'Ingrid', 'Petrov'),
    (10,'James', 'Nguyen'),
    (11,'Karen', 'Williams'),
    (12,'Liam',  'Moreau')
ON CONFLICT (memberid) DO NOTHING;
", "seed committeemember");

safeExec($db, "
INSERT INTO memberofcommittee (committeeid, memberid) VALUES
    (1,1),(1,2),(1,3),
    (2,4),(2,5),(2,6),
    (3,7),(3,8),
    (4,9),(4,10),
    (5,11),(5,12),(5,1),
    (6,3),(6,5),(6,9)
ON CONFLICT DO NOTHING;
", "seed memberofcommittee");

safeExec($db, "
INSERT INTO company (companyid, companyname) VALUES
    (1,'Cognizant'),
    (2,'RBC Royal Bank'),
    (3,'Shopify'),
    (4,'Microsoft Canada'),
    (5,'Deloitte Digital'),
    (6,'OpenText'),
    (7,'BlackBerry')
ON CONFLICT (companyid) DO NOTHING;
", "seed company");

safeExec($db, "
INSERT INTO session (sessionid, sessionname, date, starttime, endtime, roomlocation) VALUES
    (1,'Opening Keynote',              '2026-05-12','09:00','10:00','Main Hall A'),
    (2,'Machine Learning in Practice', '2026-05-12','10:30','12:00','Room 204'),
    (3,'Cloud Architecture Patterns',  '2026-05-12','13:00','14:30','Room 107'),
    (4,'Cybersecurity Workshop',       '2026-05-12','14:45','16:15','Lab 301'),
    (5,'Networking & AI Panel',        '2026-05-13','09:00','10:30','Main Hall A'),
    (6,'Data Engineering Deep Dive',   '2026-05-13','10:45','12:15','Room 204'),
    (7,'Student Research Showcase',    '2026-05-13','13:30','15:00','Room 108'),
    (8,'Closing Ceremony',             '2026-05-13','15:30','16:30','Main Hall A')
ON CONFLICT (sessionid) DO NOTHING;
", "seed session");

safeExec($db, "
INSERT INTO attendee (firstname, lastname, email, attendeetype) VALUES
    ('Jordan','MacLeod',   'jordan.macleod@queensu.ca',  'Student'),
    ('Priya', 'Sharma',    'priya.sharma@queensu.ca',    'Student'),
    ('Tyler', 'Fontaine',  'tyler.fontaine@queensu.ca',  'Student'),
    ('Sofia', 'Andrade',   'sofia.andrade@queensu.ca',   'Student'),
    ('Marcus','Diallo',    'marcus.diallo@queensu.ca',   'Student'),
    ('Rachel','Park',      'rachel.park@rbc.com',        'Professional'),
    ('Owen',  'Bergman',   'o.bergman@deloitte.com',     'Professional'),
    ('Nina',  'Takahashi', 'nina.t@microsoft.com',       'Professional'),
    ('Ethan', 'Murphy',    'emurphy@opentext.com',       'Professional'),
    ('Wei',   'Zhang',     'wei.zhang@blackberry.com',   'Professional'),
    ('Aisha', 'Nkosi',     'a.nkosi@shopify.com',        'Sponsor'),
    ('Carl',  'Jensen',    'cjensen@cognizant.com',      'Sponsor'),
    ('Diana', 'Okonkwo',   'd.okonkwo@microsoft.com',    'Sponsor');
", "seed attendee");

safeExec($db, "
INSERT INTO student (attendeeid, roomnumberstaysin)
SELECT a.attendeeid,
    CASE a.email
        WHEN 'jordan.macleod@queensu.ca' THEN 101
        WHEN 'priya.sharma@queensu.ca'   THEN 102
        WHEN 'tyler.fontaine@queensu.ca' THEN 103
        ELSE NULL
    END
FROM attendee a WHERE a.attendeetype = 'Student'
ON CONFLICT (attendeeid) DO NOTHING;
", "seed student");

safeExec($db, "
INSERT INTO professional (attendeeid)
SELECT attendeeid FROM attendee WHERE attendeetype = 'Professional'
ON CONFLICT (attendeeid) DO NOTHING;
", "seed professional");

safeExec($db, "
INSERT INTO sponsor (attendeeid, sponsorlevel, companyid)
SELECT a.attendeeid,
    CASE a.email
        WHEN 'cjensen@cognizant.com' THEN 'Platinum'
        WHEN 'a.nkosi@shopify.com'   THEN 'Gold'
        ELSE 'Silver'
    END,
    CASE a.email
        WHEN 'cjensen@cognizant.com' THEN 1
        WHEN 'a.nkosi@shopify.com'   THEN 3
        ELSE 4
    END
FROM attendee a WHERE a.attendeetype = 'Sponsor'
ON CONFLICT (attendeeid) DO NOTHING;
", "seed sponsor");

safeExec($db, "
INSERT INTO jobad (jobtitle, location, city, province, payrate, postedbycompanyid) VALUES
    ('Software Engineer',         'On-site','Toronto', 'ON',95000, 4),
    ('Data Scientist',            'Hybrid', 'Ottawa',  'ON',102000,5),
    ('Cloud Infrastructure Lead', 'Remote', 'Montreal','QC',118000,2),
    ('Machine Learning Engineer', 'Hybrid', 'Waterloo','ON',110000,1),
    ('Full-Stack Developer',      'On-site','Toronto', 'ON',88000, 3),
    ('Security Analyst',          'On-site','Kanata',  'ON',92000, 7),
    ('DevOps Engineer',           'Remote', 'Calgary', 'AB',98000, 6);
", "seed jobad");

$final = (int) $db->query("SELECT COUNT(*) FROM attendee")->fetchColumn();
echo "[migrate] Seed complete — {$final} attendees inserted.\n";
