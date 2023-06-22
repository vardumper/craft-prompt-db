<?php

declare(strict_types=1);
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license MIT
 */

namespace vardumper\promptdb\controllers;

use Craft;
use craft\web\Controller;

/**
 * Query controller class
 */
class DefaultController extends Controller
{
    /**
     * For executing the database query.
     *
     * @return \yii\web\Response
     */
    public function actionExecute()
    {
        $this->requirePermission('utility:prompt-db');
        $this->requireAcceptsJson();

        $prompt = Craft::$app->getRequest()->getRequiredBodyParam('prompt');

        try {
            $result = [];
            if ($prompt) {
                $schema = Craft::$app->getDb()->getSchema();
                $driver = Craft::$app->getDb()->getDriverName();
                // $createTableSyntax = Craft::$app->getDb()->getTableSchema()
                // $createTableSyntax = Craft::$app->getDb()->createCommand('SHOW CREATE TABLE ' . $schema->quoteTableName($prompt))->queryAll();
                $result = Craft::$app->getDb()->createCommand($sql)->queryAll();
            }
        } catch (\Exception $e) {
            return $this->asJson([
                'success' => false,
                'error' => $e->getMessage(),
            ]);
        }

        return $this->asJson([
            'success' => true,
            'result' => $result,
            'sql' => $sql,
            'formattedTotal' => Craft::$app->getFormatter()->asInteger(count($result)),
        ]);
    }
}
