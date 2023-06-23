<?php

namespace vardumper\promptdb\services;

use Craft;
use yii\base\Component;

/**
 * Demo Service service
 */
class DemoService extends Component
{
    public function getSQL(): string
    {
        return 'SELECT * FROM `users` WHERE `admin` = 1';
    }
}
