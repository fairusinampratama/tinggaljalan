<?php

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Process\Process;

class ArticleDeploymentValidatorTest extends TestCase
{
    public static function articleCases(): array
    {
        return [
            'legacy direct sections' => [false, 'us', '', true],
            'styled wrapper' => [true, 'us', '', true],
            'Chinese wrapper' => [true, 'cn', '', true],
            'missing section' => [true, 'us', 'missing-section', false],
            'duplicate section' => [true, 'us', 'duplicate-section', false],
            'reordered sections' => [true, 'us', 'reordered-sections', false],
            'missing H2' => [true, 'us', 'missing-h2', false],
            'changed heading' => [true, 'us', 'changed-heading', false],
            'missing paragraph' => [true, 'us', 'missing-paragraph', false],
            'reordered paragraphs' => [true, 'us', 'reordered-paragraphs', false],
            'changed paragraph' => [true, 'us', 'changed-paragraph', false],
            'missing line break' => [true, 'us', 'missing-break', false],
        ];
    }

    #[DataProvider('articleCases')]
    public function test_deployment_validation_preserves_article_semantics(bool $wrapped, string $language, string $damage, bool $valid): void
    {
        $sections = [
            ['heading' => ['us' => 'First & section', 'cn' => '第一节'], 'body' => ['us' => "First line\nSecond line\n\nSecond paragraph", 'cn' => "第一行\n第二行\n\n第二段"]],
            ['heading' => ['us' => 'Next section', 'cn' => '下一节'], 'body' => ['us' => 'Final paragraph', 'cn' => '最后一段']],
        ];
        $escape = fn (string $text) => htmlspecialchars($text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $rendered = array_map(function (array $section, int $index) use ($language, $escape): string {
            $paragraphs = array_map(fn (string $text) => '<p>'.str_replace("\n", '<br>', $escape($text)).'</p>', explode("\n\n", $section['body'][$language]));

            return '<section id="section-'.$index.'"><h2>'.$escape($section['heading'][$language]).'</h2>'.implode('', $paragraphs).'</section>';
        }, $sections, array_keys($sections));
        if ($damage === 'missing-section') {
            array_pop($rendered);
        } elseif ($damage === 'duplicate-section') {
            $rendered[] = $rendered[0];
        } elseif ($damage === 'reordered-sections') {
            $rendered = array_reverse($rendered);
        }
        $body = implode('', $rendered);
        $body = match ($damage) {
            'missing-h2' => preg_replace('/<h2>.*?<\/h2>/', '', $body, 1),
            'changed-heading' => preg_replace('/<h2>.*?<\/h2>/', '<h2>Wrong heading</h2>', $body, 1),
            'missing-paragraph' => preg_replace('/<p>.*?<\/p>/', '', $body, 1),
            'changed-paragraph' => preg_replace('/<p>.*?<\/p>/', '<p>Incomplete content</p>', $body, 1),
            'reordered-paragraphs' => preg_replace('/(<p>.*?<\/p>)(<p>.*?<\/p>)/', '$2$1', $body, 1),
            'missing-break' => preg_replace('/<br>/', '', $body, 1),
            default => $body,
        };
        if ($wrapped) {
            $body = '<div class="server-document">'.$body.'</div>';
        }
        $payload = json_encode(['component' => 'NewsDetailPage', 'props' => ['language' => $language, 'article' => ['sections' => $sections]]], JSON_THROW_ON_ERROR | JSON_HEX_TAG);
        $html = '<!doctype html><html><head><meta charset="UTF-8"></head><body><main class="server-seo-content"><h1>Article</h1>'.$body.'</main><script data-page="app" type="application/json">'.$payload.'</script></body></html>';
        $file = tempnam(sys_get_temp_dir(), 'article-validator-');
        try {
            file_put_contents($file, $html);
            $process = new Process([PHP_BINARY, dirname(__DIR__, 2).'/scripts/deployment/assert-article-html.php', $file]);
            $process->run();
            $this->assertSame($valid, $process->isSuccessful(), $process->getErrorOutput());
        } finally {
            unlink($file);
        }
    }
}
