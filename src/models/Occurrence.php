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

namespace mostlyserious\calendarize\models;

use DateTime;
use DateInterval;
use ReflectionClass;
use craft\base\Element;

class Occurrence
{
    // Public Properties
    // =========================================================================

    /**
     * @var Element
     */
    public $element;

    /**
     * @var string
     */
    public $next;

    /**
     * @var string
     */
    public $start;

    /**
     * @var string
     */
    public $end;

    public function __construct(Element $element, DateTime $next, DateInterval|int $duration)
    {
        $this->element = $element;
        $this->next = $next;

        // start and end date
        $this->start = $next;

        // end date, preserving the wall-clock duration across DST transitions
        $end = clone $next;
        $this->end = $duration instanceof DateInterval
            ? $end->add($duration)
            : $end->modify($duration . ' seconds');
    }

    /**
     * Fall back to element attributes
     */
    public function __call($name, $args = [])
    {
        // backwards compatibility
        if (in_array($name, get_class_methods(DateTime::class))) {
            return $this->next->{$name}(...$args);
        }

        if (!isset($this->{$name})) {
            return $this->element->{$name};
        }

        return $this->{$name};
    }

    public function __toString()
    {
        return $this->next->format('U');
    }

    public function getType(): string
    {
        return (new ReflectionClass($this->element))->getShortName();
    }
}
