# signalise/magento2-plugin

Magento2 Plugin that push data to Signalise, data wil be pushed in a queue 
and a consumer will be pushing the data to Signalise.

This plugin can also push orders or a specific order to signalise through the ``signalise:push-order {order_id}`` command.

### Configuration
![img.png](img.png)
### Events
We currently have 2 configurable events that will send data to signalise when it gets triggered. 

- ``sales_order_place_after``
- ``sales_order_payment_pay``
