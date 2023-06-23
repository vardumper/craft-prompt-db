<?php

namespace vardumper\promptdb\services;

use Craft;
use OpenAI\Contracts\ClientContract as Client;
use yii\base\Component;

/**
 * Demo Service service
 */
class DemoService extends Component
{
    private Client $openAiClient;

    public function __construct(Client $openAiClient)
    {
        $this->openAiClient = $openAiClient;
    }

    public function __invoke(string $driverName, string $driverVersion, string $schema, string $prompt): string
    {
        return 'SELECT * FROM `users` WHERE `admin` = 1';
    }
}
