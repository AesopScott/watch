<?php
// Session attendance tracking — capacity enforcement and join logging.
// Requires read_json_file / write_json_file from lib/auth.php (loaded first).

const SESSION_CAPACITY_PRO_LITE = 10;
const SESSION_CAPACITY_PRO      = 30;  // all non-lite plans share this cap

$_PRO_LITE_PLANS = ['pro_lite', 'weekly', 'lite'];

function attendance_file(): string {
    return __DIR__ . '/../data/attendance.json';
}

function is_pro_lite_plan(string $plan): bool {
    global $_PRO_LITE_PLANS;
    return in_array(strtolower(trim($plan)), $_PRO_LITE_PLANS, true);
}

// Returns all join records for a session, or [] on missing/error.
function get_session_joins(string $session_id): array {
    $data = read_json_file(attendance_file());
    return $data['sessions'][$session_id]['joins'] ?? [];
}

// True if this email has already been recorded for the session.
function has_already_joined(array $joins, string $email): bool {
    foreach ($joins as $j) {
        if (($j['email'] ?? '') === $email) return true;
    }
    return false;
}

// Returns ['lite' => n, 'pro' => n] counts from join records.
function count_joins_by_tier(array $joins): array {
    $counts = ['lite' => 0, 'pro' => 0];
    foreach ($joins as $j) {
        if (is_pro_lite_plan($j['plan'] ?? '')) {
            $counts['lite']++;
        } else {
            $counts['pro']++;
        }
    }
    return $counts;
}

// Checks capacity for a given plan. Returns true if there is a seat available.
// Fail-open: returns true on any read error so subscribers aren't locked out.
function has_capacity(string $session_id, string $plan): bool {
    try {
        $joins  = get_session_joins($session_id);
        $counts = count_joins_by_tier($joins);
        if (is_pro_lite_plan($plan)) {
            return $counts['lite'] < SESSION_CAPACITY_PRO_LITE;
        }
        return $counts['pro'] < SESSION_CAPACITY_PRO;
    } catch (Throwable $e) {
        return true;  // fail open
    }
}

// Records a join. Idempotent — won't double-count the same email.
// Returns true on success, false on error (fail-open callers should proceed anyway).
function record_join(string $session_id, string $email, string $plan): bool {
    try {
        $data  = read_json_file(attendance_file());
        $joins = $data['sessions'][$session_id]['joins'] ?? [];

        if (has_already_joined($joins, $email)) return true;

        $joins[] = [
            'email'     => $email,
            'plan'      => $plan,
            'joined_at' => gmdate('c'),
        ];
        $data['sessions'][$session_id]['joins'] = $joins;
        write_json_file(attendance_file(), $data);
        return true;
    } catch (Throwable $e) {
        return false;
    }
}
