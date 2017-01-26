<?php

return [
    'plugin' => [
        'name' => 'Octoshop Checkout',
        'description' => 'Adds checkout abilities to Octoshop Core.',
    ],
    'components' => [
        'checkout' => [
            'name' => 'Checkout',
            'description' => 'Displays the checkout form on a page.',
        ],
    ],
    'mail' => [
        'admin_confirmation' => 'Order confirmation sent to admin users',
        'customer_confirmation' => 'Order confirmation send to customers',
        'invalid_group' => 'Invalid group "%s".',
        'missing_param' =>'Missing one or more required parameters. Make sure you call forGroup() before sending.',
    ],
    'order' => [
        'label' => 'Order',
        'tabs' => [
            'items' => 'Items',
            'billing' => 'Billing Address',
            'shipping' => 'Shipping Address',
        ],
        'address' => [
            'first_name' => 'First Name',
            'last_name' => 'Last Name',
            'line1' => 'Address Line 1',
            'line2' => 'Address Line 2',
            'town' => 'Town',
            'region' => 'Region',
            'postcode' => 'Post Code',
            'country' => 'Country',
        ],
        'id' => 'Order #',
        'customer' => 'Customer',
        'email' => 'Email',
        'status' => 'Current Status',
        'shipping_option' => 'Shipping Option',
        'total_value' => 'Total Value',
        'for_x_items' => 'for %s items',
        'created_at' => 'Date Placed',
        'updated_at' => 'Last Updated',
        'saving' => 'Saving Order...',
        'deleting' => 'Deleting Order...',
        'delete_confirm' => 'Do you really want to delete this order?',
        'save_failed' => 'Failed to save order.',
        'return_to_orders' => 'Return to orders list',
    ],
    'orderitem' => [
        'label' => 'Order Item',
        'name' => 'Product',
        'quantity' => 'Quantity',
        'price' => 'Unit Price',
        'subtotal' => 'Subtotal',
    ],
    'orderstatus' => [
        'draft' => 'Draft',
        'pending' => 'Pending',
        'complete' => 'Complete',
        'cancelled' => 'Cancelled',
        'abandoned' => 'Abandoned',
    ],
    'orders' => [
        'label' => 'Orders',
        'manage' => 'Manage Orders',
        'all_orders' => 'All Orders',
        'this_month' => 'Orders this Month',
        'previous' => 'previous',
    ],
    'permissions' => [
        'orders' => 'Manage shop orders',
    ],
    'settings' => [
        'tab' => 'Checkout',
        'send_customer_confirmation' => 'Send confirmation to customer',
        'send_admin_confirmation' => 'Send confirmation to admin',
        'recipient_name' => 'Recipient Name',
        'recipient_email' => 'Recipient Address',
    ],
];
