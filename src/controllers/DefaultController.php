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
use DOMDocument;
use OpenAI;
use PDO;
use vardumper\promptdb\PromptDb;
use yii\data\ActiveDataProvider;
use yii\data\ArrayDataProvider;
use yii\db\Query;
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
                $schema = '';
                $tablenames = array_values(Craft::$app->getDb()->createCommand('show tables')->queryAll(PDO::FETCH_COLUMN));
                asort($tablenames);
                foreach ($tablenames as $table) {
                    // filter out empty tables
                    if (Craft::$app->getDb()->createCommand("SELECT COUNT(*) AS count FROM $table")->queryOne(PDO::FETCH_COLUMN) === 0) {
                        continue;
                    }

                    if (in_array($table, ['sessions', 'revisions'])) {
                        continue;
                    }

                    $tableschema = Craft::$app->getDb()->createCommand(sprintf('show create table %s;', $table))->queryOne();
                    $schema .= $tableschema['Create Table'] . ";\n";
                }
                $driverName = Craft::$app->getDb()->getDriverName();
                $driverVersion = Craft::$app->getDb()->getServerVersion();
                $sql = (PromptDb::getInstance()->demoService)($driverName, $driverVersion, $schema, $prompt);
                $result = Craft::$app->getDb()->createCommand($sql)->queryAll();
                $dataProvider = new ArrayDataProvider([
                    'allModels' => $result,
                    // @todo It's too much additional AJAX complexity for now. Add pagination later
                    // 'pagination' => [
                    //     'pageSize' => $per_page,
                    //     'page' => $page - 1,
                    // ],
                    'sort' => false,
                ]);
                $grid = GridView::widget([
                    'emptyCell' => '',
                    'emptyText' => Craft::t('prompt-db', 'Empty result set'),
                    'dataProvider' => $dataProvider,
                ]);

                $grid = $this->craftifyYiiGrid($grid);
            }
        } catch (\Exception $e) {
            return $this->asJson([
                'success' => false,
                'error' => $e->__toString(),
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

    public function craftifyYiiGrid(string $html): string
    {
        libxml_use_internal_errors(true);
        $dom = new DOMDocument("1.0", "UTF-8");
        $dom->preserveWhiteSpace = false;
        $dom->validateOnParse = false;
        $dom->strictErrorChecking = false;
        $dom->formatOutput = false;
        $dom->loadHTML('<?xml encoding="utf-8" ?>' . $html, LIBXML_HTML_NOIMPLIED);
        libxml_clear_errors();
        libxml_use_internal_errors(false);
        $tables = $dom->getElementsByTagName('table');

        // add table class
        if ($tables->length) {
            /* @var DOMNode $table */
            $table = $tables->item(0);
            // $table->setAttribute('id', 'event-attendees-grid');
            $table->setAttribute('class', 'data fullwidth');
        }

        // remove prev/next links
        $pagination = $dom->getElementsByTagName('ul');
        if ($pagination->length) {
            /* @var DOMNode $pagination */
            $pagination = $pagination->item(0);
            $pagination->removeChild($pagination->firstChild); //prev
            $pagination->removeChild($pagination->lastChild); //next
        }

        // add btn class to pagination links
        $xpath = new \DOMXPath($dom);
        $links = $xpath->query('//ul[@class="pagination"]/li/a');
        if ($links->length) {
            foreach ($links as $link) {
                $active = strpos($link->parentNode->getAttribute('class'), 'active') !== false ? 'active' : '';
                $link->setAttribute('class', 'btn ' . $active);
                $link->setAttribute('href', '#');
            }
        }
        return $dom->saveXML($dom->documentElement);
    }
}
