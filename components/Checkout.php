<?php namespace Octoshop\Checkout\Components;

use Auth;
use Cart;
use Event;
use Session;
use Backend\Models\BrandSetting;
use Octoshop\Core\Components\ComponentBase;
use Octoshop\Checkout\Models\Order;
use Octoshop\Checkout\Models\OrderItem;
use Octoshop\Core\Checkout\Confirmation;
use Octoshop\Core\Models\ShopSetting;

class Checkout extends ComponentBase
{
    protected $config;

    public $items;

    protected $canContinue = true;

    protected $confirmation;

    protected $sendAdminConfirmation;

    protected $sendCustomerConfirmation;

    protected $recipientName;

    protected $recipientEmail;

    /**
     * Load checkout config
     *
     * @todo Add support for guest checkout
     */
    public function init()
    {
        $this->config = (object) ShopSetting::get('checkout');
        $this->config->guestCanCheckout = false;
    }

    public function componentDetails()
    {
        return [
            'name'        => 'Checkout',
            'description' => 'Displays the checkout form on a page.',
        ];
    }

    public function defineProperties()
    {
        return [];
    }

    public function prepareVars()
    {
        $this->sendAdminConfirmation = $this->config->send_admin_confirmation;
        $this->sendCustomerConfirmation = $this->config->send_customer_confirmation;
        $this->recipientName = $this->config->recipient_name;
        $this->recipientEmail = $this->config->recipient_email;

        $this->loadOrder();
    }

    public function onRun()
    {
        if (!Auth::getUser() && !$this->config->guestCanCheckout) {
            // An exception would be nice here, but it blocks
            // the RainLab.User plugin from redirecting to login
            return;
        }

        $this->prepareVars();

        Event::fire('octoshop.checkoutInit', [$this]);

        if (!$this->canContinue) {
            // set error to flash or return it or whatever
        }

        $this->items = Cart::content();
    }

    protected function loadOrder($order = null)
    {
        if ($this->order instanceof Order) {
            return;
        }

        $this->setPageProp('order', Order::getFromSession() ?: $this->createOrder());
    }

    protected function createOrder()
    {
        $items = [];

        foreach (Cart::content() as $item) {
            $items[] = new OrderItem([
                'basket_row_id' => $item->rowId,
                'name' => $item->name,
                'quantity' => $item->qty,
                'price' => $item->price,
                'subtotal' => $item->qty * $item->price,
            ]);
        }

        $order = Order::createForUser(Auth::getUser());
        $order->items()->saveMany($items);

        Session::put('orderHash', $order->hash);

        return $order;
    }

    public function onCheckout()
    {
        $this->prepareVars();

        Event::fire('octoshop.checkoutProcess', [$this]);

        if (!$this->canContinue || !$this->updateOrder()) {
            if ($this->canContinue) {
                $this->abortReason = "Failed to save order details";
            }

            // set error to flash or return it or something
            Event::fire('octoshop.checkoutFailure', [$this]);
        }

        $this->loadConfirmation();

        Event::fire('octoshop.checkoutSuccess', [$this]);

        $this->sendConfirmations();

        Cart::destroy();
    }

    protected function updateOrder()
    {
        if (!post('billing_address')) {
            $this->order->setBillingAddress(post());
        }

        if (!post('shipping_address')) {
            $this->order->setShippingAddress(post());
        }

        return $this->order->save();
    }

    /**
     * @todo Update total to use Order model instead of Cart
     */
    protected function loadConfirmation()
    {
        if (!$this->sendAdminConfirmation && !$this->sendCustomerConfirmation) {
            return;
        }

        $this->confirmation = new Confirmation;
        $customer = Auth::getUser();


        $this->confirmation->with('global', [
            'site'  => BrandSetting::get('app_name'),
            'order' => $this->order,
            'total' => Cart::total(),
        ])->with('admin', [
            'name'  => $this->recipientName,
            'email' => $this->recipientEmail,
        ])->with('customer', [
        ]);
    }

    protected function sendConfirmations()
    {
        if ($this->sendAdminConfirmation) {
            $this->confirmation->forGroup('admin') ->send();
        }

        if ($this->sendCustomerConfirmation) {
            $this->confirmation ->forGroup('customer')->send();
        }
    }

    public function abort($reason)
    {
        $this->canContinue = false;
        $this->abortReason = $reason;

        return false;
    }
}
