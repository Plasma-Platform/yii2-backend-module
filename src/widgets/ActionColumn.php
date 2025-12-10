<?php

namespace indigerd\adminmodule\widgets;

use Yii;
use yii\grid\ActionColumn as BaseActionColumn;
use indigerd\adminmodule\helpers\Access;

class ActionColumn extends BaseActionColumn
{
    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();
        $this->initButtonsVisibility();
    }

    protected function initButtonsVisibility()
    {
        foreach ($this->buttons as $name => $button) {
            $this->initButtonVisibility($name);
        }
    }

    protected function initButtonVisibility($name)
    {
        $route = [implode('/', [
            Yii::$app->controller->getUniqueId(),
            $name
        ])];
        $this->visibleButtons[$name] = (Yii::$app->user->can('administrator') or Access::checkPermission($route));
    }
}
