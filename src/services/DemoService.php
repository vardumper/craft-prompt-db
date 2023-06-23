<?php

namespace vardumper\promptdb\services;

use Craft;
use OpenAI\Client;
use yii\base\Component;
use OpenAI\Contracts\ClientContract as OpenAIClient;

/**
 * Demo Service service
 */
class DemoService extends Component
{
    private const CACHE_DIR = __DIR__ . '/../cache';
    private const BASE_PROMPT_CHATGPT = "Given the table creation statements delimited by triple backticks ```%s``` translate the text delimited by triple quotes into a valid %s %s query \"\"\"%s\"\"\". Give me only the SQL code part of the answer. Compress the SQL output removing spaces and line breaks.";

    private OpenAIClient $openAiClient;
    private string $cacheDir;
    private string $basePrompt;

    public function __construct(OpenAIClient $openAiClient)
    {
        $this->openAiClient = $openAiClient;
        $this->cacheDir = self::CACHE_DIR;
        $this->basePrompt = self::BASE_PROMPT_CHATGPT;
    }

    public function __invoke(string $driverName, string $driverVersion, string $schema, string $prompt): string
    {
        $schema = preg_replace('/\s+/', ' ', $schema);
        $prompt = sprintf($this->basePrompt, trim($schema), trim($driverName), trim($driverVersion), trim($prompt));
        $response = $this->openAiClient->chat()->create([
            'model' => 'gpt-3.5-turbo',
            'messages' => [
                ['role' => 'user', 'content' => $prompt]
            ]
        ]);
        $answer = $response->choices[0]->message->content;
        if (!empty($answer)) {
            // $this->setCachePrompt($query, $answer);
            return $answer;
        }
        throw new InvalidChatGPTException(sprintf(
            "ChatGPT did not produce an a valid SQL query: %s",
            var_export($answer, true)
        ));
        // return $result;
    }
}
