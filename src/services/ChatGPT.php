<?php

declare(strict_types=1);

namespace vardumper\promptdb\services;

use craft\base\Component;
use OpenAI\Client as OpenAIClient;

class ChatGPT extends Component implements ChatGPTInterface
{
    private const CACHE_DIR = __DIR__ . '/../cache';
    private const BASE_PROMPT_CHATGPT = "Given the database schema delimited by triple backticks ```%s``` translate the text delimited by triple quotes into a valid %s query \"\"\"%s\"\"\". Give me only the SQL code part of the answer. Compress the SQL output removing spaces and line breaks.";

    private OpenAIClient $openAiClient;
    private string $cacheDir;
    private string $basePrompt;

    public function __construct(OpenAIClient $openAiClient)
    {
        $this->openAiClient = $openAiClient;
        $this->cacheDir = self::CACHE_DIR;
        $this->basePrompt = self::BASE_PROMPT_CHATGPT;
    }

    public function getSQL(string $index, string $prompt, bool $cache = true): // Yii db result set
    {
        $prompt = sprintf($this->basePrompt, $schema, $driverName, $prompt);
        $promptHash = md5($prompt);
        $cacheFile = $this->cacheDir . '/' . $promptHash . '.json';
        if ($cache && file_exists($cacheFile)) {
            $result = json_decode(file_get_contents($cacheFile), true);
        } else {
            $result = $this->openAiClient->completions()->create([
                'prompt' => $prompt,
                'max_tokens' => 64,
                'temperature' => 0,
                'top_p' => 1,
                'n' => 1,
                'stream' => false,
                'logprobs' => null,
                'echo' => false,
                'stop' => ['```'],
            ]);
            if ($cache) {
                file_put_contents($cacheFile, json_encode($result));
            }
        }
        return $result;
    }

}
