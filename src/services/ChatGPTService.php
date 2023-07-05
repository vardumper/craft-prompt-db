<?php

declare(strict_types=1);

namespace vardumper\promptdb\services;

use OpenAI\Contracts\ClientContract as OpenAIClient;
use vardumper\promptdb\PromptDb;
use vardumper\promptdb\services\Exception\InvalidChatGPTException;
use yii\base\Component;

/**
 * ChatGPT Service service
 */
class ChatGPTService extends Component
{
    private const BASE_PROMPT_CHATGPT = "Given the YAML database description delimited by triple backticks ```%s``` translate the text delimited by triple quotes into a valid %s %s query \"\"\"%s\"\"\". Give me only the SQL code part of the answer.";

    private OpenAIClient $openAiClient;
    private string $user;
    private string $cacheDir;
    private string $basePrompt;
    private string $lastQuery = '';

    public function __construct(OpenAIClient $openAiClient, string $user)
    {
        $this->openAiClient = $openAiClient;
        $this->user = $user;
        $this->cacheDir = PromptDb::getCachePath();
        $this->basePrompt = self::BASE_PROMPT_CHATGPT;
    }

    public function __invoke(string $driverName, string $driverVersion, string $schema, string $prompt): string
    {
        $prompt = sprintf($this->basePrompt, trim($schema), trim($driverName), trim($driverVersion), trim($prompt));
        $answer = $this->getCachePrompt($prompt);
        if ($answer !== false) {
            $this->lastQuery = $answer;
            return $answer;
        }

        $response = $this->openAiClient->chat()->create([
      'model' => 'gpt-3.5-turbo',
      'temperature' => 0,
      'user' => $this->user,
      'messages' => [
        ['role' => 'user', 'content' => $prompt],
      ],
    ]);

        $answer = $response->choices[0]->message->content;
        if (!empty($answer)) {
            $this->lastQuery = $answer;
            $this->setCachePrompt($prompt, $answer);
            return $answer;
        }

        throw new InvalidChatGPTException(sprintf(
      "ChatGPT did not produce an a valid SQL statement: %s",
      var_export($answer, true)
    ));
    }

    protected function getCachePrompt(string $prompt): string|false
    {
        $filename = sprintf("%s/%s.sql", $this->cacheDir, md5($prompt));
        if (file_exists($filename)) {
            return file_get_contents($filename);
        }
        return false;
    }

    protected function setCachePrompt(string $prompt, string $query): bool
    {
        $filename = sprintf("%s/%s.sql", $this->cacheDir, md5($prompt));
        return file_put_contents($filename, $query, LOCK_EX) !== false;
    }
}
