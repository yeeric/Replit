<?php
/**
 * Conference Management API — PHP Backend
 * Uses PDO for all database access (compatible with any DBMS).
 */

require_once __DIR__ . '/db.php';

// ── Helpers ──────────────────────────────────────────────────────────────────

function jsonResponse(mixed $data, int $status = 200): never {
    http_response_code($status);
    header('Content-Type: application/json');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function errorResponse(string $message, int $status = 400): never {
    jsonResponse(['message' => $message], $status);
}

function getBody(): array {
    $raw = file_get_contents('php://input');
    return $raw ? (json_decode($raw, true) ?? []) : [];
}

// Parse path segments (strip /api prefix)
$method  = $_SERVER['REQUEST_METHOD'];
$uri     = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$uri     = preg_replace('#^/api#', '', $uri);  // strip /api prefix
$parts   = array_values(array_filter(explode('/', $uri)));

// ── Router ───────────────────────────────────────────────────────────────────

try {
    $db = getDb();

    // GET /committees
    if ($method === 'GET' && $parts === ['committees']) {
        $stmt = $db->query("SELECT committeeid, committeename, chairmemberid FROM subcommittee ORDER BY committeeid");
        jsonResponse($stmt->fetchAll());
    }

    // GET /committees/:id/members
    if ($method === 'GET' && count($parts) === 3 && $parts[0] === 'committees' && $parts[2] === 'members') {
        $id   = (int) $parts[1];
        $stmt = $db->prepare("
            SELECT cm.memberid, cm.firstname, cm.lastname
            FROM memberofcommittee moc
            INNER JOIN committeemember cm ON moc.memberid = cm.memberid
            WHERE moc.committeeid = ?
            ORDER BY cm.lastname
        ");
        $stmt->execute([$id]);
        jsonResponse($stmt->fetchAll());
    }

    // GET /hotel-rooms
    if ($method === 'GET' && $parts === ['hotel-rooms']) {
        $stmt = $db->query("SELECT roomnumber, numberofbeds FROM hotelroom ORDER BY roomnumber");
        jsonResponse($stmt->fetchAll());
    }

    // GET /hotel-rooms/:id/students
    if ($method === 'GET' && count($parts) === 3 && $parts[0] === 'hotel-rooms' && $parts[2] === 'students') {
        $room = (int) $parts[1];
        $stmt = $db->prepare("
            SELECT a.attendeeid, a.firstname, a.lastname, a.email
            FROM student s
            INNER JOIN attendee a ON s.attendeeid = a.attendeeid
            WHERE s.roomnumberstaysin = ?
            ORDER BY a.lastname
        ");
        $stmt->execute([$room]);
        jsonResponse($stmt->fetchAll());
    }

    // GET /sessions/dates
    if ($method === 'GET' && $parts === ['sessions', 'dates']) {
        $stmt = $db->query("SELECT DISTINCT date::text FROM session ORDER BY date");
        $dates = array_column($stmt->fetchAll(), 'date');
        jsonResponse($dates);
    }

    // GET /sessions  (optional ?date= filter)
    if ($method === 'GET' && $parts === ['sessions']) {
        $date = $_GET['date'] ?? null;
        if ($date) {
            $stmt = $db->prepare("SELECT sessionid, sessionname, date::text, starttime::text, endtime::text, roomlocation FROM session WHERE date = ? ORDER BY starttime");
            $stmt->execute([$date]);
        } else {
            $stmt = $db->query("SELECT sessionid, sessionname, date::text, starttime::text, endtime::text, roomlocation FROM session ORDER BY date, starttime");
        }
        jsonResponse($stmt->fetchAll());
    }

    // PUT /sessions/:id
    if ($method === 'PUT' && count($parts) === 2 && $parts[0] === 'sessions') {
        $id   = (int) $parts[1];
        $body = getBody();

        $allowed = ['date', 'starttime', 'endtime', 'roomlocation'];
        $sets    = [];
        $params  = [];

        // Accept camelCase keys from frontend and normalise to snake_case
        $map = [
            'date'         => 'date',
            'startTime'    => 'starttime',
            'endTime'      => 'endtime',
            'roomLocation' => 'roomlocation',
        ];

        foreach ($map as $jsKey => $dbCol) {
            if (isset($body[$jsKey])) {
                $sets[]   = "{$dbCol} = ?";
                $params[] = $body[$jsKey];
            }
        }

        if (empty($sets)) errorResponse('No fields to update');

        $params[] = $id;
        $sql  = "UPDATE session SET " . implode(', ', $sets) . " WHERE sessionid = ? RETURNING sessionid, sessionname, date::text, starttime::text, endtime::text, roomlocation";
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $row  = $stmt->fetch();

        if (!$row) errorResponse('Session not found', 404);
        jsonResponse($row);
    }

    // GET /sponsors
    if ($method === 'GET' && $parts === ['sponsors']) {
        $stmt = $db->query("
            SELECT a.attendeeid, a.firstname, a.lastname, c.companyname, s.sponsorlevel
            FROM sponsor s
            INNER JOIN attendee a ON s.attendeeid = a.attendeeid
            INNER JOIN company  c ON s.companyid  = c.companyid
            ORDER BY s.sponsorlevel, a.lastname
        ");
        jsonResponse($stmt->fetchAll());
    }

    // GET /companies
    if ($method === 'GET' && $parts === ['companies']) {
        $stmt = $db->query("SELECT companyid, companyname FROM company ORDER BY companyname");
        jsonResponse($stmt->fetchAll());
    }

    // POST /companies
    if ($method === 'POST' && $parts === ['companies']) {
        $body = getBody();
        if (empty($body['companyName'])) errorResponse('companyName is required');

        $stmt = $db->prepare("INSERT INTO company (companyname) VALUES (?) RETURNING companyid, companyname");
        $stmt->execute([trim($body['companyName'])]);
        jsonResponse($stmt->fetch(), 201);
    }

    // DELETE /companies/:id
    if ($method === 'DELETE' && count($parts) === 2 && $parts[0] === 'companies') {
        $id   = (int) $parts[1];
        $stmt = $db->prepare("DELETE FROM company WHERE companyid = ?");
        $stmt->execute([$id]);
        http_response_code(204);
        exit;
    }

    // GET /companies/:id/jobs
    if ($method === 'GET' && count($parts) === 3 && $parts[0] === 'companies' && $parts[2] === 'jobs') {
        $id   = (int) $parts[1];
        $stmt = $db->prepare("SELECT jobtitle, location, city, province, payrate::text FROM jobad WHERE postedbycompanyid = ? ORDER BY jobtitle");
        $stmt->execute([$id]);
        jsonResponse($stmt->fetchAll());
    }

    // GET /jobs
    if ($method === 'GET' && $parts === ['jobs']) {
        $stmt = $db->query("
            SELECT j.jobtitle, j.location, j.city, j.province, j.payrate::text, c.companyname
            FROM jobad j
            INNER JOIN company c ON j.postedbycompanyid = c.companyid
            ORDER BY j.payrate DESC
        ");
        jsonResponse($stmt->fetchAll());
    }

    // GET /attendees
    if ($method === 'GET' && $parts === ['attendees']) {
        $stmt = $db->query("SELECT attendeeid, firstname, lastname, email, attendeetype, fee::text FROM attendee ORDER BY attendeeid");
        $rows = $stmt->fetchAll();

        $students      = array_values(array_filter($rows, fn($r) => $r['attendeetype'] === 'Student'));
        $professionals = array_values(array_filter($rows, fn($r) => $r['attendeetype'] === 'Professional'));
        $sponsors      = array_values(array_filter($rows, fn($r) => $r['attendeetype'] === 'Sponsor'));

        jsonResponse(compact('students', 'professionals', 'sponsors'));
    }

    // POST /attendees
    if ($method === 'POST' && $parts === ['attendees']) {
        $body = getBody();

        // Validate required fields
        foreach (['firstName', 'lastName', 'email', 'attendeeType'] as $field) {
            if (empty($body[$field])) errorResponse("{$field} is required");
        }

        $validTypes = ['Student', 'Professional', 'Sponsor'];
        if (!in_array($body['attendeeType'], $validTypes)) {
            errorResponse('attendeeType must be Student, Professional, or Sponsor');
        }

        if ($body['attendeeType'] === 'Sponsor') {
            if (empty($body['sponsorLevel'])) errorResponse('sponsorLevel required for Sponsor');
            if (empty($body['companyId']))    errorResponse('companyId required for Sponsor');
        }

        // Run in a transaction
        $db->beginTransaction();
        try {
            $stmt = $db->prepare("
                INSERT INTO attendee (firstname, lastname, email, attendeetype)
                VALUES (?, ?, ?, ?)
                RETURNING attendeeid, firstname, lastname, email, attendeetype, fee::text
            ");
            $stmt->execute([
                trim($body['firstName']),
                trim($body['lastName']),
                trim($body['email']),
                $body['attendeeType'],
            ]);
            $newAttendee = $stmt->fetch();
            $aid = $newAttendee['attendeeid'];

            if ($body['attendeeType'] === 'Student') {
                $room = !empty($body['roomNumberStaysIn']) ? (int) $body['roomNumberStaysIn'] : null;
                $stmt = $db->prepare("INSERT INTO student (attendeeid, roomnumberstaysin) VALUES (?, ?)");
                $stmt->execute([$aid, $room]);
            } elseif ($body['attendeeType'] === 'Professional') {
                $stmt = $db->prepare("INSERT INTO professional (attendeeid) VALUES (?)");
                $stmt->execute([$aid]);
            } elseif ($body['attendeeType'] === 'Sponsor') {
                $stmt = $db->prepare("INSERT INTO sponsor (attendeeid, sponsorlevel, companyid) VALUES (?, ?, ?)");
                $stmt->execute([$aid, $body['sponsorLevel'], (int) $body['companyId']]);
            }

            $db->commit();
            jsonResponse($newAttendee, 201);

        } catch (Exception $e) {
            $db->rollBack();
            errorResponse($e->getMessage());
        }
    }

    // GET /stats/intake
    if ($method === 'GET' && $parts === ['stats', 'intake']) {
        $reg = $db->query("SELECT COALESCE(SUM(fee), 0)::numeric AS total FROM attendee WHERE attendeetype IN ('Student', 'Professional')")->fetchColumn();

        $spon = $db->query("
            SELECT COALESCE(SUM(
                CASE sponsorlevel
                    WHEN 'Platinum' THEN 10000
                    WHEN 'Gold'     THEN 5000
                    WHEN 'Silver'   THEN 2500
                    WHEN 'Bronze'   THEN 1000
                    ELSE 0
                END
            ), 0) AS total
            FROM sponsor
        ")->fetchColumn();

        jsonResponse([
            'registrationAmount' => (float) $reg,
            'sponsorshipAmount'  => (float) $spon,
        ]);
    }

    // No route matched
    errorResponse('Not found', 404);

} catch (PDOException $e) {
    errorResponse('Database error: ' . $e->getMessage(), 500);
} catch (Exception $e) {
    errorResponse($e->getMessage(), 500);
}
