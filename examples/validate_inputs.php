<?php

declare(strict_types=1);

/**
 * Example: validating user input with laminas-validator.
 *
 * Run from the laminas-validator project root:
 *   php examples/validate_inputs.php
 */

require __DIR__ . '/../vendor/autoload.php';

use Laminas\Validator\EmailAddress;
use Laminas\Validator\StringLength;
use Laminas\Validator\Between;
use Laminas\Validator\Regex;
use Laminas\Validator\ValidatorChain;

// --- Standalone validators ---
$email = new EmailAddress();
$inputs = ['alice@example.com', 'not-an-email', 'bob@domain.co.uk'];
foreach ($inputs as $input) {
    $valid = $email->is_valid($input);
    printf("Email %-25s => %s\n", $input, $valid ? 'valid' : implode('; ', $email->get_messages()));
}

echo "\n";

// --- StringLength ---
$length = new StringLength(['min' => 3, 'max' => 20]);
$words  = ['hi', 'hello', 'this_is_a_very_long_username_that_exceeds_limit'];
foreach ($words as $word) {
    $valid = $length->is_valid($word);
    printf("Length %-45s => %s\n", "'$word'", $valid ? 'valid' : implode('; ', $length->get_messages()));
}

echo "\n";

// --- Between (numeric range) ---
$range = new Between(['min' => 1, 'max' => 100, 'inclusive' => true]);
foreach ([0, 1, 50, 100, 101] as $num) {
    printf("Between(1,100) %-4d => %s\n", $num, $range->is_valid($num) ? 'valid' : 'invalid');
}

echo "\n";

// --- ValidatorChain: combine validators ---
$chain = new ValidatorChain();
$chain->attach(new StringLength(['min' => 8, 'max' => 64]))
      ->attach(new Regex(['pattern' => '/[A-Z]/']))  // must contain uppercase
      ->attach(new Regex(['pattern' => '/[0-9]/'])); // must contain digit

$passwords = ['short', 'alllowercase1', 'HasNumber1', 'NoNumber'];
foreach ($passwords as $pwd) {
    $valid = $chain->is_valid($pwd);
    printf("Password %-20s => %s\n", "'$pwd'", $valid ? 'valid' : implode('; ', array_values($chain->get_messages())));
}
