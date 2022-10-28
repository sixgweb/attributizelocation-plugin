<?php

namespace Sixgweb\AttributizeLocation;

use App;
use October\Rain\Html\Helper as HtmlHelper;
use Sixgweb\Attributize\Models\Field;
use System\Classes\PluginBase;

/**
 * Plugin Information File
 */
class Plugin extends PluginBase
{

    public $require = [
        'Sixgweb.Attributize',
        'RainLab.Location',
    ];

    /**
     * Returns information about this plugin.
     *
     * @return array
     */
    public function pluginDetails()
    {
        return [
            'name'        => 'AttributizeLocation',
            'description' => 'Attributize RinabLab Location',
            'author'      => 'Sixgweb',
            'icon'        => 'icon-home'
        ];
    }

    /**
     * Boot method, called right before the request route.
     *
     * @return void
     */
    public function boot()
    {
        $this->extendField();
    }

    protected function extendField()
    {
        Field::extend(function ($model) {
            $model->bindEvent('sixgweb.attributize.field.getOptionsOptions', function (&$options) {
                $options['Sixgweb\AttributizeLocation\Plugin::getCountryOptions'] = 'Countries';
                $options['Sixgweb\AttributizeLocation\Plugin::getStateOptions'] = 'States';
            });
        });
    }

    public static function getCountryOptions()
    {
        return \RainLab\Location\Models\Country::isEnabled()
            ->orderBy('is_pinned', 'desc')
            ->orderBy('name', 'asc')
            ->lists('name', 'code');
    }

    public static function getStateOptions($model, $field)
    {
        $code = null;
        if (isset($field->config['dependsOn']) && $field->config['dependsOn']) {

            //arrayName will be set when in backend context
            if ($field->arrayName) {
                $post = $field->arrayName . '[' . implode('][', HtmlHelper::nameToArray($field->config['dependsOn'])) . ']';
                $key = 'field_values_' . $field->config['dependsOn'];
            } else {
                $post = $field->config['dependsOn'];
                $key = str_replace(
                    ['[', ']'],
                    ['_', ''],
                    $field->config['dependsOn']
                );
            }

            $code = post($post, $model->{$key});
        }

        $country = \RainLab\Location\Models\Country::where('code', $code)->first();
        $countryId = $country ? $country->id : \RainLab\Location\Models\Setting::get('default_country');

        return \RainLab\Location\Models\State::whereCountryId($countryId)
            ->isEnabled()
            ->orderBy('name', 'asc')
            ->lists('name', 'code');
    }
}
