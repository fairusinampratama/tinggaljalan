<?php

// Shared configuration logic, also exercised without touching hosting.
class StagingConfigurationException extends RuntimeException
{
}

function stagingValidatePasswords(array $input): void
{
    $errors = [];
    foreach (['db_password', 'review_password', 'admin_password'] as $field) {
        $value = $input[$field] ?? null;
        $minimum = $field === 'db_password' ? 1 : 16;
        if (! is_string($value) || strlen($value) < $minimum
            || preg_match('/[\x00-\x1f\x7f]/', $value)
            || ($field !== 'db_password' && strlen($value) > 64)) {
            $errors[] = 'Invalid staging field: '.$field;
        }
    }
    if (! $errors && count(array_unique(array_intersect_key($input, array_flip(['db_password', 'review_password', 'admin_password'])))) !== 3) {
        $errors[] = 'Use three different staging passwords.';
    }
    if ($errors) {
        throw new StagingConfigurationException(implode('; ', $errors));
    }
}

function stagingSerialize(array $values): string
{
    $lines = [];
    foreach ($values as $name => $value) {
        $quoted = '"'.str_replace(['\\', '"', '$'], ['\\\\', '\\"', '\\$'], $value).'"';
        try {
            $roundtrip = Dotenv\Dotenv::parse($name.'='.$quoted);
        } catch (Throwable) {
            throw new StagingConfigurationException('Configuration encoding failed: '.$name);
        }
        if (($roundtrip[$name] ?? null) !== $value) {
            throw new StagingConfigurationException('Configuration encoding failed: '.$name);
        }
        $lines[] = $name.'='.$quoted;
    }
    return implode("\n", $lines)."\n";
}

function stagingConfigure(string $shared, string $template, array $input, callable $connect): void
{
    stagingValidatePasswords($input);
    $envPath = $shared.'/.env';
    $authPath = $shared.'/.htpasswd';
    $existing = file_exists($envPath);
    $values = Dotenv\Dotenv::parse(file_get_contents($existing ? $envPath : $template));
    if (! $existing) {
        $values['APP_KEY'] = 'base64:'.base64_encode(random_bytes(32));
        $values['DB_PASSWORD'] = $input['db_password'];
    }
    foreach (['APP_ENV' => 'staging', 'APP_DEBUG' => 'false', 'APP_URL' => 'https://preview.tinggaljalan.com',
        'DB_CONNECTION' => 'mysql', 'DB_HOST' => 'localhost', 'DB_DATABASE' => 'u304629909_tj_preview',
        'DB_USERNAME' => 'u304629909_tj_preview', 'DB_URL' => '', 'MAIL_MAILER' => 'log',
        'GOOGLE_ADS_ID' => '', 'GOOGLE_ADS_CONSENT_ENABLED' => 'false', 'QUEUE_CONNECTION' => 'database',
        'MIDTRANS_SERVER_KEY' => '', 'MIDTRANS_CLIENT_KEY' => '', 'DOKU_SECRET_KEY' => '',
        'DOKU_CLIENT_ID' => '', 'SMTP_PASSWORD' => '', 'WHATSPIE_API_TOKEN' => ''] as $name => $expected) {
        if (($values[$name] ?? null) !== $expected) {
            throw new StagingConfigurationException('Unsafe staging configuration: '.$name);
        }
    }
    if (($values['DB_PASSWORD'] ?? '') !== $input['db_password'] || empty($values['APP_KEY'])) {
        throw new StagingConfigurationException('Existing database credentials differ; use the existing password or an explicit rotation.');
    }
    if (file_exists($authPath) && ! password_verify($input['review_password'], trim(substr(file_get_contents($authPath), strlen('reviewer:'))))) {
        throw new StagingConfigurationException('Existing preview access password differs; use the existing password or an explicit rotation.');
    }
    $encoded = stagingSerialize($values);
    try {
        $schema = $connect($input['db_password']);
    } catch (Throwable) {
        throw new StagingConfigurationException('Preview database connection failed. Verify the database password and user permissions in Hostinger.');
    }
    if ($schema !== 'u304629909_tj_preview') {
        throw new StagingConfigurationException('Unexpected connected schema.');
    }
    // Commit only after all validation and the connection check pass.
    foreach ([$envPath => [$encoded, 0600], $authPath => ['reviewer:'.password_hash($input['review_password'], PASSWORD_BCRYPT)."\n", 0644]] as $path => [$content, $permissions]) {
        if (file_exists($path)) {
            continue;
        }
        $temporary = tempnam($shared, '.staging-config-');
        try {
            if ($temporary === false || file_put_contents($temporary, $content, LOCK_EX) !== strlen($content)
                || ! chmod($temporary, $permissions) || ! rename($temporary, $path)) {
                throw new StagingConfigurationException('Could not atomically save staging configuration.');
            }
        } finally {
            if ($temporary && file_exists($temporary)) {
                unlink($temporary);
            }
        }
    }
}
