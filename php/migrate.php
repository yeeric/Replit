<?php
/**
 * CISC 332 Conference — Database Migration & Seed
 * Run once at startup to create schema and seed data if empty.
 */
require_once __DIR__ . '/db.php';

$db = getDb();

// ── Schema ────────────────────────────────────────────────────────────────────

$db->exec("
CREATE TABLE IF NOT EXISTS hotelroom (
    roomnumber   SERIAL PRIMARY KEY,
    numberofbeds INT NOT NULL DEFAULT 2
);

CREATE TABLE IF NOT EXISTS subcommittee (
    committeeid   SERIAL PRIMARY KEY,
    committeename TEXT NOT NULL
);

CREATE TABLE IF NOT EXISTS committeemember (
    memberid  SERIAL PRIMARY KEY,
    firstname TEXT NOT NULL,
    lastname  TEXT NOT NULL
);

CREATE TABLE IF NOT EXISTS memberofcommittee (
    committeeid INT NOT NULL REFERENCES subcommittee(committeeid) ON DELETE CASCADE,
    memberid    INT NOT NULL REFERENCES committeemember(memberid)  ON DELETE CASCADE,
    PRIMARY KEY (committeeid, memberid)
);

CREATE TABLE IF NOT EXISTS company (
    companyid   SERIAL PRIMARY KEY,
    companyname TEXT NOT NULL
);

CREATE TABLE IF NOT EXISTS session (
    sessionid    SERIAL PRIMARY KEY,
    sessionname  TEXT NOT NULL,
    date         DATE NOT NULL,
    starttime    TIME NOT NULL,
    endtime      TIME NOT NULL,
    roomlocation TEXT NOT NULL
);

CREATE TABLE IF NOT EXISTS attendee (
    attendeeid   SERIAL PRIMARY KEY,
    firstname    TEXT NOT NULL,
    lastname     TEXT NOT NULL,
    email        TEXT NOT NULL,
    attendeetype TEXT NOT NULL CHECK (attendeetype IN ('Student','Professional','Sponsor')),
    fee          NUMERIC(8,2) NOT NULL DEFAULT 0
);

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

CREATE TABLE IF NOT EXISTS student (
    attendeeid        INT PRIMARY KEY REFERENCES attendee(attendeeid) ON DELETE CASCADE,
    roomnumberstaysin INT REFERENCES hotelroom(roomnumber) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS professional (
    attendeeid INT PRIMARY KEY REFERENCES attendee(attendeeid) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS sponsor (
    attendeeid   INT PRIMARY KEY REFERENCES attendee(attendeeid) ON DELETE CASCADE,
    sponsorlevel TEXT NOT NULL CHECK (sponsorlevel IN ('Platinum','Gold','Silver','Bronze')),
    companyid    INT NOT NULL REFERENCES company(companyid)
);

CREATE TABLE IF NOT EXISTS jobad (
    jobid             SERIAL PRIMARY KEY,
    jobtitle          TEXT NOT NULL,
    location          TEXT NOT NULL,
    city              TEXT NOT NULL,
    province          TEXT NOT NULL,
    payrate           NUMERIC(10,2) NOT NULL,
    postedbycompanyid INT NOT NULL REFERENCES company(companyid) ON DELETE CASCADE
);
");

echo "[migrate] Schema ready.\n";

// ── Seed (only if empty) ──────────────────────────────────────────────────────

$count = (int) $db->query("SELECT COUNT(*) FROM attendee")->fetchColumn();
if ($count > 0) {
    echo "[migrate] Data already present ({$count} attendees). Skipping seed.\n";
    exit(0);
}

echo "[migrate] Seeding data...\n";

// Hotel Rooms
$db->exec("
INSERT INTO hotelroom (numberofbeds) VALUES
    (1),(1),(2),(2),(2),(3),(3),(2),(1),(2);
");

// Sub-committees
$db->exec("
INSERT INTO subcommittee (committeename) VALUES
    ('Program Committee'),
    ('Finance Committee'),
    ('Logistics & Venues'),
    ('Sponsorship Committee'),
    ('Student Affairs'),
    ('Technical Review Board');
");

// Committee Members
$db->exec("
INSERT INTO committeemember (firstname, lastname) VALUES
    ('Alice',  'Chen'),
    ('Bob',    'Tremblay'),
    ('Carol',  'Okafor'),
    ('David',  'Singh'),
    ('Emily',  'Larsson'),
    ('Frank',  'Bouchard'),
    ('Grace',  'Kim'),
    ('Hassan', 'Al-Rashid'),
    ('Ingrid', 'Petrov'),
    ('James',  'Nguyen'),
    ('Karen',  'Williams'),
    ('Liam',   'Moreau');
");

// Member ↔ Committee assignments
$db->exec("
INSERT INTO memberofcommittee (committeeid, memberid) VALUES
    (1,1),(1,2),(1,3),
    (2,4),(2,5),(2,6),
    (3,7),(3,8),
    (4,9),(4,10),
    (5,11),(5,12),(5,1),
    (6,3),(6,5),(6,9);
");

// Companies
$db->exec("
INSERT INTO company (companyname) VALUES
    ('Cognizant'),
    ('RBC Royal Bank'),
    ('Shopify'),
    ('Microsoft Canada'),
    ('Deloitte Digital'),
    ('OpenText'),
    ('BlackBerry');
");

// Sessions
$db->exec("
INSERT INTO session (sessionname, date, starttime, endtime, roomlocation) VALUES
    ('Opening Keynote',              '2026-05-12', '09:00', '10:00', 'Main Hall A'),
    ('Machine Learning in Practice', '2026-05-12', '10:30', '12:00', 'Room 204'),
    ('Cloud Architecture Patterns',  '2026-05-12', '13:00', '14:30', 'Room 107'),
    ('Cybersecurity Workshop',       '2026-05-12', '14:45', '16:15', 'Lab 301'),
    ('Networking & AI Panel',        '2026-05-13', '09:00', '10:30', 'Main Hall A'),
    ('Data Engineering Deep Dive',   '2026-05-13', '10:45', '12:15', 'Room 204'),
    ('Student Research Showcase',    '2026-05-13', '13:30', '15:00', 'Room 108'),
    ('Closing Ceremony',             '2026-05-13', '15:30', '16:30', 'Main Hall A');
");

// Attendees (fee auto-set by trigger)
$db->exec("
INSERT INTO attendee (firstname, lastname, email, attendeetype) VALUES
    ('Jordan', 'MacLeod',    'jordan.macleod@queensu.ca',   'Student'),
    ('Priya',  'Sharma',     'priya.sharma@queensu.ca',     'Student'),
    ('Tyler',  'Fontaine',   'tyler.fontaine@queensu.ca',   'Student'),
    ('Sofia',  'Andrade',    'sofia.andrade@queensu.ca',    'Student'),
    ('Marcus', 'Diallo',     'marcus.diallo@queensu.ca',    'Student'),
    ('Rachel', 'Park',       'rachel.park@rbc.com',         'Professional'),
    ('Owen',   'Bergman',    'o.bergman@deloitte.com',      'Professional'),
    ('Nina',   'Takahashi',  'nina.t@microsoft.com',        'Professional'),
    ('Ethan',  'Murphy',     'emurphy@opentext.com',        'Professional'),
    ('Wei',    'Zhang',      'wei.zhang@blackberry.com',    'Professional'),
    ('Aisha',  'Nkosi',      'a.nkosi@shopify.com',         'Sponsor'),
    ('Carl',   'Jensen',     'cjensen@cognizant.com',       'Sponsor'),
    ('Diana',  'Okonkwo',    'd.okonkwo@microsoft.com',     'Sponsor');
");

// Student sub-type
$db->exec("
INSERT INTO student (attendeeid, roomnumberstaysin)
SELECT a.attendeeid,
    CASE a.lastname
        WHEN 'MacLeod'  THEN 1
        WHEN 'Sharma'   THEN 2
        WHEN 'Fontaine' THEN 3
        ELSE NULL
    END
FROM attendee a WHERE a.attendeetype = 'Student';
");

// Professional sub-type
$db->exec("
INSERT INTO professional (attendeeid)
SELECT attendeeid FROM attendee WHERE attendeetype = 'Professional';
");

// Sponsor sub-type (companyid references companies inserted above: 1=Cognizant, 3=Shopify, 4=Microsoft)
$db->exec("
INSERT INTO sponsor (attendeeid, sponsorlevel, companyid)
SELECT a.attendeeid,
    CASE a.lastname
        WHEN 'Jensen'   THEN 'Platinum'
        WHEN 'Nkosi'    THEN 'Gold'
        ELSE            'Silver'
    END,
    CASE a.lastname
        WHEN 'Jensen'   THEN 1
        WHEN 'Nkosi'    THEN 3
        ELSE            4
    END
FROM attendee a WHERE a.attendeetype = 'Sponsor';
");

// Job Ads
$db->exec("
INSERT INTO jobad (jobtitle, location, city, province, payrate, postedbycompanyid) VALUES
    ('Software Engineer',         'On-site', 'Toronto',  'ON', 95000,  4),
    ('Data Scientist',            'Hybrid',  'Ottawa',   'ON', 102000, 5),
    ('Cloud Infrastructure Lead', 'Remote',  'Montreal', 'QC', 118000, 2),
    ('Machine Learning Engineer', 'Hybrid',  'Waterloo', 'ON', 110000, 1),
    ('Full-Stack Developer',      'On-site', 'Toronto',  'ON', 88000,  3),
    ('Security Analyst',          'On-site', 'Kanata',   'ON', 92000,  7),
    ('DevOps Engineer',           'Remote',  'Calgary',  'AB', 98000,  6);
");

echo "[migrate] Seed complete.\n";
