# Emplifi UGC plugin for Magento 2 / Adobe Commerce

Use this Magento 2 extension to connect to Emplifi's UGC service. Compatible with Magento Open Source and Adobe Commerce, versions 2.3–2.4.

[Documentation](https://developers.pixlee.com/docs/magento-2)

* * *

## Installation Instructions

### Install using Composer (recommended)

Follow the Adobe documentation to [Install an extension](https://experienceleague.adobe.com/docs/commerce-operations/installation-guide/tutorials/extensions.html?lang=en)

Composer Package [pixlee/magento2](https://packagist.org/packages/pixlee/magento2)

1. Run these commands in your root Magento installation directory for composer install:

    ```bash
    composer require pixlee/magento2
    bin/magento module:enable Pixlee_Pixlee --clear-static-content
    bin/magento setup:upgrade
    bin/magento setup:di:compile
    bin/magento cache:clean
    ```

2. Configure the module to connect to your Emplifi UGC account. Please see the Setup section below.

### Install by copying files

1. Create a `code/Pixlee/Pixlee` directory in the `app` directory of your Magento installation.
2. Download the latest "Source code" from this page: [https://github.com/pixlee/magento2/releases](https://github.com/pixlee/magento2/releases)
3. Extract the file and copy the contents of the Pixlee_Pixlee directory into the `app/code/Pixlee/Pixlee` directory.
4. Run the following commands from your root Magento installation directory:

    ```bash
    bin/magento module:enable Pixlee_Pixlee --clear-static-content
    bin/magento setup:upgrade
    bin/magento setup:di:compile
    bin/magento cache:clean
    ```

5. Configure the module to connect to your Emplifi UGC account. Please see the Setup section below.

* * *

## Setup

### Configure the Emplifi UGC Magento 2 Module

Select **Stores** > **Configuration** on the main menu. Then select **Emplifi (Pixlee)** > **UGC** in the left navigation menu.

The Emplifi UGC account settings can be configured at the Website, Store, or Store view depending on account setup. For
each UGC account, select the desired cope in the scope (store view) dropdown menu and enter the account configurations.

1. Set **Enable** to *Yes*
2. Fill in **API Key**, **Private API Key**, and **Secret Key** with the API keys from your Emplifi Account. [Getting you API Keys](https://developers.pixlee.com/docs/getting-your-api-keys)
3. Click **Save Config** in the top right corner of the page to save your Account Settings.

### Exporting Products from Magento to Emplifi

#### Recurring Product Export Cron Job

The extension sets up a cron job that exports products to Emplifi daily. This will keep product
data up to date in Emplifi by exporting new product data and updated data for existing products to Emplifi.

#### Disable Product Export

The extension can be configured to disable product export. To disable product export, set the **Enable Nightly Export**
configuration option to *No*.

#### Change Product Export Run Time

By default, products will be exported daily at 3am UTC. If this time causes an issue, you can change the time that the
product export runs by updating the cron schedule in Magento.

1.  Create a copy of the extension's crontab.xml in your Magento 2 directory on the server in the app/code/Pixlee/Pixlee/etc directory.

2.  Update the <schedule> time to the time you want the export to run. To change the hour in the day at which the 
products are exported, change the `3` in the schedule tag to the hour you want the export to run. The example below will 
run the export at one minute after 5am UTC.

    ```xml
    <job name="pixlee_product_export" instance="Pixlee\Pixlee\Cron\ExportCron" method="execute">
        <schedule>1 5 * * *</schedule>
    </job>
    ```

#### Manually Run Product Export

Products can be exported to Emplifi when required by using the Export Products button.
In the extension configuration **Products** section, click the **Export Products to Emplifi** button to export all
products for the currently selected configuration scope to your Emplifi account. This process can be repeated for each scope.

> ### 🚧 Note
> If the export times out, ensure the **max_execution_time** configuration for Magento is set to at least 3600 seconds (1 hour).
Magento sets the value to 5 hours by default.

### Embedding PDP Widget on Product Pages

After creating a PDP Widget in Emplifi, adding the widget ID to the configuration will add the widget to the product details pages.

1. Log in to [your account](https://app.pixlee.com) and navigate to the [Publish Center](https://app.pixlee.com/app#publish).

2. Click the "Publish New PDP Display" button and configure the display per the [documentation](https://docs.emplifi.io/platform/latest/home/publish-a-product-description-page-display-pdp-).

3. After customizing, click the "Generate Embed Code" button and copy the value for **widgetId** from the snippet or get the ID from the Publish Center.

4. To enable the PDP widget, enter the **widgetId** from the previous step in the **PDP Widget ID** field under **Widget Settings**
in the Magento store configuration and click **Save Config**.

5. To **customize the placement** of the PDP widget, modify _catalog_product_view.xml_. And use the move tag to move the widget block.

    For example, you can create a new `view/frontend/layout/catalog_product_view.xml` file in a custom module in the 
    app/code directory and use the move tag to move the widget block.

    ```xml
    <move element="product.view.community_gallery" destination="content" after="product.info.main" />
    ```

### Embedding a CDP Widget on your Category Pages

The first three steps are exactly the same as embedding a PDP widget as they only involve getting a **widgetId** from the Control Panel.

1. Log in to [your account](https://app.pixlee.com) and navigate to the [Publish Center](https://app.pixlee.com/app#publish).

2. Click the "Publish New PDP Display" button and configure the display per the [documentation](https://docs.emplifi.io/platform/latest/home/publish-a-product-description-page-display-pdp-).
   > It is recommended that you leave the "Load Priority" setting to "Low priority" when customizing.

3. After customizing, click the "Generate Embed Code" button and copy the value for **widgetId** from the snippet or get the ID from the Publish Center.

4. To enable the CDP widget, enter the **widgetId** from the previous step in the **CDP Widget ID** field under **Widget Settings**
   in the Magento store configuration and click **Save Config**.

5. To **customize the placement** of the CDP widget, modify _catalog_product_view.xml_. And use the move tag to move the widget block.

   For example, you can create a new `view/frontend/layout/catalog_category_view.xml` file in a custom module in the
   app/code directory and use the move tag to move the widget block.

   ```xml
   <move element="product.view.community_gallery" destination="category.product.list.additional" before="-" />
   ```

* * *

## Testing

This module includes both unit and integration tests located in:

- `vendor/pixlee/magento2/Test/Unit`
- `vendor/pixlee/magento2/Test/Integration`

These tests can be executed using PHPUnit and the Magento 2 test framework.

---

### Unit Tests

Run unit tests with:

```bash
vendor/bin/phpunit -c phpunit.xml.dist vendor/pixlee/magento2/Test/Unit
```

---

### Integration Tests (Magento Test Framework)

Run integration tests with:

```bash
vendor/bin/phpunit -c dev/tests/integration/phpunit.xml.dist vendor/pixlee/magento2/Test/Integration
```

> Ensure your Magento integration test environment (database and configuration) is properly set up before running integration tests.

---

### Optional: Include module tests in your Magento test suite

To run Pixlee tests as part of your existing Magento integration test suite, add the following to your `dev/tests/integration/phpunit.xml.dist` (or a custom test suite configuration):

```xml
<testsuite name="Pixlee Module Tests">
    <directory>../../../vendor/pixlee/magento2/Test/Integration</directory>
    <directory>../../../vendor/pixlee/magento2/Test/Unit</directory>
</testsuite>
```

---

### Optional: Enable module for integration tests

Ensure the module is enabled in your integration test environment by adding it to:

`dev/tests/integration/etc/config-global.php`

```php
<?php

return [
    'TESTS_MODULES_ENABLED' => [
        'Pixlee_Pixlee' => true,
    ],
];
```

---

### Notes

- Integration tests require a configured Magento test database.
- Run all commands from the Magento root directory.
- Unit tests do not require Magento to be bootstrapped.

## Validation and Troubleshooting

To validate the extension setup, you can confirm:
- All the products exported
- API calls are successfully made to the Emplifi UGC API

### Product Export

Ensure the product export has run at least once. To run the export manually:

- Open your store Admin
- Navigate to **Stores > Configuration > Emplifi > UGC**
- Click the **Export Products to Emplifi** button in the Products section

Validate products updated in Emplifi
- Login to your [Emplifi](https://app.pixlee.com) account and navigate to [Products](https://app.pixlee.com/app#products) under the **Album** tab.
- Confirm the exported products are listed.
- Search for products to confirm they appear in your account with the correct data.

#### Troubleshooting Product Export

If the list is empty, or if you were not able to find a particular product, check the application and server logs for
errors. The extension logs to a separate log file **pixlee.log** which can be found in the application logs usually
located in **var/log** directory.

You can also check the PHP server logs. Some common errors are:
1. Lack of allocated memory for the server
    
    `Allowed memory size of 33554432 bytes exhausted (tried to allocate 43148176 bytes) in PHP`

   - To fix the issue, increase the allocated memory and run the product export job again.
2. Insufficient **max_execution_time**

    `Fatal error: Maximum execution time of 999 seconds exceeded in ...`

   - To fix the issue, increase the **max_execution_time** setting inside your **php.ini** file to at least 3600. And then try exporting the products again.

If you need further help troubleshooting, please email support@emplifi.io and attach relevant logs.

### API Validation

API calls are made to Emplifi UGC API when a customer adds something to their cart and when they buy something on your store. We need to make sure that these calls are being made at the right time.

1.  Open your favorite browser and open a product page of your store. And click **Add to Cart**.

2.  Open the **pixlee.log** file located in **var/log** using your favorite text editor and scroll down to the very end.

3.  There should be an entry beginning with **AddToCart**

4.  If you found the **AddToCart** calls, then your analytics were integrated correctly. If not, contact us at support@emplifi.io and attach the **pixlee.log** file with the email.

5.  Switch back to the browser and proceed to checkout to buy the product that you added to the cart previously. Use a test payment method for the checkout.

6.  When you reach the order confirmation page switch open the **pixlee.log** file again and ensure that you are viewing the latest copy of the file.

7.  This time look for log entries beginning with **CheckoutSuccess**

8.  If you do not see the **CheckoutSuccess** calls like in the screenshot, please contact us at support@emplifi.io and attach the **pixlee.log** file with the email.

> ### 🚧 Disclaimer: Mobile Analytics
>
> Based on the design of Magento2, user agents are not passed in the **add to cart** and **conversion** events. This means that
> there is no current ability to split between mobile and desktop conversion data.

### RequireJS error on pages containing widgets

To verify that you are encountering this issue, do the following:

- Open the developer tools for your browser and check for console errors.
- If you find an error like `Mismatched anonymous define() module` this is likely caused by adding scripts directly to
the page without using RequireJS.
- Refer to the documentation for [Embedding Widgets on your site](https://developers.pixlee.com/docs/magento-2#embedding-widgets-on-your-site)

To manually add a widget, use the **require()** function to add the widget embed code:
*   Add the **asset.pixlee.com** url to the array as the first parameter.
*   Move the JavaScript code from the **window.PixleeAsyncInit** as the **require** callback function.
*   Call the **Pixlee.resizeWidget()** at the end of the callback.
*   The new code should look similar to this:

    ```html
    <div id="pixlee_container"></div>
    <script type="text/javascript">
      require(['https://assets.pxlecdn.com/assets/pixlee_widget_1_0_0.js'], function() {
          Pixlee.init({apiKey:'YOUR_API_KEY'});
          Pixlee.addSimpleWidget({widgetId:YOUR_WIDGET_ID});
          Pixlee.resizeWidget();
        }
      );
    </script>
    ```
