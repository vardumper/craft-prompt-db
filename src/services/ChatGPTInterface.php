<?php

declare(strict_types=1);

namespace vardumper\promptdb\services;

interface ChatGPTInterface
{
    public function getSQL(string $driverName, string $driverVersion, string $prompt, bool $cache = true): string;
}
