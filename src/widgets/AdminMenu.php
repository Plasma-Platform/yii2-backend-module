<?php

namespace indigerd\adminmodule\widgets;

use Yii;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;
use yii\helpers\Url;
use yii\widgets\Menu;
use indigerd\adminmodule\AdminMenuInterface;
use indigerd\adminmodule\helpers\Access;

class AdminMenu extends Menu
{
    /**
     * @var string
     */
    public $linkTemplate = "<a href=\"{url}\">\n{icon}\n{label}\n{right-icon}\n{badge}</a>";
    /**
     * @var string
     */
    public $labelTemplate = "{icon}\n{label}\n{badge}";

    /**
     * @var string
     */
    public $badgeTag = 'span';
    /**
     * @var string
     */
    public $badgeClass = 'label pull-right';
    /**
     * @var string
     */
    public $badgeBgClass;

    /**
     * @var string
     */
    public $parentRightIcon = '<i class="fa fa-angle-left pull-right"></i>';

    /**
     * @inheritdoc
     */
    protected function renderItem($item)
    {
        $item['badgeOptions'] = isset($item['badgeOptions']) ? $item['badgeOptions'] : [];

        if (!ArrayHelper::getValue($item, 'badgeOptions.class')) {
            $bg = isset($item['badgeBgClass']) ? $item['badgeBgClass'] : $this->badgeBgClass;
            $item['badgeOptions']['class'] = $this->badgeClass . ' ' . $bg;
        }

        if (isset($item['items']) && !isset($item['right-icon'])) {
            $item['right-icon'] = $this->parentRightIcon;
        }

        if (isset($item['url'])) {
            $template = ArrayHelper::getValue($item, 'template', $this->linkTemplate);

            return strtr($template, [
                '{badge}'=> isset($item['badge'])
                    ? Html::tag('small', $item['badge'], $item['badgeOptions'])
                    : '',
                '{icon}'=>isset($item['icon']) ? $item['icon'] : '',
                '{right-icon}'=>isset($item['right-icon']) ? $item['right-icon'] : '',
                '{url}' => Url::to($item['url']),
                '{label}' => $item['label'],
            ]);
        } else {
            $template = ArrayHelper::getValue($item, 'template', $this->labelTemplate);

            return strtr($template, [
                '{badge}'=> isset($item['badge'])
                    ? Html::tag('small', $item['badge'], $item['badgeOptions'])
                    : '',
                '{icon}'=>isset($item['icon']) ? $item['icon'] : '',
                '{right-icon}'=>isset($item['right-icon']) ? $item['right-icon'] : '',
                '{label}' => $item['label'],
            ]);
        }
    }

    public static function widget($c = [])
    {
        $modules       = Yii::$app->getModules();
        $items         = [];
        foreach ($modules as $module) {
            if ($module instanceof AdminMenuInterface) {
                $items = array_merge($items, $module->getAdminMenu());
            }
        }
        foreach ($items as $key => $item) {
            if (isset($item['group'])) {
                $group = $item['group'];
                unset($item['group']);
                foreach ($c['items'] as $k => $i) {
                    if (isset($i['items']) and $i['label'] == $group) {
                        $c['items'][$k]['items'][] = $item;
                        unset($items[$key]);
                    }
                }
            }
            if (isset($item['section'])) {
                $section = $item['section'];
                unset($item['section']);
                foreach ($c['items'] as $k => $i) {
                    if (
                        isset($i['options']['class']) and
                        $i['options']['class'] == 'header' and
                        $i['label'] == $section
                    ) {
                        array_splice( $c['items'], $k + 1, 0, [$item]);
                        unset($items[$key]);
                    }
                }
                if (isset($items[$key])) {
                    $item = [
                        'label'   => $section,
                        'options' => ['class' => 'header']
                    ];
                    $c['items'] = array_merge([$item, $items[$key]], $c['items']);
                    unset($items[$key]);
                }
            }
        }
        /* maybe implement insert before and after ????? */
        //print_r($c);exit;
        $c['items'] = array_merge($items, $c['items']);
        foreach ($c['items'] as $k => $i) {
            if (!isset($i['visible'])) {
                $visible = false;
                if (!empty($i['items'])) {
                    foreach ($i['items'] as $childKey => $child) {
                        if (isset($child['url']) and is_array($child['url']) and !isset($child['visible'])) {
                            $childVisible = (Yii::$app->user->can('administrator') or Access::checkPermission($child['url']));
                            if ($childVisible) {
                                $visible = true;
                            }
                            $c['items'][$k]['items'][$childKey]['visible'] = $childVisible;
                        }
                    }
                } elseif (isset($i['url']) and is_array($i['url'])) {
                    $visible = (Yii::$app->user->can('administrator') or Access::checkPermission($i['url']));
                } else {
                    $visible = true;
                }
                $c['items'][$k]['visible'] = $visible;
            }
        }
        return parent::widget($c);
    }
}
