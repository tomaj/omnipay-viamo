<?php

declare(strict_types=1);

namespace Omnipay\Viamo\Tests;

use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Runs every ```php block of README.md, UPGRADE-5.0.md and docs/ against the current code
 * (the longer examples live in docs/snippets/ and examples/ and are tested on their own).
 *
 * - A block runs in its own PHP process with the Composer autoloader and the variables the
 *   short snippets refer to already defined (see prepend()), and must exit with status 0
 *   without writing to stderr.
 * - A block directly after `<!-- snippet: skip -->` is not run: the "before" side of an upgrade
 *   step, which on purpose no longer works.
 */
final class InlineSnippetsTest extends TestCase
{
    private const string SKIP = '<!-- snippet: skip -->';

    /**
     * @return iterable<string, array{string}>
     */
    public static function snippetProvider(): iterable
    {
        $root = dirname(__DIR__);
        $files = [$root . '/README.md', $root . '/UPGRADE-5.0.md'];
        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($root . '/docs', \FilesystemIterator::SKIP_DOTS));
        foreach ($iterator as $file) {
            \assert($file instanceof \SplFileInfo);
            $path = $file->getPathname();
            if ($file->getExtension() === 'md' && !str_contains($path, '/node_modules/') && !str_contains($path, '/.vitepress/')) {
                $files[] = $path;
            }
        }
        sort($files);

        foreach ($files as $path) {
            $relative = substr($path, strlen($root) + 1);
            foreach (self::blocks((string) file_get_contents($path)) as $line => $code) {
                yield $relative . ':' . $line => [$code];
            }
        }
    }

    public function testDocumentationHasSnippets(): void
    {
        self::assertGreaterThanOrEqual(8, iterator_count(self::snippetProvider()));
    }

    #[DataProvider('snippetProvider')]
    public function testSnippetRuns(string $code): void
    {
        $script = tempnam(sys_get_temp_dir(), 'snippet');
        $prepend = tempnam(sys_get_temp_dir(), 'prepend');
        self::assertIsString($script);
        self::assertIsString($prepend);

        try {
            file_put_contents($script, "<?php\n\ndeclare(strict_types=1);\n\n" . $code);
            file_put_contents($prepend, $this->prepend());

            $process = proc_open(
                [PHP_BINARY, '-d', 'auto_prepend_file=' . $prepend, '-d', 'display_errors=stderr', '-d', 'error_reporting=-1', '-d', 'zend.assertions=1', $script],
                [1 => ['pipe', 'w'], 2 => ['pipe', 'w']],
                $pipes,
            );
            self::assertIsResource($process);
            $stdout = (string) stream_get_contents($pipes[1]);
            $stderr = (string) stream_get_contents($pipes[2]);
            fclose($pipes[1]);
            fclose($pipes[2]);
            $status = proc_close($process);
        } finally {
            unlink($script);
            unlink($prepend);
        }

        self::assertSame('', $stderr, "The snippet wrote to stderr:\n" . $code);
        self::assertSame(0, $status, "The snippet failed:\n" . $code . "\n" . $stdout);
    }

    /**
     * The context of the short snippets: a configured gateway whose HTTP request carries a
     * signed return URL, a signed webhook body, and the requests and responses they mention.
     */
    private function prepend(): string
    {
        $autoload = var_export(__DIR__ . '/../vendor/autoload.php', true);
        $bid = var_export(self::BID, true);
        $key1 = var_export(self::KEY1, true);
        $key2 = var_export(self::KEY2, true);

        return <<<PHP
            <?php
            // deprecations raised inside dependencies (omnipay/common on PHP 8.6, old polyfills with
            // --prefer-lowest) are not the snippet's; set before the autoloader compiles them
            set_error_handler(static fn(int \$type, string \$message, string \$file): bool => \$type === E_DEPRECATED && str_contains(\$file, '/vendor/'));
            require {$autoload};
            putenv('VIAMO_KEY1=' . {$key1});
            putenv('VIAMO_KEY2=' . {$key2});
            \$sign = new Omnipay\\Viamo\\Core\\ViamoSign();
            \$symfonyRequest = new Symfony\\Component\\HttpFoundation\\Request(['responseString' => 'BID:42*VS:777*RES:OK*SIG:' . \$sign->sign('BID:42*VS:777*RES:OK', {$key1})]);
            \$gateway = new Omnipay\\Viamo\\Gateway(null, \$symfonyRequest);
            \$gateway->initialize(['bid' => {$bid}, 'key1' => {$key1}, 'key2' => {$key2}]);
            \$json = ['notificationId' => 'N1', 'payment' => ['vs' => '777', 'result' => 'OK', 'amount' => '10.00', 'id' => 555], 'signature' => ['sign' => \$sign->sign('777OK10.00555', {$key2})]];
            \$parameters = ['amount' => '10.00', 'currency' => 'EUR', 'vs' => '777'];
            \$request = \$gateway->purchase(\$parameters);
            \$response = \$gateway->webhook(['json' => \$json])->send();
            \$result = \$gateway->completePurchase()->send();
            function markOrderPaid(?string \$vs): void {}
            PHP;
    }

    /**
     * @return array<int, string> line number => php code
     */
    private static function blocks(string $markdown): array
    {
        $lines = explode("\n", $markdown);
        $blocks = [];
        $previous = '';
        $count = count($lines);
        for ($i = 0; $i < $count; $i++) {
            if (preg_match('/^(\s*)```(\w+)/', $lines[$i], $match) !== 1) {
                if (trim($lines[$i]) !== '') {
                    $previous = trim($lines[$i]);
                }
                continue;
            }
            $start = $i + 1;
            $indent = strlen($match[1]);
            $body = [];
            for ($i++; $i < $count && !str_starts_with(ltrim($lines[$i]), '```'); $i++) {
                $body[] = substr($lines[$i], min($indent, strlen($lines[$i]) - strlen(ltrim($lines[$i]))));
            }
            if ($match[2] === 'php' && $previous !== self::SKIP) {
                $blocks[$start] = (string) preg_replace('/^<\?php\s*/', '', implode("\n", $body) . "\n");
            }
            $previous = '```';
        }

        return $blocks;
    }
}
