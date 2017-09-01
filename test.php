<?php
include "library.php";
include "solution.php";

assert_options(ASSERT_ACTIVE, true);
assert_options(ASSERT_WARNING, false);
assert_options(ASSERT_BAIL, true);
assert_options(ASSERT_CALLBACK, function ($path, $line, $message) {
    echo "$path:$line   ERROR"."\n";
});


assert(function_exists("parse_request"));
assert(function_exists("dates_with_at_least_n_scores"));
assert(function_exists("users_with_top_score_on_date"));
assert(function_exists("times_user_beat_overall_daily_average"));

// check that encode/decode of the same payload works
for ($i = 0; $i < 1000; $i++) {
    $request = make_request($i, API_SECRET);
    $j       = parse_request($request, API_SECRET);

    assert($i == $j);
}


$pdo = new PDO("sqlite:challenge.sqlite");
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

create_db_table($pdo);
populate_db_from_csv($pdo, "./data/data-1.csv");

$correct = ["2017-09-02", "2017-09-01", "2017-08-31"];
$actual  = dates_with_at_least_n_scores($pdo, 1);
assert($correct == $actual);

$correct = ["2017-09-02", "2017-09-01"];
$actual  = dates_with_at_least_n_scores($pdo, 2);
assert($correct == $actual);

$correct = ["2017-09-02"];
$actual  = dates_with_at_least_n_scores($pdo, 3);
assert($correct == $actual);


populate_db_from_csv($pdo, "./data/data-2.csv");

$correct = ["1"];
$actual  = users_with_top_score_on_date($pdo, "2017-08-31");
assert($correct == $actual);

$correct = ["2"];
$actual  = users_with_top_score_on_date($pdo, "2017-09-01");
assert($correct == $actual);

$correct = ["1", "2"];
$actual  = users_with_top_score_on_date($pdo, "2017-09-02");
assert($correct == $actual);


populate_db_from_csv($pdo, "./data/data-3.csv");

$correct = 1;
$actual  = times_user_beat_overall_daily_average($pdo, 1);
assert($correct == $actual);

$correct = 2;
$actual  = times_user_beat_overall_daily_average($pdo, 2);
assert($correct == $actual);

$correct = 0;
$actual  = times_user_beat_overall_daily_average($pdo, 3);
assert($correct == $actual);



// the following tests are based on the randomly generated dataset
//populate_requests("./data/requests.txt", API_SECRET, pow(10, 5));

$correct = 51278;
$actual  = populate_db_from_requests($pdo, "./data/requests.txt");
assert($correct == $actual);

$correct = ["2017-04-04", "2017-03-05", "2017-01-08", "2017-01-07", "2016-10-24"];
$actual  = dates_with_at_least_n_scores($pdo, 170);
assert($correct == $actual);

$correct = ["46", "48", "90"];
$actual  = users_with_top_score_on_date($pdo, "2017-04-03");
assert($correct == $actual);

$correct = 142;
$actual  = times_user_beat_overall_daily_average($pdo, 3);
assert($correct == $actual);

echo "All tests pass, nice job!\n";
unlink("challenge.sqlite");
