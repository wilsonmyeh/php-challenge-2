<?php
const API_SECRET = "21db65a65e204cca7b5afcbad91fea59";
date_default_timezone_set("UTC");

//populate_file("./requests.txt", API_SECRET, pow(10, 5));

function populate_db($pdo, $file)
{
    $sql = "
    SELECT      name
    FROM        sqlite_master
    WHERE       type='table'
    AND         name='scores'
    ";
    $statement = $pdo->prepare($sql);
    $statement->execute();
    if ($statement->fetchColumn()) {
        return; // if the table already exists, get out
    }


    $sql = "
    CREATE TABLE scores
    (
        `id`          MEDIUMINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        `user_id`     MEDIUMINT UNSIGNED NOT NULL,
        `score`       TINYINT UNSIGNED NOT NULL,
        `date`        DATE NOT NULL
    )
    ";
    $pdo->exec($sql);


    $sql = "
    INSERT INTO scores (`user_id`, `score`, `date`)
    VALUES (:user_id, :score, :date)
    ";

    $handle = fopen($file, "r");
    while (($request = fgets($handle)) !== false) {
        $data = parse_request($request, API_SECRET);
        if ($data) {
            $pdo->prepare($sql)->execute($data);
        }
    }
    fclose($handle);
}

function populate_file($file, $secret, $count = 1000, $noise = 0.2)
{
    $bugs = 0;
    file_put_contents($file, "");

    for ($i = 0; $i < $count; $i++) {
        $line = make_request(generate_payload(), $secret);
        if (rand(0, 1) < $noise) {
            $swapped = random_swap($line);
            if ($line != $swapped) {
                $line = $swapped;
                $bugs++;
            }
        }

        file_put_contents($file, $line."\n", FILE_APPEND);
    }

    echo "There are ".($count - $bugs)." correctly signed requests in the dataset.\n";
}

function make_request($payload, $secret)
{
    $payload   = json_encode($payload, true);
    $signature = hash_hmac('sha256', $payload, $secret, true);
    $request   = base64_encode($signature).'.'.base64_encode($payload);

    return strtr($request, '+/', '-_');
}

function generate_payload()
{
    return [
        "user_id" => rand(1, 100), // small number of users
        "score"   => rand(1, 100), // fixed score range
        "date"    => date("Y-m-d", rand(strtotime("-1 year"), time())),
    ];
}

function random_swap($line)
{
    // collisions are okay here
    $a = rand(0, strlen($line) - 1);
    $b = rand(0, strlen($line) - 1);

    $tmp      = $line[$b];
    $line[$b] = $line[$a];
    $line[$a] = $tmp;

    return $line;
}
