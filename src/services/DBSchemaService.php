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
            $rowCount = Craft::$app
                ->getDb()
                ->createCommand(sprintf('SELECT COUNT(*) AS count FROM %s', $table))
                ->queryOne(\PDO::FETCH_COLUMN);
            if (is_int($rowCount) && $rowCount === 0 || $rowCount === false) {
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
            file_put_contents($this->cacheDir . '/test' . $table . '.txt', var_export($columns, true));

            $foreignKeys = Craft::$app->getDb()->createCommand(sprintf('
SELECT
  `TABLE_SCHEMA`,
  `TABLE_NAME`,
  `COLUMN_NAME`,
  `REFERENCED_TABLE_SCHEMA`,
  `REFERENCED_TABLE_NAME`,
  `REFERENCED_COLUMN_NAME`
FROM
  `INFORMATION_SCHEMA`.`KEY_COLUMN_USAGE`
WHERE
  `TABLE_SCHEMA` = SCHEMA()
  AND `TABLE_NAME` = %s
  AND `REFERENCED_TABLE_NAME` IS NOT NULL;', "'" . $table . "'"))->queryAll();

            file_put_contents($this->cacheDir . '/keys' . $table . '.txt', var_export($foreignKeys, true));

            foreach ($columns as $column) {
                // var_dump($tableInfo);
                // var_dump($column);
                // exit;

                $tmp = [
                    'name' => $column['Field'],
                    'type' => $column['Type'],
                ];

                // not null?
                $tmp['nullable'] = $column['Null'] === "YES" ? true : false;
                // primary key?
                if ($column['Key'] === "PRI") {
                    $tmp['primary_key'] = true;
                }
                // foreign keys
                foreach ($foreignKeys as $key) {
                    if ($key['COLUMN_NAME'] === $column['Field']) {
                        $tmp['foreign_key'] = [
                            'references' => $key['REFERENCED_TABLE_NAME'],
                            'on_column' => $key['REFERENCED_COLUMN_NAME'],
                        ];
                    }
                }
                $cols[] = $tmp;
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
