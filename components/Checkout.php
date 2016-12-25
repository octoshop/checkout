<?php namespace Octoshop\Checkout\Components;

use Auth;
use Cart;
use Event;
use Session;
use Validator;
use Backend\Models\BrandSetting;
use October\Rain\Exception\ValidationException;
use Octoshop\AddressBook\Models\Address;
use Octoshop\Core\Components\ComponentBase;
use Octoshop\Checkout\Models\Order;
use Octoshop\Checkout\Models\OrderItem;
use Octoshop\Checkout\Models\OrderStatus;
use Octoshop\Core\Checkout\Confirmation;
use Octoshop\Core\Models\ShopSetting;

class Checkout extends ComponentBase
{
    protected $config;

    protected $user;

    /**
     * @var array Custom checkout fields to save
     */
    protected $userFields = ['notes' => 'notes'];

    protected $order;

    public function init()
    {
        $this->config = (object) ShopSetting::get('checkout');

        $this->user = Auth::getUser();
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
        $this->order->save();

        Session::forget('order');
        Cart::destroy();
    }
}
