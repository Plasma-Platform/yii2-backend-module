<?php

namespace indigerd\adminmodule;

use Yii;
use yii\base\Application;
use yii\base\BootstrapInterface;
use yii\base\InvalidConfigException;
use yii\web\AssetBundle;
use indigerd\migrationaware\MigrationAwareModule;

class AdminAwareModule extends MigrationAwareModule implements AdminMenuInterface, BootstrapInterface
{
    public $mode = 'backend';

    protected $modes = ['backend', 'frontend', 'api', 'console'];

    public $assetBundles = [];

    public $adminMenu = [];

    public $urlRules = [];

    public $registerComponents = [];

    public $requiredComponents = [];

    public $translations = [];

    protected static $registeredId;

    public function getAdminMenu()
    {
        return $this->adminMenu;
    }

    public function getNamespace()
    {
        return implode('\\', array_slice(explode('\\', get_class($this)), 0, -1));
    }

    public function getAlias()
    {
        return '@module-' . $this->id;
    }

    public function bootstrap($app)
    {
        if (!in_array($this->mode, $this->modes)) {
            throw new InvalidConfigException('Unsupported mode: ' . $this->mode . ' for module: ' . get_class($this));
        }
        Yii::setAlias($this->getAlias(), $this->getBasePath());
        foreach ($this->registerComponents as $componentId => $definition) {
            if (!$app->has($componentId)) {
                $app->set($componentId, $definition);
            }
        }
        $this->registerAssets($app);
    }

    public function registerAssets(Application $app)
    {
        if (!empty($this->assetBundles[$this->mode])) {
            foreach ($this->assetBundles[$this->mode] as $asset) {
                /** @var AssetBundle $asset */
                $asset::register($app->view);
            }
        }
    }

    public function init()
    {
        parent::init();
        $this
            ->setControllerNamespace()
            ->checkRequiredComponents()
            ->addUrlRules()
            ->loadTranslations();
    }

    /** @return $this */
    public function setControllerNamespace()
    {
        $this->controllerNamespace = $this->getNamespace() . '\controllers\\' . $this->mode;
        if (Yii::$app instanceof \yii\console\Application) {
            $this->controllerNamespace = $this->getNamespace() . '\controllers\console';
        }
        return $this;
    }

    /** @return $this */
    public function checkRequiredComponents()
    {
        foreach ($this->requiredComponents as $component) {
            if (!Yii::$app->has($component)) {
                throw new InvalidConfigException(
                    sprintf('The required component "%s" is not registered in the configuration file', $component)
                );
            }
        }
        return $this;
    }

    /** @return $this */
    public function addUrlRules()
    {
        if (!empty($this->urlRules)) {
            Yii::$app->urlManager->addRules($this->urlRules, true);
        }
        return $this;
    }

    /** @return $this */
    public function loadTranslations()
    {
        if (!empty($this->translations)) {
            Yii::$app->i18n->translations['modules/' . $this->id . '/*'] = [
                'class'            => 'yii\i18n\PhpMessageSource',
                'sourceLanguage'   => 'en-US',
                'forceTranslation' => true,
                'basePath'         => $this->getAlias() . '/messages',
                'fileMap'          => $this->translations
            ];
        }
        return $this;
    }

    public static function t($message, array $params = [])
    {
        if (null === static::$registeredId) {
            foreach (Yii::$app->getModules() as $module) {
                if (get_class($module) === static::class) {
                    static::$registeredId = $module->id;
                }
            }
        }
        return Yii::t('modules/' . static::$registeredId, $message, $params);
    }
}
