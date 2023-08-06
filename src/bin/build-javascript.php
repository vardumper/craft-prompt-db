<?php

declare(strict_types=1);

use MatthiasMullie\Minify\JS;

// config
$supported_languages = ['php', 'sql'];

try {
    require_once __DIR__ . '/../../vendor/autoload.php';

    $client = new GuzzleHttp\Client();

    // mandatory script(s)
    $sourceFiles = [
        __DIR__ . '/../assets/prompt-db.js',
    ];

    // languages first
    foreach ($supported_languages as $language) {
        if (!is_file(__DIR__ . sprintf('/../assets/languages/%s.min.js', $language))) {
            $response = $client->get(sprintf('https://raw.githubusercontent.com/highlightjs/cdn-release/11-stable/build/languages/%s.min.js', $language));
            if ($response->getStatusCode() > 200) {
                throw new \Exception('couldn\'t fetch file from github');
            }
            file_put_contents(__DIR__ . sprintf('/../assets/languages/%s.min.js', $language), (string) $response->getBody());
        }
        array_unshift($sourceFiles, __DIR__ . sprintf('/../assets/languages/%s.min.js', $language)); // add to beginning of array
    }

    // highlight.js last - so its at beginning of array (before languages)
    if (!is_file(__DIR__ . '/../assets/highlight.js')) {
        $response = $client->get('https://raw.githubusercontent.com/highlightjs/cdn-release/11-stable/build/highlight.js');
        if ($response->getStatusCode() > 200) {
            throw new \Exception('couldn\'t fetch latest release from github');
        }
        file_put_contents(__DIR__ . '/../assets/highlight.js', (string) $response->getBody());
    }
    array_unshift($sourceFiles, __DIR__ . '/../assets/highlight.js'); // add to beginning of array

    // join and minify
    $minifier = new JS();
    foreach ($sourceFiles as $file) {
        $minifier->addFile($file);
    }

    $minifier->minify(__DIR__ . '/../assets/scripts.min.js');
    echo 'Done.';
} catch (\Exception $e) {
    echo 'Error: ' . $e->__toString();
}
