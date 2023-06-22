<?php

namespace vardumper\promptdb\models;

use Craft;
use craft\base\Model;

/**
 * Prompt DB settings
 */
class Settings extends Model
{
    /** @var string */
    public $apiKey = '';

    /**
     * @return array
     */
    public function rules(): array
    {
        return [
            [
                ['apiKey'],
                'required'
            ],
            [
                ['apiKey'],
                'string'
            ],
        ];
    }
}
