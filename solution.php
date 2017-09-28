<?php
// YOUR NAME AND EMAIL GO HERE
// Wilson Yeh
// wilsonmyeh@gmail.com

function parse_request($request, $secret)
{
    // Reverse everything in make_request
    $request = strtr($request, array('-_' => '+/')); // Translate back from URL-safe

    // Separate signature from payload
    $parts = explode('.', $request);
    if (count($parts) !== 2) {
    	return false; // Missing payload or signature
    }

    // Decode signature and payload
    $signature = base64_decode($parts[0], true);
    $payload = base64_decode($parts[1], true);

    if ($signature === false || $payload === false || $signature !== hash_hmac('sha256', $payload, $secret)) {
    	return false; // Signature is invalid, or payload is invalid, or signature does not match payload
    }

    return json_decode($payload, true);
}

function dates_with_at_least_n_scores($pdo, $n)
{
	// Group scores by date, but filter out any dates with less than n scores
	// Descending order by date
    $sql = "
    SELECT date
    FROM scores
    GROUP BY date
    HAVING COUNT(score) >= :n
    ORDER BY date
    DESC
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':n', $n, PDO::PARAM_INT); // Emulated prepares are difficult
    $result = $stmt->execute();
    $dates = $stmt->fetchAll(PDO::FETCH_COLUMN, 'date'); // Fetch only the array of dates
    return $dates;
}

function users_with_top_score_on_date($pdo, $date)
{
    // Select the top score for that date, and select only users from that date with the top score
    $sql = "
    SELECT user_id
    FROM scores
    WHERE score IN (
    				SELECT MAX(score)
    				FROM scores
    				WHERE date = :date
    			  )
    AND date = :date
    ORDER BY user_id
    ASC
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':date', $date);
    $result = $stmt->execute();
    $users = $stmt->fetchAll(PDO::FETCH_COLUMN, 'user_id'); // Fetch only the array of user_id
    return $users;
}

function dates_when_user_was_in_top_n($pdo, $user_id, $n)
{
	// Group by date
	// Don't want to have to re-query the top n for every date
	// Try to memorize whether or not the user is in the top n for each date

	// Join the table with itself s.t. for each row in the original table,
	// there are now i rows where i is the number of rows with a higher score on the same date

	// We want to count the number of rows with a higher score than user_id to figure out that date's placement

	// Filter by the desired user_id
	// Group by date, then for each date, filter out any rows where the number of rows with a higher score is larger than n

	// id's are null due to the join, but user_id's are assumed to still unique for each combination
	// (no re-scores, otherwise need criteria for which score to pick for each user)

	// If there are no rows with a higher score, the date is joined with a null row. COUNT does not count a null column value

    $sql = "
    SELECT s1.date as date
    FROM scores s1
    LEFT OUTER JOIN	scores s2
    ON (s1.date = s2.date AND s1.score < s2.score)
	WHERE s1.user_id = :user_id
	GROUP BY s1.date
	HAVING COUNT(s2.user_id) < :n
	ORDER BY s1.date
	DESC
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);
    $stmt->bindValue(':n', $n, PDO::PARAM_INT);
    $result = $stmt->execute();
    $dates = $stmt->fetchAll(PDO::FETCH_COLUMN, 'date'); // Fetch only the array of dates
    return $dates;
}
