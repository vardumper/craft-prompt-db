<?php

namespace vardumper\promptdb\services;

use Craft;
use OpenAI\Client;
use yii\base\Component;

/**
 * Demo Service service
 */
class DemoService extends Component
{
    private const CACHE_DIR = __DIR__ . '/../cache';
    private const BASE_PROMPT_CHATGPT = "Given the database creation statement delimited by triple backticks ```%s``` translate the text delimited by triple quotes into a valid %s query \"\"\"%s\"\"\". Give me only the SQL code part of the answer. Compress the SQL output removing spaces and line breaks.";
    private Client $openAiClient;
    private string $cacheDir;
    private string $basePrompt;

    public function __construct(Client $openAiClient)
    {
        $this->openAiClient = $openAiClient;
        $this->cacheDir = self::CACHE_DIR;
        $this->basePrompt = self::BASE_PROMPT_CHATGPT;
    }

    public function __invoke(string $driverName, string $driverVersion, string $schema, string $prompt): string
    {
        return 'SELECT * FROM `users` WHERE `admin` = 1';
    }
}
