<?php

function calculate_end_date($start_date, $duration, $is_walk_in) {
    $start = new DateTime($start_date);
    if ($is_walk_in) {
        $interval = new DateInterval("P{$duration}D"); // P for period, D for days
    } else {
        $interval = new DateInterval("P{$duration}M"); // P for period, M for months
    }
    $start->add($interval);
    return $start->format('Y-m-d');
}

function get_membership_duration_in_months_or_days($start_date, $end_date, $is_walk_in) {
    $start = new DateTime($start_date);
    $end = new DateTime($end_date);
    
    // Add 1 day to end date to make the difference inclusive (e.g., Jan 1 to Jan 31 becomes Jan 1 to Feb 1 = 1 Month)
    $end->modify('+1 day');
    $diff = $start->diff($end);

    if ($is_walk_in) {
        return $diff->days;
    }

    return ($diff->y * 12) + $diff->m;
}

?>