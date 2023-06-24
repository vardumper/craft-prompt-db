<?php

/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license MIT
 */

namespace vardumper\promptdb\models;

use craft\web\AssetBundle;
use craft\web\assets\cp\CpAsset;
use craft\web\View;

/**
 * QueryAsset class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 2.0
 */
class Assets extends AssetBundle
{
    /**
     * @inheritdoc
     */
    public function init()
    {
        // define the path that your publishable resources live
        $this->sourcePath = '@vardumper/prompt-db/assets';

        // define the dependencies
        $this->depends = [
            CpAsset::class,
        ];

        $this->css = [
            'highlight.css',
        ];

        $this->js = [
            'scripts.min.js',
        ];

        parent::init();
    }

    /**
     * @inheritdoc
     */
    public function registerAssetFiles($view)
    {
        parent::registerAssetFiles($view);

        if ($view instanceof View) {
            $view->registerTranslations('prompt-db', [
                '1 result:',
                '{num} results:',
            ]);
        }
    }
}
