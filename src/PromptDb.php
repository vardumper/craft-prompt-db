<?php

namespace vardumper\promptdb;

use Craft;
use craft\base\Model;
use craft\base\Plugin;
use craft\events\RegisterComponentTypesEvent;
use craft\services\Utilities;
use Dallgoot\Yaml\Yaml;
use OpenAI;
use vardumper\promptdb\models\Settings;
use vardumper\promptdb\services\ChatGPTService;
use vardumper\promptdb\services\DBSchemaService;
use vardumper\promptdb\utilities\Utility;
use yii\base\Event;

/**
 * Prompt DB plugin
 *
 * @method static PromptDb getInstance()
 * @method Settings getSettings()
 * @author Erik Pöhler <info@erikpoehler.com>
 * @copyright Erik Pöhler
 * @license https://craftcms.github.io/license/ Craft License
 * @property-read ChatGPT $chatGPT
 * @property ChatGTP $vhatGPT;
 * @property-read ChatGPTService $chatGPTService
 */
class PromptDb extends Plugin
{
    public string $schemaVersion = '1.0.0';
    public bool $hasCpSettings = true;
    public ?string $changelogUrl = 'https://raw.githubusercontent.com/vardumper/craft-prompt-db/main/CHANGELOG.md';
    public ?string $downloadUrl = 'https://github.com/vardumper/craft-prompt-db/archive/main.zip';
    public ?string $documentationUrl = 'https://github.com/vardumper/craft-prompt-db/blob/main/README.md';

    public static function config(): array
    {
        return [
            'components' => [
                'chatGPTService' => ChatGPTService::class,
                'dbSchemaService' => DBSchemaService::class,
            ],
        ];
    }

    public function init(): void
    {
        parent::init();

        if (!function_exists('yaml_emit')) {
            Craft::error('The yaml extension is required for this plugin to work.', __METHOD__);
            // throw new MissingYamlExtensionException('The yaml extension is required for this plugin to work.');
        }

        Craft::setAlias('@vardumper/prompt-db', $this->getBasePath());

        $yaml = null;
        if (!function_exists('yaml_emit')) {
            $yaml = new Yaml();
        }

        $this->setComponents([
            'chatGPTService' => function () {
                $user = !empty($this->getSettings()->user) ? $this->getSettings()->user : '';
                $openAi = OpenAI::client($this->getSettings()->apiKey);
                return new ChatGPTService($openAi, $user);
            },
            'dbSchemaService' => new DBSchemaService($yaml),
        ]);

        // Defer most setup tasks until Craft is fully initialized
        Craft::$app->onInit(function () {
            $this->attachEventHandlers();
            // ...
        });

        // Register our query utility.
        Event::on(
            Utilities::class,
            Utilities::EVENT_REGISTER_UTILITY_TYPES,
            function (RegisterComponentTypesEvent $event) {
                $event->types[] = Utility::class;
            }
        );
    }

    protected function createSettingsModel(): ?Model
    {
        return Craft::createObject(Settings::class);
    }

    protected function settingsHtml(): ?string
    {
        return \Craft::$app->getView()->renderTemplate(
            'prompt-db/_settings.twig',
            ['settings' => $this->getSettings()]
        );
    }

    private function attachEventHandlers(): void
    {
        // Register event handlers here ...
        // (see https://craftcms.com/docs/4.x/extend/events.html to get started)
    }

    public static function getCachePath(): string
    {
        $path = Craft::$app->getPath()->getStoragePath() . DIRECTORY_SEPARATOR . 'prompt-db' . DIRECTORY_SEPARATOR;
        if (!is_dir($path)) {
            mkdir($path, 0777, true);
        }
        return $path;
    }
}
