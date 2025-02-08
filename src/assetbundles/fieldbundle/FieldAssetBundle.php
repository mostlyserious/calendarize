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

namespace mostlyserious\calendarize\assetbundles\fieldbundle;

use craft\web\AssetBundle;
use craft\web\assets\cp\CpAsset;

/**
 * @author    Franco Valdes
 *
 * @since     1.0.0
 */
class FieldAssetBundle extends AssetBundle
{
    // Public Methods
    // =========================================================================

    /**
     * {@inheritdoc}
     */
    public function init()
    {
        $this->sourcePath = '@mostlyserious/calendarize/assetbundles/resources/dist';

        $this->depends = [
            CpAsset::class,
        ];

        $this->js = [
            'js/vendor.js',
            'js/main.js',
        ];

        $this->css = [
            'css/app.css',
        ];

        parent::init();
    }
}
