<?php

namespace indigerd\adminmodule\helpers;

use Yii;

class Access
{
    public static function checkPermission($route)
    {
        //$route[0] - is the route, $route[1] - is the associated parameters
        $routes = static::createPartRoutes($route);
        $params = [];
        if (isset($route[1])) {
            $params = $route[1];
        }
        foreach ($routes as $routeVariant) {
            if (Yii::$app->user->can($routeVariant, $params)) {
                return true;
            }
        }
        return false;
    }

    protected static function createPartRoutes($route)
    {
        //$route[0] - is the route, $route[1] - is the associated parameters
        $routePathTmp = explode('/', trim($route[0], '/'));
        $result = [];
        $routeVariant = array_shift($routePathTmp);
        $result[] = $routeVariant;
        foreach ($routePathTmp as $routePart) {
            $routeVariant .= '/' . $routePart;
            $result[] = $routeVariant;
        }
        return $result;
    }
}
