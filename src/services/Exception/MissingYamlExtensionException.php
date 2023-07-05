<?php

declare(strict_types=1);

namespace vardumper\promptdb\services\Exception;

use Exception;

class MissingYamlExtensionException extends Exception implements ChatGPTException
{
}
