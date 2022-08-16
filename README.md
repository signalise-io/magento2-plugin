# signalise/magento2-plugin

Magento2 Plugin that push data to Signalise, data wil be pushed in a queue 
and a consumer will be pushing the data to Signalise.

This plugin can also push orders to signalise through the ``signalise:push-order {order_id}`` command.

### Events
Currently, we have 2 configurable events. We have observers that listen to the events
and push data to the Signalise queue when the events get triggered.

- ``sales_order_place_after``
- ``sales_order_payment_pay``
