<?php

namespace Sixgweb\AttributizeLocation;

use App;
use October\Rain\Html\Helper as HtmlHelper;
use Sixgweb\Attributize\Models\Field;
use System\Classes\PluginBase;
use RainLab\Location\Models\State;
use RainLab\Location\Models\Country;
use RainLab\Location\Models\Setting;


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
        return Country::isEnabled()
            ->orderBy('is_pinned', 'desc')
            ->orderBy('name', 'asc')
            ->lists('name', 'code');
    }

    public static function getStateOptions($model, $field)
    {
        $code = null;
        if (isset($field->config['dependsOn']) && $field->config['dependsOn']) {

            $parts = HtmlHelper::nameToArray($field->config['dependsOn']);
            $key = end($parts);

            //arrayName will be set when in backend context
            if ($field->arrayName) {
                $post = $field->arrayName . '[' . implode('][', $parts) . ']';
            } else {
                $post = $field->config['dependsOn'];
            }

            $code = post($post, $model->field_values[$key] ?? null);
        }

        $country = Country::where('code', $code)->first();
        $countryId = $country ? $country->id : Setting::get('default_country');

        return State::whereCountryId($countryId)
            ->isEnabled()
            ->orderBy('name', 'asc')
            ->lists('name', 'code');
    }
}
