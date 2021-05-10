<?php
/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2020 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace humhub\modules\twofa\assets;

use yii\web\AssetBundle;
use yii\web\View;

class Assets extends AssetBundle
{
    /**
     * @inheritDoc
     */
    public $sourcePath = '@twofa/resources';

    /**
     * @inheritDoc
     */
    public $js = [
        'js/humhub.twofa.js',
        'js/qrcode.min.js',
    ];

    /**
     * @inheritDoc
     */
    public $jsOptions = ['position' => View::POS_END];

    /**
     * @inheritDoc
     */
    public $publishOptions = [
        'forceCopy' => false
    ];
}
