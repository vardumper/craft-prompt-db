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
use vardumper\promptdb\PromptDb;
use yii\data\ActiveDataProvider;
use yii\grid\GridView;

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
                $driverName = Craft::$app->getDb()->getDriverName();
                $driverVersion = Craft::$app->getDb()->getServerVersion();

                $sql = PromptDb::getInstance()->demoService->getSQL($driverName, $driverVersion, $schema, $prompt);
                // $createTableSyntax = Craft::$app->getDb()->getTableSchema()
                // $createTableSyntax = Craft::$app->getDb()->createCommand('SHOW CREATE TABLE ' . $schema->quoteTableName($prompt))->queryAll();
                $result = Craft::$app->getDb()->createCommand($sql)->queryAll();
                $query = yii\db\Query
                $dataProvider = new ActiveDataProvider([
                    'query' => $result,
                    // @todo It's too much additional AJAX complexity for now. Add pagination later
                    // 'pagination' => [
                    //     'pageSize' => $per_page,
                    //     'page' => $page - 1,
                    // ],
                    'sort' => false,
                ]);
                $grid = GridView::widget([
                    'emptyCell' => '',
                    'emptyText' => Craft::t('site', 'event.noRecords'),
                    'dataProvider' => $dataProvider,
                    'columns' => []
                ]);
            }
        } catch (\Exception $e) {
            return $this->asJson([
                'success' => false,
                'error' => $e->getMessage(),
            ]);
        }

        return $this->asJson([
            'success' => true,
            'grid' => $grid,
            'resultCount' => $dataProvider->getTotalCount(),
            'sql' => $sql,
            'formattedTotal' => Craft::$app->getFormatter()->asInteger(count($result)),
        ]);
    }
}
