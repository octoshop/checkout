<?php namespace Octoshop\Checkout;

use Lang;
use Mail;

class Confirmation
{
    protected $group;

    protected $template;

    protected $recipient;

    protected $data = [];

    public function forGroup($group)
    {
        if (!in_array($group, ['admin', 'customer'])) {
            throw new ApplicationException(
                sprintf(Lang::get('octoshop.checkout::lang.mail.invalid_group'), $group)
            );
        }

        $this->group = $group;

        $this->template = $group == 'admin'
            ? 'octoshop.checkout::mail.checkoutconfirm_admin'
            : 'octoshop.checkout::mail.checkoutconfirm_customer';

        return $this;
    }

    public function send()
    {
        return Mail::send(
            $this->require('template'),
            $data = $this->getData(),
            function($message) use ($data) {
                extract($data);

                $customerName = $customer->name.' '.$customer->surname;

                if ($this->group == 'admin') {
                    $message->to($email, $name);
                    $message->replyTo($customer->email, $customerName);
                } else {
                    $message->to($customer->email, $customerName);
                }
            }
        );
    }

    protected function getData()
    {
        $group = $this->require('group');
        $data = $this->data['global'];

        if (isset($this->data[$group])) {
            $data = array_merge($data, $this->data[$group]);
        }

        return $data;
    }

    protected function require($var)
    {
        if (!$this->$var) {
            throw new ApplicationException(
                Lang::get('octoshop.checkout::lang.mail.missing_param')
            );
        }

        return $this->$var;
    }

    public function with($group, array $data)
    {
        if (!array_key_exists($group, $this->data)) {
            $this->data[$group] = [];
        }

        $this->data[$group] = array_merge($this->data[$group], $data);

        return $this;
    }
}
