<?php

namespace vardumper\promptdb\models;

use craft\base\Model;

/**
 * Prompt DB settings
 */
class Settings extends Model
{
    /** @var string */
    public $apiKey = '';

    /** @var string */
    public $user = '';

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
                ['apiKey', 'user'],
                'string'
            ],
        ];
    }
}
