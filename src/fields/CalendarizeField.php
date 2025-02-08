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

namespace mostlyserious\calendarize\fields;

use Craft;
use craft\base\Field;
use craft\i18n\Locale;
use craft\base\Element;
use craft\base\ElementInterface;
use craft\base\PreviewableFieldInterface;
use mostlyserious\calendarize\Calendarize;
use craft\elements\db\ElementQueryInterface;
use mostlyserious\calendarize\assetbundles\fieldbundle\FieldAssetBundle;

/**
 * @author    Franco Valdes
 *
 * @since     1.0.0
 */
class CalendarizeField extends Field implements PreviewableFieldInterface
{
    // Public Properties
    // =========================================================================

    /**
     * @var datetime
     */
    public $startDate;

    /**
     * @var datetime
     */
    public $endDate;

    /**
     * @var bool
     */
    public $repeats = false;

    /**
     * @var bool
     */
    public $allDay = false;

    /**
     * @var array
     */
    public $days = [];

    /**
     * @var string
     */
    public $endRepeat = null;

    /**
     * @var datetime
     */
    public $endRepeatDate = null;

    /**
     * @var array
     */
    public $exceptions = [];

    /**
     * @var array
     */
    public $timeChanges = [];

    /**
     * @var string
     */
    public $repeatType = null;

    /**
     * @var string
     */
    public $months = null;

    // Static Methods
    // =========================================================================

    /**
     * {@inheritdoc}
     */
    public static function displayName(): string
    {
        return Craft::t('calendarize', 'Calendarize');
    }

    /**
     * {@inheritdoc}
     */
    public static function hasContentColumn(): bool
    {
        return false;
    }

    // Public Methods
    // =========================================================================

    /**
     * {@inheritdoc}
     */
    public function rules(): array
    {
        $rules = parent::rules();

        return $rules;
    }

    /**
     * {@inheritdoc}
     */
    public function getElementValidationRules(): array
    {
        return [
            [CalendarizeValidator::class, 'on' => Element::SCENARIO_LIVE],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function normalizeValue(mixed $value, ?\craft\base\ElementInterface $element = null): mixed
    {
        return Calendarize::$plugin->calendar->getField($this, $element, $value);
    }

    /**
     * {@inheritdoc}
     */
    public function modifyElementsQuery(ElementQueryInterface $query, mixed $value): void
    {
        // For whatever reason, this function can be
        // run BEFORE Calendarize has been initialized
        if (!Calendarize::$plugin) {
            return;
        }

        Calendarize::$plugin->calendar->modifyElementsQuery($query, $value);

    }

    /**
     * {@inheritdoc}
     */
    public function afterElementSave(ElementInterface $element, bool $isNew): void
    {
        Calendarize::$plugin->calendar->saveField($this, $element);
        parent::afterElementSave($element, $isNew);
    }

    /**
     * {@inheritdoc}
     */
    public function getPreviewHtml(mixed $value, ElementInterface $element): string
    {
        if (empty($value->startDate) && empty($value->endDate)) {
            return '-';
        }

        $hr = $value->readable(['locale' => Craft::$app->locale->id]);
        $html = "<span title=\"{$hr}\">";

        if ($value->hasPassed()) {
            $html .= '<b>' . Craft::t('calendarize', 'Last Occurrence') . ':</b>';
        } else {
            $html .= '<b>' . Craft::t('calendarize', 'Next Occurrence') . ':</b>';
        }

        $html .= '<br/>' . $value->next()->format('l, m/d/Y @ h:i:s a');

        return $html;
    }

    /**
     * {@inheritdoc}
     */
    public function getInputHtml(mixed $value, ?\craft\base\ElementInterface $element = null): string
    {
        // Register our asset bundle
        $view = Craft::$app->getView();

        // Get our id and namespace
        $id = $view->formatInputId($this->handle);
        $namespacedId = $view->namespaceInputId($id);
        $locale = Craft::$app->getLocale()->id;
        $dateFormat = Craft::$app->getLocale()->getDateFormat(Locale::LENGTH_MEDIUM);

        $view->registerAssetBundle(FieldAssetBundle::class);
        $view->registerJs("new Calendarize('{$namespacedId}', '{$locale}', '{$dateFormat}');");

        // Render the input template
        return $view->renderTemplate(
            'calendarize/_components/fields/CalendarizeField_input',
            [
                'name' => $this->handle,
                'value' => $value,
                'field' => $this,
                'id' => $id,
                'namespacedId' => $namespacedId,
            ]
        );
    }
}
