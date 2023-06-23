<?php

declare(strict_types=1);

namespace vardumper\promptdb\services;

interface ChatGPTInterface
{
    public function search(string $driverName, string $driverVersion, string $prompt, bool $cache = true);
}
