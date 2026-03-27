<?php
require_once __DIR__ . '/db.php';

function logStep(string $level, string $stepId, bool $critical, string $message): void {
    $criticalText = $critical ? "true" : "false";
    echo "[migrate] {$level} step_id={$stepId} critical={$criticalText} message=\"{$message}\"\n";
}

function printSummary(int $steps, int $warnings, int $criticalFailures): void {
    echo "[migrate] SUMMARY steps={$steps} warnings={$warnings} critical_failures={$criticalFailures} warning_count={$warnings} fatal_count={$criticalFailures}\n";
}

function runSqlStep(PDO $db, array $step, int &$steps, int &$warnings, int &$criticalFailures): bool {
    $steps++;
    try {
        $db->exec($step['sql']);
        logStep('INFO', $step['step_id'], $step['critical'], 'ok');
        return true;
    } catch (Throwable $e) {
        $reason = str_replace('"', "'", $e->getMessage());
        if ($step['critical']) {
            $criticalFailures++;
            logStep('FATAL', $step['step_id'], true, $reason . ' exit_code=1');
            return false;
        }
        $warnings++;
        logStep('WARN', $step['step_id'], false, $reason);
        return true;
    }
}

$steps = 0;
$warnings = 0;
$criticalFailures = 0;

try {
    $db = getDb();
} catch (Throwable $e) {
    $criticalFailures++;
    logStep('FATAL', 'connect.database', true, str_replace('"', "'", $e->getMessage()) . ' exit_code=1');
    printSummary($steps, $warnings, $criticalFailures);
    exit(1);
}

