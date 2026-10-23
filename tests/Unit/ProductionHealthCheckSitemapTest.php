<?php

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Process\Process;

class ProductionHealthCheckSitemapTest extends TestCase
{
    #[DataProvider('articleLocations')]
    public function test_production_health_check_validates_sitemap_locations_without_rewriting_them(string $article, bool $accepted): void
    {
        $source = file_get_contents(dirname(__DIR__, 2).'/scripts/deployment/health-check.sh');
        $start = strpos($source, 'SITEMAP="$WORK/sitemap.xml"');
        $this->assertNotFalse($start);
        $end = strpos($source, "\nwhile IFS= read -r url; do", $start);
        $this->assertNotFalse($end);
        $block = substr($source, $start, $end - $start);

        $work = sys_get_temp_dir().'/tinggaljalan-health-'.bin2hex(random_bytes(8));
        mkdir($work, 0700);
        $fixture = $work.'/fixture.xml';
        $xml = '<?xml version="1.0"?><urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'
            .'<url><loc>https://example.test/</loc></url>'
            .'<url><loc>https://example.test/routes/bromo</loc></url>'
            .'<url><loc>'.htmlspecialchars($article, ENT_XML1).'</loc></url></urlset>';
        file_put_contents($fixture, $xml);

        try {
            // Execute the real deployment parser with PHP/SimpleXML. Only its
            // HTTP download is replaced by a local XML fixture; no host access.
            $process = new Process(['bash', '-c', "set -euo pipefail\nfresh_request() { cat \"\$FIXTURE\"; }\n".$block], null, [
                'BASE_URL' => 'https://example.test',
                'FIXTURE' => $fixture,
                'PHP_BIN' => PHP_BINARY,
                'WORK' => $work,
            ]);
            $process->run();

            if ($accepted) {
                $this->assertTrue($process->isSuccessful(), $process->getErrorOutput());
                $this->assertSame("https://example.test/routes/bromo\n".$article."\n", file_get_contents($work.'/sample-urls'));
            } else {
                $this->assertFalse($process->isSuccessful());
                $this->assertStringContainsString('Malformed sitemap location:', $process->getErrorOutput());
            }
        } finally {
            foreach (glob($work.'/*') as $file) {
                unlink($file);
            }
            rmdir($work);
        }
    }

    public static function articleLocations(): array
    {
        return [
            'ordinary slug' => ['https://example.test/news/bromo-guide', true],
            'encoded legacy spaces' => ['https://example.test/news/5%20Things%20to%20Check', true],
            'encoded reserved characters' => ['https://example.test/news/Bromo%3F%20Guide%2520', true],
            'raw spaces' => ['https://example.test/news/5 Things to Check', false],
            'leading whitespace' => [' https://example.test/news/bromo-guide', false],
            'trailing whitespace' => ["https://example.test/news/bromo-guide\n", false],
            'empty location' => ['', false],
        ];
    }
}
