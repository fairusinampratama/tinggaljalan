<?php

require __DIR__.'/../../vendor/autoload.php';
require __DIR__.'/../../scripts/deployment/staging-config.php';

function check(bool $condition, string $message): void
{
    if (! $condition) {
        throw new RuntimeException($message);
    }
}
function fails(callable $action, string $message): void
{
    try {
        $action();
    } catch (StagingConfigurationException $error) {
        check(str_contains($error->getMessage(), $message), 'Unexpected safe diagnostic.');

        return;
    }
    throw new RuntimeException('Expected configuration failure.');
}
$directory = sys_get_temp_dir().'/staging-config-test-'.bin2hex(random_bytes(8));
mkdir($directory, 0700);
$template = __DIR__.'/../../.env.staging.example';
$input = ['db_password' => 'existing"\\${HOME}', 'review_password' => 'review-unique-123456789', 'admin_password' => 'admin-unique-123456789'];
$connect = fn (string $password): string => 'u304629909_tj_preview';
try {
    fails(fn () => stagingValidatePasswords([]), 'db_password');
    fails(fn () => stagingValidatePasswords(array_fill_keys(array_keys($input), 'same-password-123456')), 'different');
    fails(fn () => stagingValidatePasswords([...$input, 'review_password' => str_repeat('r', 65)]), 'review_password');
    $failure = function (string $password): string {
        throw new RuntimeException('sensitive connection error');
    };
    fails(fn () => stagingConfigure($directory, $template, $input, $failure), 'connection failed');
    check(! file_exists($directory.'/.env') && ! file_exists($directory.'/.htpasswd'), 'Failed connection wrote configuration.');
    fails(fn () => stagingConfigure($directory, $template, $input, fn () => 'other_schema'), 'schema');
    check(! file_exists($directory.'/.env'), 'Unexpected schema wrote configuration.');
    stagingConfigure($directory, $template, $input, $connect);
    $before = file_get_contents($directory.'/.env');
    $authBefore = file_get_contents($directory.'/.htpasswd');
    check(Dotenv\Dotenv::parse($before)['DB_PASSWORD'] === $input['db_password'], 'Special characters changed.');
    check((fileperms($directory.'/.env') & 0777) === 0600, 'Environment permissions differ.');
    check(password_verify($input['review_password'], trim(substr($authBefore, 9))), 'Access password changed.');
    stagingConfigure($directory, $template, $input, $connect);
    check(file_get_contents($directory.'/.env') === $before && file_get_contents($directory.'/.htpasswd') === $authBefore, 'Retry rewrote existing credentials.');
    fails(fn () => stagingConfigure($directory, $template, [...$input, 'db_password' => 'corrected-different'], $connect), 'Existing database');
    fails(fn () => stagingConfigure($directory, $template, [...$input, 'review_password' => 'another-review-123456789'], $connect), 'Existing preview');
    fails(fn () => stagingConfigure($directory, $template, $input, $failure), 'connection failed');
    check(file_get_contents($directory.'/.env') === $before && file_get_contents($directory.'/.htpasswd') === $authBefore, 'Failure changed existing configuration.');
    check(count(glob($directory.'/.staging-config-*')) === 0, 'Temporary credential file remained.');
    echo "Staging configuration validation, encoding, failure, and retry checks passed.\n";
} finally {
    foreach (glob($directory.'/{*,.*}', GLOB_BRACE) as $file) {
        if (is_file($file)) {
            unlink($file);
        }
    }
    rmdir($directory);
}
