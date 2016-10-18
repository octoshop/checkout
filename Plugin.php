<?php namespace Octoshop\Checkout;

use Backend;
use Event;
use Octoshop\Core\Models\ShopSetting;
use System\Classes\PluginBase;
use System\Controllers\Settings;

class Plugin extends PluginBase
{
    public $require = ['Octoshop.Core'];

    public function pluginDetails()
    {
        return [
            'name' => 'octoshop.checkout::lang.plugin.name',
            'icon' => 'icon-shopping-cart',
            'author' => 'Dave Shoreman',
            'homepage' => 'http://octoshop.co/',
            'description' => 'octoshop.checkout::lang.plugin.description',
        ];
    }

    public function boot()
    {
        $this->extendBackendForm();
        $this->extendBackendMenu();

        $this->extendComponents();
        $this->extendModels();
    }

    protected function extendBackendForm()
    {
        Event::listen('backend.form.extendFields', function($form) {
            if (!$form->getController() instanceof Settings
             || !$form->model instanceof ShopSetting) {
                return;
            }

            $form->addTabFields($this->getShopSettings());
        });
    }

    protected function getShopSettings()
    {
        return [
            'checkout[send_customer_confirmation]' => [
                'label' => 'Send confirmation to customer',
                'type' => 'switch',
                'span' => 'left',
                'tab' => 'Checkout',
            ],
            'checkout[send_admin_confirmation]' => [
                'label' => 'Send confirmation to admin',
                'type' => 'switch',
                'span' => 'right',
                'tab' => 'Checkout',
            ],
            'checkout[recipient_name]' => [
                'label' => 'Recipient Name',
                'type' => 'text',
                'tab' => 'Checkout',
                'trigger' => [
                    'action' => 'show',
                    'condition' => 'checked',
                    'field' => 'checkout[send_admin_confirmation]',
                ],
            ],
            'checkout[recipient_email]' => [
                'label' => 'Recipient Address',
                'type' => 'text',
                'tab' => 'Checkout',
                'trigger' => [
                    'action' => 'show',
                    'condition' => 'checked',
                    'field' => 'checkout[send_admin_confirmation]',
                ],
            ],
        ];
    }

    public function extendBackendMenu()
    {
        Event::listen('backend.menu.extendItems', function($manager) {
            $manager->addSideMenuItems('Octoshop.Core', 'octoshop', [
                'orders' => [
                    'label'       => 'Orders',
                    'url'         => Backend::url('octoshop/checkout/orders'),
                    'icon'        => 'icon-gavel',
                    'order'       => 300,
                ],
            ]);
        });
    }

    public function extendComponents()
    {
        Event::listen('octoshop.core.extendComponents', function($plugin) {
            $plugin->addComponents([
                'Octoshop\Checkout\Components\Checkout' => 'shopCheckout',
            ]);
        });
    }

    protected function extendModels()
    {
        ShopSetting::extend(function($model) {
            $model->registerDefaults([
                'checkout' => (object) [
                    'send_admin_confirmation'    => false,
                    'send_customer_confirmation' => false,
                    'recipient_name'  => '',
                    'recipient_email' => '',
                ],
            ]);

            // TODO: Pretty sure there's a bool transformer trait we can use here
            $model->bindEvent('model.afterFetch', function() use ($model) {
                $model->value = (object) json_decode(str_replace(
                    ['"0"', '"1"'],
                    ['false', 'true'],
                    json_encode($model->value)
                ));
            });
        });
    }

    public function registerMailTemplates()
    {
        return [
            'octoshop.core::mail.checkoutconfirm_admin' => 'Order confirmation sent to admin users',
            'octoshop.core::mail.checkoutconfirm_customer' => 'Order confirmation sent to customers',
        ];
    }
}
