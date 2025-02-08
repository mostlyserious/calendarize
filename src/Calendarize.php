<?php

/**
 * Calendarize plugin for Craft CMS 3.x
 *
 * Calendar element types
 *
 * @link      https://union.co
 *
 * @copyright Copyright (c) 2018 Franco Valdes
 */

namespace mostlyserious\calendarize;

use Craft;
use craft\web\View;
use yii\base\Event;
use craft\base\Plugin;
use craft\services\Fields;
use craft\web\twig\variables\CraftVariable;
use mostlyserious\calendarize\services\ICS;
use craft\events\RegisterTemplateRootsEvent;
use craft\events\RegisterComponentTypesEvent;
use mostlyserious\calendarize\models\Settings;
use mostlyserious\calendarize\fields\CalendarizeField;
use mostlyserious\calendarize\services\CalendarizeService;
use mostlyserious\calendarize\variables\CalendarizeVariable;

/**
 * Class Calendarize
 *
 * @author    Franco Valdes
 *
 * @since     1.0.0
 *
 * @property CalendarizeServiceService $calendarizeService
 */
class Calendarize extends Plugin
{
    // Static Properties
    // =========================================================================

    /**
     * @var Calendarize
     */
    public static $plugin;

    // Public Properties
    // =========================================================================

    /**
     * @var bool
     */
    public $hasSettings = false;

    public bool $hasCpSection = false;

    public ?string $changelogUrl = 'https://raw.githubusercontent.com/mostlyserious/calendarize/master/CHANGELOG.md';

    public string $schemaVersion = '1.3.0';

    // Public Methods
    // =========================================================================

    /**
     * {@inheritdoc}
     */
    public function init(): void
    {
        parent::init();
        self::$plugin = $this;

        $this->controllerNamespace = 'mostlyserious\calendarize\controllers';

        $this->setComponents([
            'calendar' => CalendarizeService::class,
            'ics' => ICS::class,
        ]);

        // Base template directory
        Event::on(
            View::class,
            View::EVENT_REGISTER_CP_TEMPLATE_ROOTS,
            function (RegisterTemplateRootsEvent $e) {
                if (is_dir($baseDir = $this->getBasePath() . DIRECTORY_SEPARATOR . 'templates')) {
                    $e->roots[$this->id] = $baseDir;
                }
            }
        );

        Event::on(
            Fields::class,
            Fields::EVENT_REGISTER_FIELD_TYPES,
            function (RegisterComponentTypesEvent $event) {
                $event->types[] = CalendarizeField::class;
            }
        );

        Event::on(
            CraftVariable::class,
            CraftVariable::EVENT_INIT,
            function (Event $event) {
                /** @var CraftVariable $variable */
                $variable = $event->sender;
                $variable->set('calendarize', CalendarizeVariable::class);
            }
        );

        Craft::info(
            Craft::t(
                'calendarize',
                '{name} plugin loaded',
                ['name' => $this->name]
            ),
            __METHOD__
        );
    }

    public function afterInstall(): void
    {
        parent::afterInstall();
    }

    // Protected Methods
    // =========================================================================

    /**
     * {@inheritdoc}
     */
    protected function createSettingsModel(): ?\craft\base\Model
    {
        return new Settings();
    }

    /**
     * {@inheritdoc}
     */
    protected function settingsHtml(): ?string
    {
        return Craft::$app->view->renderTemplate(
            'calendarize/settings',
            [
                'settings' => $this->getSettings(),
            ]
        );
    }
}
