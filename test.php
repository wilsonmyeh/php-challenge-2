<?php
include "library.php";
include "solution.php";

assert_options(ASSERT_ACTIVE, true);
assert_options(ASSERT_WARNING, false);
assert_options(ASSERT_BAIL, true);
assert_options(ASSERT_CALLBACK, function ($path, $line, $message) {
    echo "$path:$line   ERROR"."\n";
});

$pdo = new PDO("sqlite:challenge.sqlite");
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

populate_db($pdo, "./requests.txt"); // this will only work once



// check that all functions exist
assert(function_exists("parse_request"));
assert(function_exists("total_number_of_valid_requests"));
assert(function_exists("dates_with_at_least_n_scores"));
assert(function_exists("users_with_top_score_on_date"));
assert(function_exists("times_user_beat_overall_daily_average"));

// check that encode/decode of the same payload works
for ($i = 0; $i < 1000; $i++) {
    $request = make_request($i, API_SECRET);
    $j       = parse_request($request, API_SECRET);

    assert($i == $j);
}

$correct = 51278;
$actual  = total_number_of_valid_requests($pdo);
assert($correct == $actual);

$correct = ["2017-04-04", "2017-03-05", "2017-01-08", "2017-01-07", "2016-10-24"];
$actual  = dates_with_at_least_n_scores($pdo, 170);
assert($correct == $actual);

$correct = ["90", "46", "48"];
$actual  = users_with_top_score_on_date($pdo, "2017-04-03");
assert($correct == $actual);

$correct = 142;
$actual  = times_user_beat_overall_daily_average($pdo, 3);
assert($correct == $actual);

echo "All tests pass, nice job!\n";
