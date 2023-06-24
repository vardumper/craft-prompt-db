<?php

declare(strict_types=1);

namespace vardumper\promptdb\controllers;

use Craft;
use craft\web\Controller;
use DOMDocument;
use vardumper\promptdb\PromptDb;
use yii\data\ArrayDataProvider;
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
        // die('xxx');

        $prompt = Craft::$app->getRequest()->getRequiredBodyParam('prompt');

        try {
            $driverName = Craft::$app->getDb()->getDriverName();
            $driverVersion = Craft::$app->getDb()->getServerVersion();
            if (empty($prompt)) {
                return $this->asJson([
                    'success' => false,
                    'error' => 'No prompt provided',
                ]);
            }

            $schema = (PromptDb::getInstance()->dbSchemaService)();
            $sql = (PromptDb::getInstance()->chatGPTService)($driverName, $driverVersion, $schema, $prompt);

            if (self::contains($sql, ['update', 'alter', 'drop', 'truncate', 'insert', 'delete', 'replace', 'set'], true)) {
                Craft::error($sql);
                return $this->asJson([
                    'success' => false,
                    'error' => 'This plugin does not allow you to modify the database.',
                ]);
            }
            $result = Craft::$app->getDb()->createCommand($sql)->queryAll();

            $dataProvider = new ArrayDataProvider([
                'allModels' => $result,
                'sort' => false,
            ]);

            $grid = GridView::widget([
                'emptyCell' => '',
                'emptyText' => Craft::t('prompt-db', 'Empty result set'),
                'dataProvider' => $dataProvider,
            ]);

            $grid = $this->craftifyYiiGrid($grid);
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

    public static function contains($haystack, $needles, $ignoreCase = false)
    {
        if ($ignoreCase) {
            $haystack = mb_strtolower($haystack);
            $needles = array_map('mb_strtolower', $needles);
        }
        foreach ((array) $needles as $needle) {
            if ($needle !== '' && mb_strpos($haystack, $needle) !== false) {
                return true;
            }
        }

        return false;
    }

    private function craftifyYiiGrid(string $html): string
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
