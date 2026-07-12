<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use RuntimeException;
use Symfony\Component\Process\Process;
use Throwable;

class BackupDeploymentDatabase extends Command
{
    protected $signature = 'deploy:backup-database
        {--retain=10 : Number of newest backups to retain}
        {--path= : Absolute backup directory; defaults to storage/app/backups/database}';

    protected $description = 'Create a compressed production MySQL backup for an atomic deployment';

    public function handle(): int
    {
        if (app()->environment('testing')) {
            $this->error('Deployment database backups are disabled in the testing environment.');

            return self::FAILURE;
        }

        $connectionName = (string) config('database.default');
        $connection = config("database.connections.{$connectionName}");

        if (! is_array($connection) || ($connection['driver'] ?? null) !== 'mysql') {
            $this->error('The deployment backup command supports the MySQL driver only.');

            return self::FAILURE;
        }

        $retain = filter_var($this->option('retain'), FILTER_VALIDATE_INT, [
            'options' => ['min_range' => 1, 'max_range' => 100],
        ]);

        if ($retain === false) {
            $this->error('The --retain option must be between 1 and 100.');

            return self::FAILURE;
        }

        $directory = rtrim((string) ($this->option('path') ?: storage_path('app/backups/database')), DIRECTORY_SEPARATOR);

        if ($directory === '' || ! str_starts_with($directory, DIRECTORY_SEPARATOR)) {
            $this->error('The backup path must be absolute.');

            return self::FAILURE;
        }

        File::ensureDirectoryExists($directory, 0700, true);

        $timestamp = now()->format('Ymd-His');
        $database = (string) ($connection['database'] ?? '');
        $safeDatabase = Str::slug($database) ?: 'database';
        $sqlPath = "{$directory}/{$safeDatabase}-{$timestamp}.sql";
        $archivePath = "{$sqlPath}.gz";
        $defaultsPath = tempnam(sys_get_temp_dir(), 'tj-mysql-');

        if ($defaultsPath === false) {
            throw new RuntimeException('Unable to create a temporary MySQL credentials file.');
        }

        try {
            $this->writeDefaultsFile($defaultsPath, $connection);

            $dump = new Process([
                'mysqldump',
                "--defaults-extra-file={$defaultsPath}",
                '--single-transaction',
                '--quick',
                '--skip-lock-tables',
                '--no-tablespaces',
                "--result-file={$sqlPath}",
                $database,
            ]);
            $dump->setTimeout(600);
            $dump->mustRun();

            if (! is_file($sqlPath) || filesize($sqlPath) === 0) {
                throw new RuntimeException('mysqldump created an empty backup.');
            }

            $gzip = new Process(['gzip', '-f', $sqlPath]);
            $gzip->setTimeout(600);
            $gzip->mustRun();

            if (! is_file($archivePath) || filesize($archivePath) === 0) {
                throw new RuntimeException('The compressed database backup is missing or empty.');
            }

            $this->pruneBackups($directory, (int) $retain);
            $this->info($archivePath);

            return self::SUCCESS;
        } catch (Throwable $exception) {
            File::delete([$sqlPath, $archivePath]);
            $this->error('Database backup failed: '.$exception->getMessage());

            return self::FAILURE;
        } finally {
            File::delete($defaultsPath);
        }
    }

    private function writeDefaultsFile(string $path, array $connection): void
    {
        $values = [
            'host' => $connection['host'] ?? '127.0.0.1',
            'port' => $connection['port'] ?? 3306,
            'user' => $connection['username'] ?? '',
            'password' => $connection['password'] ?? '',
        ];

        foreach ($values as $value) {
            if (str_contains((string) $value, "\n") || str_contains((string) $value, "\r")) {
                throw new RuntimeException('Database credentials contain an unsupported newline.');
            }
        }

        $quote = static fn (mixed $value): string => '"'.str_replace(
            ['\\', '"'],
            ['\\\\', '\\"'],
            (string) $value,
        ).'"';

        $contents = "[client]\n";
        foreach ($values as $key => $value) {
            $contents .= "{$key}=".$quote($value)."\n";
        }

        if (file_put_contents($path, $contents, LOCK_EX) === false || ! chmod($path, 0600)) {
            throw new RuntimeException('Unable to protect the temporary MySQL credentials file.');
        }
    }

    private function pruneBackups(string $directory, int $retain): void
    {
        collect(File::glob("{$directory}/*.sql.gz"))
            ->sortByDesc(fn (string $path): int => File::lastModified($path))
            ->values()
            ->slice($retain)
            ->each(fn (string $path) => File::delete($path));
    }
}
