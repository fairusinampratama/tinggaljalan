"""Exercise the actual Laravel backup command and restore into a new CI database.

Deliberately refuses hosting credentials, non-local servers and existing schemas.
No production backup is downloaded or uploaded by this drill.
"""
import gzip
import os
from pathlib import Path
import subprocess
import tempfile

ROOT = Path(__file__).resolve().parents[2]
SOURCE = 'tj_backup_drill_source'
TARGET = 'tj_backup_drill_restore'


def run(args, **kwargs):
    return subprocess.run(args, check=True, capture_output=True, **kwargs)


def main():
    if not (os.environ.get('GITHUB_ACTIONS') == 'true'
            and os.environ.get('DB_HOST') == '127.0.0.1'
            and os.environ.get('DB_DATABASE') == SOURCE
            and os.environ.get('DB_USERNAME') == 'root'
            and os.environ.get('DB_PASSWORD') == 'disposable-drill-password'
            and os.environ.get('APP_ENV') == 'local'
            and not os.environ.get('DB_URL')):
        raise RuntimeError('Only the fixed disposable GitHub MySQL fixture is permitted')

    owned = []
    with tempfile.TemporaryDirectory(prefix='tj-backup-drill-') as directory:
        work = Path(directory)
        defaults = work / 'client.cnf'
        defaults.write_text('[client]\nhost=127.0.0.1\nport=3306\nuser=root\n'
                            'password=disposable-drill-password\n')
        defaults.chmod(0o600)
        client = ['mysql', '--defaults-extra-file=' + str(defaults), '--batch', '--skip-column-names']

        def sql(statement, database=None):
            return run(client + ([database] if database else []),
                       input=statement.encode()).stdout.decode().strip()

        try:
            # CREATE without IF NOT EXISTS refuses to reuse or reset any schema.
            for database in (SOURCE, TARGET):
                sql('CREATE DATABASE `' + database + '` CHARACTER SET utf8mb4')
                owned.append(database)
            run(['php', 'artisan', 'migrate', '--force'], cwd=ROOT)
            sql('''CREATE TABLE drill_content (
                    id BIGINT PRIMARY KEY, title VARCHAR(255), price DECIMAL(12,2), details JSON
                   );
                   CREATE TABLE drill_orders (
                    id BIGINT PRIMARY KEY, content_id BIGINT,
                    FOREIGN KEY (content_id) REFERENCES drill_content(id)
                   );
                   INSERT INTO drill_content VALUES
                    (1, 'Disposable tourism fixture', 123456.78, '{"days":3,"public":true}');
                   INSERT INTO drill_orders VALUES (5,1);''', SOURCE)
            before = sorted(Path(tempfile.gettempdir()).glob('tj-mysql-*'))
            archive_directory = work / 'backups'
            result = run(['php', 'artisan', 'deploy:backup-database', '--retain=2',
                          '--path=' + str(archive_directory)], cwd=ROOT)
            archives = list(archive_directory.glob('*.sql.gz'))
            if len(archives) != 1 or list(archive_directory.glob('*.sql')):
                raise RuntimeError('Expected one compressed backup and no raw SQL')
            if archive_directory.stat().st_mode & 0o077:
                raise RuntimeError('Backup directory is not private')
            # Restore only into the separately created empty disposable target.
            with gzip.open(archives[0], 'rb') as archive:
                run(client + [TARGET], input=archive.read())
            source_tables = sql('SHOW TABLES', SOURCE).splitlines()
            target_tables = sql('SHOW TABLES', TARGET).splitlines()
            if source_tables != target_tables or len(source_tables) < 20:
                raise RuntimeError('Application schema did not survive backup and restore')
            for statement in (
                'SELECT id,title,price,details FROM drill_content ORDER BY id',
                'SELECT id,content_id FROM drill_orders ORDER BY id',
                'SELECT COUNT(*) FROM migrations',
            ):
                if sql(statement, SOURCE) != sql(statement, TARGET):
                    raise RuntimeError('Restored fixture values differ')
            # Check the restored FK rather than merely comparing table names.
            invalid = subprocess.run(client + [TARGET], input=b'INSERT INTO drill_orders VALUES (6,999)',
                                     capture_output=True)
            if invalid.returncode == 0:
                raise RuntimeError('Restored foreign key is not enforced')

            failure_directory = work / 'failed-backup'
            failed = subprocess.run(
                ['php', 'artisan', 'deploy:backup-database', '--path=' + str(failure_directory)],
                cwd=ROOT, env=dict(os.environ, DB_PASSWORD='intentionally-invalid-fixture'),
                capture_output=True,
            )
            if failed.returncode == 0 or list(failure_directory.iterdir()):
                raise RuntimeError('Failed dump did not clean partial backup files')
            if sorted(Path(tempfile.gettempdir()).glob('tj-mysql-*')) != before:
                raise RuntimeError('Temporary MySQL credentials were not removed')
            print('Disposable MySQL backup/restore passed: application schema, values, FK, '
                  'private directory and failure cleanup verified. No production access.')
        finally:
            for database in reversed(owned):
                sql('DROP DATABASE `' + database + '`')


if __name__ == '__main__':
    main()
