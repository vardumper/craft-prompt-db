<?php

declare(strict_types=1);

namespace vardumper\promptdb\services\Exception;

use Exception;

class InvalidChatGPTException extends Exception implements ChatGPTException
{
}
