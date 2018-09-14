# App and Platform Engineer Project 

## Add to Cart Events

### Changes Made

1. Documentation from [this source](https://www.mageplaza.com/magento-2-module-development/magento-2-events.html) was used to understand Magento's event driven architecture and an observer was implemented to catch the `checkout_cart_add_product_complete` event dispatched when a user adds a product to their cart. 

2. The changes were made to the file `app/code/Pixlee/Pixlee/etc/events.xml` by adding the following xml snippet:

```xml
<event name="checkout_cart_add_product_complete">
  <observer name="add_product_cart" instance="Pixlee\Pixlee\Observer\AddToCartObserver" />
</event>

```
3. When the above event was dispatched, this would call the `AddToCartObserver` in the `app/code/Pixlee/Pixlee/Observer/AddToCartObserver.php` file to execute:
    - Initialize the Pixlee API (`initializePixleeAPI` in `app/code/Pixlee/Pixlee/Helper/Data.php`)
    - Initialize and get data needed for the analytics webhook (`initializeAddToCartData` in `app/code/Pixlee/Pixlee/Helper/Data.php`)
    - POST the data to the analytics endpoint, using the `addToCartAnalytics` funtion and existing `postToAPI` available in `app/code/Pixlee/Pixlee/Helper/Pixlee.php`

4. Magento system and debug logs were used to troubleshoot and make sure the endpoint responded with a 200 response while inspecting the request made to the endpoint.

Sample Debug message: 

```
[2018-09-14 12:43:22] main.INFO: [Pixlee] :: AddToCart: pushing product data to analytics endpoint [] []
[2018-09-14 12:43:22] main.DEBUG: * In initializeAddToCartData [] []
[2018-09-14 12:43:22] main.DEBUG: * In addToCartAnalytics [] []
[2018-09-14 12:43:22] main.DEBUG: *** In postToAPI [] []
[2018-09-14 12:43:22] main.DEBUG: With this URI: /analytics?api_key=MagentoTestAccessToken [] []
[2018-09-14 12:43:22] main.DEBUG: Hitting URL: https://takehomemagento.herokuapp.com/analytics?api_key=MagentoTestAccessToken [] []
[2018-09-14 12:43:22] main.DEBUG: With payload: {"product_id":67,"price":52,"quantity":1,"currency":"USD"} [] []
[2018-09-14 12:43:22] main.DEBUG: Got response: received [] []
```

## Categories Ingestion

### Changes Made

1. Since I was not able to get the `Export Products to Pixlee` to work and no logs were available, I used the 'add to cart event' set up in the previous example to test out the `getCategories` function.

2. The `CategoryFactory` Model was included in the Data.php file to get the names of categories by category ID.

3. `getCategories($product)` was implemented in Data.php file

4. Using the debug logs, a sample serialized output for the function was generated as follows:


```json
[
    {
        "category_id": "15",
        "category_name": "Hoodies & Sweatshirts"
    },
    {
        "category_id": "36",
        "category_name": "Eco Friendly"
    },
    {
        "category_id": "2",
        "category_name": "Default Category"
    }
]

```

