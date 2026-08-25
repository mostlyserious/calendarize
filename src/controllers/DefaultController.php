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

namespace mostlyserious\calendarize\controllers;

use Craft;
use craft\elements\Entry;
use craft\web\Controller;
use craft\records\Section;
use yii\web\NotFoundHttpException;
use craft\records\Field as FieldRecord;
use mostlyserious\calendarize\Calendarize;
use mostlyserious\calendarize\models\CalendarizeModel;
use mostlyserious\calendarize\records\CalendarizeRecord;

/**
 * @author    Franco Valdes
 *
 * @since     1.0.0
 */
class DefaultController extends Controller
{
    // Protected Properties
    // =========================================================================

    /**
     * @var bool|array Allows anonymous access to this controller's actions.
     *                 The actions must be in 'kebab-case'
     */
    protected array|int|bool $allowAnonymous = ['make-ics', 'make-section-ics'];

    // Public Methods
    // =========================================================================

    /**
     * Download an ICS file for a single event.
     *
     * @return mixed
     */
    public function actionMakeIcs(int $ownerId, int $ownerSiteId, int $fieldId)
    {
        $record = CalendarizeRecord::findOne(
            [
                'ownerId' => $ownerId,
                'ownerSiteId' => $ownerSiteId,
                'fieldId' => $fieldId,
            ]
        );

        if (!$record) {
            $this->handleMissingIcsRequest($ownerId, $ownerSiteId, $fieldId);
        }

        $owner = $record->getOwner()->one();

        if (!$owner) {
            $this->handleMissingIcsRequest($ownerId, $ownerSiteId, $fieldId);
        }

        $element = $owner->type::find()
            ->id($owner->id)
            ->one();

        if (!$element) {
            $this->handleMissingIcsRequest($ownerId, $ownerSiteId, $fieldId);
        }

        $model = new CalendarizeModel($element, $record->getAttributes());
        $ics = Calendarize::$plugin->ics->make($model);

        $response = Craft::$app->getResponse();

        return $response->sendFile($ics, null, ['inline' => true]);
    }

    /**
     * Download an ICS file for all events in a section.
     *
     * @return mixed
     */
    public function actionMakeSectionIcs(int $sectionId, int $siteId, int $fieldId, $relatedTo = null, $filename = null)
    {
        $field = FieldRecord::findOne($fieldId);
        $section = Section::findOne($sectionId);

        if (!$field || !$section) {
            Craft::warning(
                sprintf(
                    'Invalid section ICS request for sectionId=%d fieldId=%d url=%s',
                    $sectionId,
                    $fieldId,
                    Craft::$app->request->absoluteUrl
                ),
                __METHOD__
            );

            throw new NotFoundHttpException('Calendar not found.');
        }

        $fieldHandle = $field->handle;

        $entries = Entry::find()
            ->sectionId($sectionId)
            ->siteId($siteId)
            ->relatedTo($relatedTo)
            ->all();

        $events = array_reduce($entries, function ($carry, $entry) use ($fieldHandle) {
            if ($event = $entry->$fieldHandle) {
                if ($event->startDate && $event->endDate) {
                    $carry[] = $event;
                }
            }

            return $carry;
        }, []);

        if (empty($events)) {
            throw new NotFoundHttpException('No calendar events found.');
        }

        $ics = Calendarize::$plugin->ics->makeEvents($events, $filename);
        $response = Craft::$app->getResponse();

        return $response->sendFile($ics, null, ['inline' => true]);
    }

    private function handleMissingIcsRequest(int $ownerId, int $ownerSiteId, int $fieldId): never
    {
        Craft::warning(
            sprintf(
                'Invalid ICS request for ownerId=%d ownerSiteId=%d fieldId=%d url=%s',
                $ownerId,
                $ownerSiteId,
                $fieldId,
                Craft::$app->request->absoluteUrl
            ),
            __METHOD__
        );

        throw new NotFoundHttpException('Calendar event not found.');
    }
}
