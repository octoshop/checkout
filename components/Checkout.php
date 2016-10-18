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
    public $items;

    protected $canContinue = true;

    protected $confirmation;

    protected $sendAdminConfirmation;

    protected $sendCustomerConfirmation;

    protected $recipientName;

    protected $recipientEmail;

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
        $this->config = $config = (object) ShopSetting::instance()->checkout;

        $this->sendAdminConfirmation = $config->send_admin_confirmation;
        $this->sendCustomerConfirmation = $config->send_customer_confirmation;
        $this->recipientName = $config->recipient_name;
        $this->recipientEmail = $config->recipient_email;

        $this->loadOrder();
    }

    public function onRun()
    {
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

    protected function loadConfirmation()
    {
        if (!$this->sendAdminConfirmation && !$this->sendCustomerConfirmation) {
            return;
        }

        $this->confirmation = new Confirmation;
        $customer = Auth::getUser();

        $this->confirmation->with('global', [
            'site' => BrandSetting::get('app_name'),
            'items' => Cart::content()->toArray(),
            'total' => Cart::total(),
        ])->with('admin', [
            'name' => $this->recipientName,
            'email' => $this->recipientEmail,
            'customer_name' => $customer->name.' '.$customer->surname,
            'customer_email' => Auth::getUser()->email,
        ])->with('customer', [
            'name' => $customer->name,
            'email' => $customer->email,
            'fullname' => $customer->name.' '.$customer->surname,
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