$schemaSteps = [
    [
        'step_id' => 'schema.create_sponsor_level_enum',
        'critical' => true,
        'sql' => "
DO \$\$
BEGIN
    IF NOT EXISTS (SELECT 1 FROM pg_type WHERE typname = 'sponsor_level') THEN
        CREATE TYPE sponsor_level AS ENUM ('Platinum', 'Gold', 'Silver', 'Bronze');
    END IF;
END
\$\$;
",
    ],
    [
        'step_id' => 'schema.hotelroom',
        'critical' => true,
        'sql' => "
CREATE TABLE IF NOT EXISTS hotelroom (
    roomnumber   INT PRIMARY KEY,
    numberofbeds INT NOT NULL DEFAULT 2
);",
    ],
    [
        'step_id' => 'schema.subcommittee',
        'critical' => true,
        'sql' => "
CREATE TABLE IF NOT EXISTS subcommittee (
    committeeid   INT PRIMARY KEY,
    committeename TEXT NOT NULL
);",
    ],
    [
        'step_id' => 'schema.committeemember',
        'critical' => true,
        'sql' => "
CREATE TABLE IF NOT EXISTS committeemember (
    memberid  INT PRIMARY KEY,
    firstname TEXT NOT NULL,
    lastname  TEXT NOT NULL
);",
    ],
    [
        'step_id' => 'schema.memberofcommittee',
        'critical' => true,
        'sql' => "
CREATE TABLE IF NOT EXISTS memberofcommittee (
    committeeid INT NOT NULL REFERENCES subcommittee(committeeid) ON DELETE CASCADE,
    memberid    INT NOT NULL REFERENCES committeemember(memberid) ON DELETE CASCADE,
    PRIMARY KEY (committeeid, memberid)
);",
    ],
    [
        'step_id' => 'schema.company',
        'critical' => true,
        'sql' => "
CREATE TABLE IF NOT EXISTS company (
    companyid   INT PRIMARY KEY,
    companyname TEXT NOT NULL
);",
    ],
    [
        'step_id' => 'schema.session',
        'critical' => true,
        'sql' => "
CREATE TABLE IF NOT EXISTS session (
    sessionid    INT PRIMARY KEY,
    sessionname  TEXT NOT NULL,
    date         DATE NOT NULL,
    starttime    TIME NOT NULL,
    endtime      TIME NOT NULL,
    roomlocation TEXT NOT NULL
);",
    ],
    [
        'step_id' => 'schema.attendee',
        'critical' => true,
        'sql' => "
CREATE TABLE IF NOT EXISTS attendee (
    attendeeid   SERIAL PRIMARY KEY,
    firstname    TEXT NOT NULL,
    lastname     TEXT NOT NULL,
    email        TEXT NOT NULL,
    attendeetype TEXT NOT NULL CHECK (attendeetype IN ('Student','Professional','Sponsor')),
    fee          NUMERIC(8,2) NOT NULL DEFAULT 0
);",
    ],
    [
        'step_id' => 'schema.fee_trigger',
        'critical' => true,
        'sql' => "
CREATE OR REPLACE FUNCTION set_attendee_fee() RETURNS TRIGGER AS \$\$
BEGIN
    NEW.fee := CASE NEW.attendeetype
        WHEN 'Student' THEN 50.00
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
",
    ],
    [
        'step_id' => 'schema.student',
        'critical' => true,
        'sql' => "
CREATE TABLE IF NOT EXISTS student (
    attendeeid        INT PRIMARY KEY REFERENCES attendee(attendeeid) ON DELETE CASCADE,
    roomnumberstaysin INT REFERENCES hotelroom(roomnumber) ON DELETE SET NULL
);",
    ],
    [
        'step_id' => 'schema.professional',
        'critical' => true,
        'sql' => "
CREATE TABLE IF NOT EXISTS professional (
    attendeeid INT PRIMARY KEY REFERENCES attendee(attendeeid) ON DELETE CASCADE
);",
    ],
    [
        'step_id' => 'schema.sponsor',
        'critical' => true,
        'sql' => "
CREATE TABLE IF NOT EXISTS sponsor (
    attendeeid   INT PRIMARY KEY REFERENCES attendee(attendeeid) ON DELETE CASCADE,
    sponsorlevel sponsor_level NOT NULL,
    companyid    INT NOT NULL REFERENCES company(companyid)
);",
    ],
    [
        'step_id' => 'schema.sponsorlevel_to_enum',
        'critical' => true,
        'sql' => "
ALTER TABLE sponsor DROP CONSTRAINT IF EXISTS sponsor_sponsorlevel_check;
ALTER TABLE sponsor
    ALTER COLUMN sponsorlevel TYPE sponsor_level
    USING sponsorlevel::sponsor_level;
ALTER TABLE sponsor
    ALTER COLUMN sponsorlevel SET NOT NULL;
",
    ],
    [
        'step_id' => 'schema.jobad',
        'critical' => true,
        'sql' => "
CREATE TABLE IF NOT EXISTS jobad (
    jobid             SERIAL PRIMARY KEY,
    jobtitle          TEXT NOT NULL,
    location          TEXT NOT NULL,
    city              TEXT NOT NULL,
    province          TEXT NOT NULL,
    payrate           NUMERIC(10,2) NOT NULL,
    postedbycompanyid INT NOT NULL REFERENCES company(companyid) ON DELETE CASCADE
);",
    ],
    [
        'step_id' => 'schema.noncritical_legacy_cleanup',
        'critical' => false,
        'sql' => "DROP VIEW conference_seed_legacy;",
    ],
];

foreach ($schemaSteps as $step) {
    if (!runSqlStep($db, $step, $steps, $warnings, $criticalFailures)) {
        printSummary($steps, $warnings, $criticalFailures);
        exit(1);
    }
}

