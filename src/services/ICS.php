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

namespace mostlyserious\calendarize\services;

use DateTime;
use craft\base\Component;
use mostlyserious\calendarize\models\CalendarizeModel;

/**
 * @author    Franco Valdes
 *
 * @since     1.0.0
 */
class ICS extends Component
{
    /**
     * Return the ICS url for a specific event
     *
     * @param  array $options
     * @return mixed
     */
    public function getUrl(CalendarizeModel $model, $options)
    {
        $params = http_build_query([
            'filename' => $filename = $options['filename'] ?? null,
            'ownerId' => $model->ownerId,
            'ownerSiteId' => $model->ownerSiteId,
            'fieldId' => $model->fieldId,
        ]);

        return '/actions/calendarize/default/make-ics?' . $params;
    }

    /**
     * Return the ICS for all events in the parent section
     *
     * @param  array $options
     * @return mixed
     */
    public function getCalendarIcsUrl(CalendarizeModel $model, $options)
    {
        $params = http_build_query([
            'sectionId' => $model->getOwner()->getSection()->id,
            'siteId' => $model->ownerSiteId,
            'fieldId' => $model->fieldId,
            'relatedTo' => $options['relatedTo'] ?? null,
            'filename' => $options['filename'] ?? null,
        ]);

        return '/actions/calendarize/default/make-section-ics?' . $params;
    }

    /**
     * Build the ICS document for a single event
     *
     * @return string
     */
    public function make(CalendarizeModel $model)
    {
        return "BEGIN:VCALENDAR\n" .
                "VERSION:2.0\n" .
                "PRODID:-//CALENDARIZE Craft //EN\n" .
                $this->_makeEvent($model) .
                "END:VCALENDAR\n";
    }

    /**
     * Build the ICS document for a list of events
     *
     * @param  CalendarizeModel[] $events
     * @return string
     */
    public function makeEvents($events)
    {
        $cal = "BEGIN:VCALENDAR\n" .
            "VERSION:2.0\n" .
            "PRODID:-//CALENDARIZE Craft //EN\n";

        foreach ($events as $event) {
            $cal .= $this->_makeEvent($event);
        }

        $cal .= "END:VCALENDAR\n";

        return $cal;
    }

    private function _makeEvent(CalendarizeModel $model)
    {
        $owner = $model->getOwner();
        $rule = $model->rrule()->getRRules()[0];

        $ics = "BEGIN:VEVENT\n";

        if ($model->startDate) {
            $ics .= $rule->rfcString() . "\n";
            $ics .= 'DTEND;TZID=' . ($model->endDate ?: $model->startDate)->getTimezone()->getName() . ':' . ($model->endDate ?: $model->startDate)->format('Ymd\THis') . "\n";
        }

        $ics .= 'SUMMARY:' . $this->_escapeString($owner->title) . "\n";
        $ics .= "DESCRIPTION:\n";
        $ics .= 'URL;VALUE=URI:' . str_replace(["\r", "\n"], '', (string) $owner->url) . "\n";
        $ics .= 'UID:calendarize-' . $model->fieldId . '-' . $model->ownerId . '-' . $model->ownerSiteId . "\n";

        if ($model->startDate) {
            $ics .= 'DTSTAMP:' . $this->_dateToCal() . "\n";
        }

        $ics .= "END:VEVENT\n";

        return $ics;
    }

    /**
     * Generate the specific date markup for a ics file
     *
     * @param  int    $timestamp Timestamp to be transformed
     * @return string
     */
    private function _dateToCal(?DateTime $dateTime = null)
    {
        if (!$dateTime) {
            $dateTime = new DateTime('now');
        }

        return $dateTime->setTimezone(new \DateTimeZone('UTC'))->format('Ymd\THis\Z');
    }

    /**
     * Escape a TEXT value per RFC 5545 section 3.3.11
     *
     * @param  string $string String to be escaped
     * @return string
     */
    private function _escapeString($string)
    {
        $string = str_replace('\\', '\\\\', (string) ($string ?? ''));
        $string = str_replace(["\r\n", "\r", "\n"], '\n', $string);

        return preg_replace('/([,;])/', '\\\$1', $string);
    }
}
