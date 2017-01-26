<?php namespace Octoshop\Checkout;

use Backend;
use Event;
use Octoshop\Core\Models\ShopSetting;
use Octoshop\Checkout\Models\Order;
use Octoshop\Checkout\Models\OrderItem;
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

    public function registerPermissions()
    {
        return [
            'octoshop.core.access_orders' => [
                'tab' => 'octoshop.core::lang.plugin.name',
                'label' => 'octoshop.checkout::lang.permissions.orders',
            ],
        ];
    }

    public function boot()
    {
        $this->extendBackendForm();
        $this->extendBackendMenu();

        $this->extendComponents();
        $this->extendModels();

        $this->handleOrderEvents();
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
                'label' => 'octoshop.checkout::lang.settings.send_customer_confirmation',
                'tab' => 'octoshop.checkout::lang.settings.tab',
                'type' => 'switch',
                'span' => 'left',
            ],
            'checkout[send_admin_confirmation]' => [
                'label' => 'octoshop.checkout::lang.settings.send_admin_confirmation',
                'tab' => 'octoshop.checkout::lang.settings.tab',
                'type' => 'switch',
                'span' => 'right',
            ],
            'checkout[recipient_name]' => [
                'label' => 'octoshop.checkout::lang.settings.recipient_name',
                'tab' => 'octoshop.checkout::lang.settings.tab',
                'type' => 'text',
                'trigger' => [
                    'action' => 'show',
                    'condition' => 'checked',
                    'field' => 'checkout[send_admin_confirmation]',
                ],
            ],
            'checkout[recipient_email]' => [
                'label' => 'octoshop.checkout::lang.settings.recipient_address',
                'tab' => 'octoshop.checkout::lang.settings.tab',
                'type' => 'text',
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
                    'label'       => 'octoshop.checkout::lang.orders.label',
                    'url'         => Backend::url('octoshop/checkout/orders'),
                    'icon'        => 'icon-gavel',
                    'order'       => 300,
                    'permissions' => ['octoshop.core.access_orders'],
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

    protected function handleOrderEvents()
    {
        Event::listen('cart.added', function($item) {
            if ($order = Order::getFromSession()) {
                $order->syncWithCartItem($item);
            }
        });

        Event::listen('cart.updated', function($item) {
            if ($order = Order::getFromSession()) {
                $order->syncWithCartItem($item);
            }
        });

        Event::listen('cart.removed', function($item) {
            $item = OrderItem::whereBasketRowId($item->rowId);

            if ($item = $item->first()) {
                $item->delete();
            }
        });
    }

    public function registerMailTemplates()
    {
        return [
            'octoshop.checkout::mail.checkoutconfirm_admin' => 'octoshop.checkout::lang.mail.admin_confirmation',
            'octoshop.checkout::mail.checkoutconfirm_customer' => 'octoshop.checkout::lang.mail.customer_confirmation',
        ];
    }
}
