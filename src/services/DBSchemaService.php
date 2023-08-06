<?php

declare(strict_types=1);

namespace vardumper\promptdb\services;

use Craft;
use Dallgoot\Yaml\Yaml;
use vardumper\promptdb\PromptDb;
use yii\base\Component;

class DBSchemaService extends Component
{
    /**
     * do not include cache-alike tables in the schema
     * @var array
     */
    private const EXCLUDE_TABLES = ['sessions', 'revisions'];

    /**
     * where to cache the schema for subsequent requests
     * @var string
     */
    private string $cacheDir;

    /**
     * optional YAML implementation for when the yaml php extension is missing
     * @var ?Yaml $yaml
     */
    private ?Yaml $yaml = null;

    public function __construct(?Yaml $yaml = null)
    {
        $this->cacheDir = PromptDb::getCachePath();
        $this->yaml = $yaml;
    }

    public function __invoke(): string
    {
        $schema = $this->getCacheSchema();
        if ($schema) {
            return $schema;
        }

        $tablenames = array_values(Craft::$app->getDb()->createCommand('show tables')->queryAll(\PDO::FETCH_COLUMN));
        asort($tablenames);
        $allTables = [];
        foreach ($tablenames as $table) {

            // filter out empty tables
            $emptyTableArray = Craft::$app
                ->getDb()
                ->createCommand(sprintf('SELECT COUNT(*) AS count FROM %s', $table))
                ->queryOne(\PDO::FETCH_COLUMN);
            if (!$emptyTableArray || count(intval($emptyTableArray)) === 0) {
                continue;
            }

            // maybe filter out more cache-alike tables
            if (in_array($table, self::EXCLUDE_TABLES, true)) {
                continue;
            }

            $tableInfo = ['name' => $table, 'columns' => []];
            $columns = Craft::$app
                ->getDb()
                ->createCommand(sprintf('DESCRIBE %s;', $table))
                ->queryAll(\PDO::FETCH_ASSOC);

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
        $yaml = ($this->yaml instanceof Yaml) ? $this->yaml->dump($allTables) : \yaml_emit($allTables);
        $this->setCacheSchema($yaml);
        return $yaml;
    }

    /**
     * only create a YAML db schema once every 24 hours
     * @return string|null
     */
    protected function getCacheSchema(): ?string
    {
        $filename = sprintf("%s/schema.yaml", $this->cacheDir);
        if (file_exists($filename) && filemtime($filename) > time() - 60 * 60 * 24) {
            return file_get_contents($filename);
        }
        return null;
    }

    protected function setCacheSchema(string $yaml): bool
    {
        $filename = sprintf("%s/schema.yaml", $this->cacheDir);
        return file_put_contents($filename, $yaml, LOCK_EX) !== false;
    }
}
