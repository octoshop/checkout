<?php namespace Octoshop\Checkout\Components;

use Auth;
use Cart;
use Event;
use Lang;
use Session;
use Validator;
use Backend\Models\BrandSetting;
use October\Rain\Exception\ValidationException;
use Octoshop\AddressBook\Models\Address;
use Octoshop\Checkout\Confirmation;
use Octoshop\Checkout\Models\Order;
use Octoshop\Checkout\Models\OrderItem;
use Octoshop\Checkout\Models\OrderStatus;
use Octoshop\Core\Components\ComponentBase;
use Octoshop\Core\Models\ShopSetting;

class Checkout extends ComponentBase
{
    protected $config;

    protected $user;

    /**
     * @var array Custom checkout fields to save
     */
    protected $userFields = ['notes' => 'notes'];

    public $order;

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

        $this->user = Auth::getUser();
    }

    public function componentDetails()
    {
        return [
            'name'        => 'octoshop.checkout::lang.components.checkout.name',
            'description' => 'octoshop.checkout::lang.components.checkout.description',
        ];
    }

    public function defineProperties()
    {
        return [];
    }

    public function onRun() // or init?
    {
        if (!$this->user) {
            // An exception would be nice here, but it blocks
            // the RainLab.User plugin from redirecting to login
            return;
        }

        $this->prepareVars();
    }

    public function prepareVars()
    {
        $this->sendAdminConfirmation = $this->config->send_admin_confirmation;
        $this->sendCustomerConfirmation = $this->config->send_customer_confirmation;
        $this->recipientName = $this->config->recipient_name;
        $this->recipientEmail = $this->config->recipient_email;

        $this->setPageProp('order', $this->loadOrder());
    }

    protected function loadOrder()
    {
        if ($this->order instanceof Order) {
            return $this->order;
        }

        if ($order = Order::getFromSession()) {
            return $order;
        }

        return $this->createOrder();
    }

    protected function createOrder()
    {
        $basketItems = Cart::content();
        $orderItems = [];

        foreach ($basketItems as $item) {
            $orderItems[] = new OrderItem([
                'basket_row_id' => $item->rowId,
                'name' => $item->name,
                'quantity' => $item->qty,
                'price' => $item->price,
                'subtotal' => $item->qty * $item->price,
            ]);
        }

        $order = Order::createForUser(Auth::getUser());
        $order->items()->saveMany($orderItems);

        $order->total = Cart::total();

        Session::put('order', $order->uuid->string);

        return $order;
    }

    public function onSetOrderDetails()
    {
        $this->prepareVars();

        $rules = [];

        $data = Address::find(post('billing_address')) ?: post();
        $this->order->setBillingAddress($data);
        $rules += Address::getValidationRules('billing');

        $data = Address::find(post('shipping_address')) ?: post();
        $this->order->setShippingAddress($data);
        $rules += Address::getValidationRules('shipping');

        foreach ($this->userFields as $field => $column) {
            if (!($value = post($field))) {
                continue;
            }

            $this->order->$column = $value;
        }

        $validator = Validator::make($this->order->toArray(), $rules);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        $basketErrors = Cart::validate();

        if (count($basketErrors) > 0) {
            throw new ValidationException($basketErrors);
        }

        $this->order->save();
    }

    public function onConfirm()
    {
        $this->prepareVars();

        $basketErrors = Cart::validate();

        if (count($basketErrors) > 0) {
            throw new ValidationException($basketErrors);
        }

        $pending = OrderStatus::whereName('Pending')->first();
        $this->order->status()->associate($pending);

        if (!$this->order->save()) {
            throw new ValidationException(Lang::get('octoshop.checkout::lang.order.save_failed'));
        }

        $this->loadConfirmation();

        Event::fire('octoshop.checkout.success', [$this]);

        $this->sendConfirmations();

        Session::forget('order');
        Cart::destroy();
    }

    protected function loadConfirmation()
    {
        if (!$this->sendAdminConfirmation && !$this->sendCustomerConfirmation) {
            return;
        }

        $this->confirmation = new Confirmation;
        $customer = $this->order->user;

        $this->confirmation->with('global', [
            'site'  => BrandSetting::get('app_name'),
            'order' => $this->order,
            'customer' => $customer,
        ])->with('admin', [
            'name'  => $this->recipientName,
            'email' => $this->recipientEmail,
        ])->with('customer', [
        ]);
    }

    protected function sendConfirmations()
    {
        if ($this->sendAdminConfirmation) {
            $this->confirmation->forGroup('admin')->send();
        }

        if ($this->sendCustomerConfirmation) {
            $this->confirmation->forGroup('customer')->send();
        }
    }
}
