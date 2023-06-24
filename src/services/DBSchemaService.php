<?php

declare(strict_types=1);

namespace vardumper\promptdb\services;

use Craft;
use vardumper\promptdb\PromptDb;
use yii\base\Component;

class DBSchemaService extends Component
{
    private string $cacheDir;

    public function __construct()
    {
        $this->cacheDir = PromptDb::getCachePath();
    }
    public function __invoke(): string
    {
        $schema = $this->getCacheSchema();
        if ($schema !== false) {
            return $schema;
        }

        $tablenames = array_values(Craft::$app->getDb()->createCommand('show tables')->queryAll(PDO::FETCH_COLUMN));
        asort($tablenames);
        $allTables = [];
        foreach ($tablenames as $table) {

            // filter out empty tables
            if (Craft::$app->getDb()->createCommand("SELECT COUNT(*) AS count FROM $table")->queryOne(PDO::FETCH_COLUMN) === 0) {
                continue;
            }

            // maybe filter out more cache-alike stuff
            if (in_array($table, ['sessions', 'revisions'])) {
                continue;
            }

            $tableInfo = ['name' => $table, 'columns' => []];
            $columns = Craft::$app->getDb()->createCommand(sprintf('DESCRIBE %s;', $table))->queryAll(PDO::FETCH_ASSOC);
            $cols = [];
            foreach ($columns as $column) {
                $cols[] = [
                    'name' => $column['Field'],
                    'type' => $column['Type'],
                ];
            }

            $tableInfo['columns'] = $cols;
            $allTables[] = $tableInfo;
        }
        $yaml = \yaml_emit($allTables);
        $this->setCacheSchema($yaml);
        return $yaml;
    }

    protected function getCacheSchema(): string|false
    {
        $filename =  sprintf("%s/schema.yaml", $this->cacheDir);
        if (file_exists($filename) && filemtime($filename) > time() - 60 * 60 * 24) {
            return file_get_contents($filename);
        }
        return false;
    }

    protected function setCacheSchema(string $yaml): bool
    {
        $filename = sprintf("%s/schema.yaml", $this->cacheDir);
        return file_put_contents($filename, $yaml, LOCK_EX) !== false;
    }
}