try {
    $steps++;
    $db->exec("BEGIN");
    logStep('INFO', 'seed.begin', true, 'BEGIN');

    $steps++;
    $db->exec("
TRUNCATE TABLE
    sponsor,
    professional,
    student,
    attendee,
    jobad,
    session,
    company,
    memberofcommittee,
    committeemember,
    subcommittee,
    hotelroom
RESTART IDENTITY CASCADE;
");
    logStep('INFO', 'seed.reset_all_tables', true, 'TRUNCATE TABLE reset complete');

    $seedSteps = [
        [
            'step_id' => 'seed.hotelroom',
            'critical' => true,
            'sql' => "
INSERT INTO hotelroom (roomnumber, numberofbeds) VALUES
    (101,1),(102,1),(103,2),(104,2),(105,2),
    (106,3),(107,3),(108,2),(109,1),(110,2),
    (111,1),(112,2),(113,2),(114,3),(115,1),
    (116,2),(117,2),(118,3),(119,1),(120,2);
",
        ],
        [
            'step_id' => 'seed.subcommittee',
            'critical' => true,
            'sql' => "
INSERT INTO subcommittee (committeeid, committeename) VALUES
    (1,'Program Committee'),
    (2,'Finance Committee'),
    (3,'Logistics & Venues'),
    (4,'Sponsorship Committee'),
    (5,'Student Affairs'),
    (6,'Technical Review Board');
",
        ],
        [
            'step_id' => 'seed.committeemember',
            'critical' => true,
            'sql' => "
INSERT INTO committeemember (memberid, firstname, lastname) VALUES
    (1,'Alice','Chen'),
    (2,'Bob','Tremblay'),
    (3,'Carol','Okafor'),
    (4,'David','Singh'),
    (5,'Emily','Larsson'),
    (6,'Frank','Bouchard'),
    (7,'Grace','Kim'),
    (8,'Hassan','Al-Rashid'),
    (9,'Ingrid','Petrov'),
    (10,'James','Nguyen'),
    (11,'Karen','Williams'),
    (12,'Liam','Moreau'),
    (13,'Mia','Thompson'),
    (14,'Noah','Patel'),
    (15,'Olivia','Hernandez'),
    (16,'Patrick','Osei'),
    (17,'Quinn','Leblanc'),
    (18,'Ravi','Subramanian'),
    (19,'Sara','Kowalski'),
    (20,'Tariq','Hassan');
",
        ],
        [
            'step_id' => 'seed.memberofcommittee',
            'critical' => true,
            'sql' => "
INSERT INTO memberofcommittee (committeeid, memberid) VALUES
    (1,1),(1,2),(1,3),(1,13),(1,14),
    (2,4),(2,5),(2,6),(2,15),(2,16),
    (3,7),(3,8),(3,17),(3,18),
    (4,9),(4,10),(4,19),(4,20),
    (5,11),(5,12),(5,1),(5,13),(5,14),
    (6,3),(6,5),(6,9),(6,15),(6,16);
",
        ],
        [
            'step_id' => 'seed.company',
            'critical' => true,
            'sql' => "
INSERT INTO company (companyid, companyname) VALUES
    (1,'Cognizant'),
    (2,'RBC Royal Bank'),
    (3,'Shopify'),
    (4,'Microsoft Canada'),
    (5,'Deloitte Digital'),
    (6,'OpenText'),
    (7,'BlackBerry');
",
        ],
        [
            'step_id' => 'seed.session',
            'critical' => true,
            'sql' => "
INSERT INTO session (sessionid, sessionname, date, starttime, endtime, roomlocation) VALUES
    (1,'Opening Keynote','2026-05-12','09:00','10:00','Main Hall A'),
    (2,'Machine Learning in Practice','2026-05-12','10:30','12:00','Room 204'),
    (3,'Cloud Architecture Patterns','2026-05-12','13:00','14:30','Room 107'),
    (4,'Cybersecurity Workshop','2026-05-12','14:45','16:15','Lab 301'),
    (5,'Networking & AI Panel','2026-05-13','09:00','10:30','Main Hall A'),
    (6,'Data Engineering Deep Dive','2026-05-13','10:45','12:15','Room 204'),
    (7,'Student Research Showcase','2026-05-13','13:30','15:00','Room 108'),
    (8,'Closing Ceremony','2026-05-13','15:30','16:30','Main Hall A'),
    (9,'Quantum Computing Intro','2026-05-14','09:00','10:30','Main Hall A'),
    (10,'Blockchain Applications','2026-05-14','10:45','12:15','Room 204'),
    (11,'Open Source Contributions','2026-05-14','13:00','14:30','Room 107'),
    (12,'Career Networking Lunch','2026-05-14','14:45','16:00','Atrium'),
    (13,'Workshop: System Design','2026-05-15','09:00','11:00','Lab 301'),
    (14,'Panel: Future of Work & AI','2026-05-15','11:15','12:45','Main Hall A');
",
        ],
        [
            'step_id' => 'seed.attendee',
            'critical' => true,
            'sql' => "
INSERT INTO attendee (firstname, lastname, email, attendeetype) VALUES
    ('Jordan','MacLeod','jordan.macleod@queensu.ca','Student'),
    ('Priya','Sharma','priya.sharma@queensu.ca','Student'),
    ('Tyler','Fontaine','tyler.fontaine@queensu.ca','Student'),
    ('Sofia','Andrade','sofia.andrade@queensu.ca','Student'),
    ('Marcus','Diallo','marcus.diallo@queensu.ca','Student'),
    ('Rachel','Park','rachel.park@rbc.com','Professional'),
    ('Owen','Bergman','o.bergman@deloitte.com','Professional'),
    ('Nina','Takahashi','nina.t@microsoft.com','Professional'),
    ('Ethan','Murphy','emurphy@opentext.com','Professional'),
    ('Wei','Zhang','wei.zhang@blackberry.com','Professional'),
    ('Aisha','Nkosi','a.nkosi@shopify.com','Sponsor'),
    ('Carl','Jensen','cjensen@cognizant.com','Sponsor'),
    ('Diana','Okonkwo','d.okonkwo@microsoft.com','Sponsor'),
    ('Lena','Kowalski','lena.k@queensu.ca','Student'),
    ('Derek','Osei','derek.osei@queensu.ca','Student'),
    ('Fatima','Al-Zahra','fatima.z@queensu.ca','Student'),
    ('Carlos','Reyes','carlos.r@queensu.ca','Student'),
    ('Hannah','Brennan','h.brennan@queensu.ca','Student'),
    ('Sam','Nakamura','s.nakamura@queensu.ca','Student'),
    ('Zoe','Clifford','z.clifford@queensu.ca','Student'),
    ('Alex','Dubois','a.dubois@queensu.ca','Student'),
    ('Claire','Fontaine','c.fontaine@rbc.com','Professional'),
    ('Marcus','Webb','m.webb@shopify.com','Professional'),
    ('Sunita','Rao','s.rao@deloitte.com','Professional'),
    ('Patrick','O Brien','p.obrien@microsoft.com','Professional'),
    ('Elena','Marchetti','e.marchetti@opentext.com','Professional'),
    ('James','Thornton','j.thornton@blackberry.com','Professional'),
    ('Amara','Diallo','a.diallo@cognizant.com','Sponsor'),
    ('Victor','Rousseau','v.rousseau@rbc.com','Sponsor'),
    ('Linda','Chen','l.chen@shopify.com','Sponsor');
",
        ],
        [
            'step_id' => 'seed.student',
            'critical' => true,
            'sql' => "
INSERT INTO student (attendeeid, roomnumberstaysin)
SELECT a.attendeeid,
    CASE a.email
        WHEN 'jordan.macleod@queensu.ca' THEN 101
        WHEN 'priya.sharma@queensu.ca' THEN 102
        WHEN 'tyler.fontaine@queensu.ca' THEN 103
        WHEN 'lena.k@queensu.ca' THEN 104
        WHEN 'derek.osei@queensu.ca' THEN 105
        WHEN 'fatima.z@queensu.ca' THEN 106
        WHEN 'carlos.r@queensu.ca' THEN 107
        WHEN 'h.brennan@queensu.ca' THEN 108
        WHEN 's.nakamura@queensu.ca' THEN 111
        WHEN 'z.clifford@queensu.ca' THEN 112
        ELSE NULL
    END
FROM attendee a
WHERE a.attendeetype = 'Student';
",
        ],
        [
            'step_id' => 'seed.professional',
            'critical' => true,
            'sql' => "
INSERT INTO professional (attendeeid)
SELECT attendeeid FROM attendee WHERE attendeetype = 'Professional';
",
        ],
        [
            'step_id' => 'seed.sponsor',
            'critical' => true,
            'sql' => "
INSERT INTO sponsor (attendeeid, sponsorlevel, companyid)
SELECT a.attendeeid,
    CASE a.email
        WHEN 'cjensen@cognizant.com' THEN 'Platinum'::sponsor_level
        WHEN 'a.nkosi@shopify.com' THEN 'Gold'::sponsor_level
        WHEN 'v.rousseau@rbc.com' THEN 'Platinum'::sponsor_level
        WHEN 'l.chen@shopify.com' THEN 'Silver'::sponsor_level
        WHEN 'a.diallo@cognizant.com' THEN 'Bronze'::sponsor_level
        ELSE 'Silver'::sponsor_level
    END,
    CASE a.email
        WHEN 'cjensen@cognizant.com' THEN 1
        WHEN 'a.nkosi@shopify.com' THEN 3
        WHEN 'v.rousseau@rbc.com' THEN 2
        WHEN 'l.chen@shopify.com' THEN 3
        WHEN 'a.diallo@cognizant.com' THEN 1
        ELSE 4
    END
FROM attendee a
WHERE a.attendeetype = 'Sponsor';
",
        ],
        [
            'step_id' => 'seed.jobad',
            'critical' => true,
            'sql' => "
INSERT INTO jobad (jobtitle, location, city, province, payrate, postedbycompanyid) VALUES
    ('Software Engineer','On-site','Toronto','ON',95000,4),
    ('Data Scientist','Hybrid','Ottawa','ON',102000,5),
    ('Cloud Infrastructure Lead','Remote','Montreal','QC',118000,2),
    ('Machine Learning Engineer','Hybrid','Waterloo','ON',110000,1),
    ('Full-Stack Developer','On-site','Toronto','ON',88000,3),
    ('Security Analyst','On-site','Kanata','ON',92000,7),
    ('DevOps Engineer','Remote','Calgary','AB',98000,6),
    ('Product Manager','Hybrid','Toronto','ON',115000,3),
    ('Backend Engineer','Remote','Vancouver','BC',105000,1),
    ('iOS Developer','On-site','Waterloo','ON',90000,7),
    ('Data Analyst II','Hybrid','Toronto','ON',90000,2),
    ('QA Engineer','Remote','Ottawa','ON',78000,6),
    ('Solutions Architect','Hybrid','Calgary','AB',130000,4),
    ('Frontend Developer','On-site','Montreal','QC',86000,5),
    ('Cybersecurity Specialist','Remote','Toronto','ON',108000,7);
",
        ],
    ];

    foreach ($seedSteps as $step) {
        if (!runSqlStep($db, $step, $steps, $warnings, $criticalFailures)) {
            $steps++;
            $db->exec("ROLLBACK");
            logStep('INFO', 'seed.rollback', true, 'ROLLBACK');
            printSummary($steps, $warnings, $criticalFailures);
            exit(1);
        }
    }

    $steps++;
    $db->exec("COMMIT");
    logStep('INFO', 'seed.commit', true, 'COMMIT');
} catch (Throwable $e) {
    $criticalFailures++;
    $steps++;
    try {
        $db->exec("ROLLBACK");
        logStep('INFO', 'seed.rollback', true, 'ROLLBACK');
    } catch (Throwable $rollbackError) {
        $warnings++;
        logStep('WARN', 'seed.rollback', false, str_replace('"', "'", $rollbackError->getMessage()));
    }
    logStep('FATAL', 'seed.transaction', true, str_replace('"', "'", $e->getMessage()) . ' exit_code=1');
    printSummary($steps, $warnings, $criticalFailures);
    exit(1);
}

if ($warnings === 0) {
    logStep('WARN', 'summary.no_noncritical_failures', false, 'no WARN events from non-critical steps');
}

$final = (int) $db->query("SELECT COUNT(*) FROM attendee")->fetchColumn();
logStep('INFO', 'seed.attendee_count', true, "attendees={$final}");
printSummary($steps, $warnings, $criticalFailures);
exit(0);
