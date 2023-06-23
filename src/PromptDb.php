<?php

namespace vardumper\promptdb;

use Craft;
use craft\events\RegisterComponentTypesEvent;
use craft\services\Utilities;
use yii\base\Event;
use craft\base\Model;
use craft\base\Plugin;
use vardumper\promptdb\models\Settings;
use vardumper\promptdb\services\ChatGPTInterface;
use vardumper\promptdb\utilities\Utility;

/**
 * Prompt DB plugin
 *
 * @method static PromptDb getInstance()
 * @method Settings getSettings()
 * @author Erik Pöhler <info@erikpoehler.com>
 * @copyright Erik Pöhler
 * @license https://craftcms.github.io/license/ Craft License
 */
class PromptDb extends Plugin
{
    public string $schemaVersion = '1.0.0';
    public bool $hasCpSettings = true;
    public ?string $changelogUrl = 'https://raw.githubusercontent.com/vardumper/craft-prompt-db/main/CHANGELOG.md';
    public ?string $downloadUrl  = 'https://github.com/vardumper/craft-prompt-db/archive/main.zip';
    public ?string $documentationUrl = 'https://github.com/vardumper/craft-prompt-db/blob/main/README.md';

    public static function config(): array
    {
        return [
            'components' => [
                'promptDbChatGPT' => ChatGPTInterface::class,
            ],
        ];
    }

    public function init(): void
    {
        parent::init();

        Craft::setAlias('@vardumper/prompt-db', $this->getBasePath());

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

    // public function getSettingsResponse(): mixed
    // {
    //     // Redirect to our settings page
    //     Craft::$app->controller->redirect(
    //         UrlHelper::cpUrl('prompt-db/settings')
    //     );

    //     return null;
    // }

    private function attachEventHandlers(): void
    {
        // Register event handlers here ...
        // (see https://craftcms.com/docs/4.x/extend/events.html to get started)
    }
}
