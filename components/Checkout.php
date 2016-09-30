<?php namespace Octoshop\Checkout\Components;

use Auth;
use Cart;
use Event;
use Backend\Models\BrandSetting;
use Cms\Classes\ComponentBase;
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
            'name'        => '',
            'description' => '',
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

    public function onCheckout()
    {
        $this->prepareVars();

        Event::fire('octoshop.checkoutProcess', [$this]);

        if (!$this->canContinue) {
            // set error to flash or return it or something
            Event::fire('octoshop.checkoutFailure', [$this]);
        }

        $this->loadConfirmation();

        Event::fire('octoshop.checkoutSuccess', [$this]);

        $this->sendConfirmations();

        Cart::destroy();
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
