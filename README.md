# Emplifi UGC plugin for Magento 2 / Adobe Commerce

To view these docs online, navigate to:
https://developers.pixlee.com/docs/magento-2

Use this Magento 2 extension to connect to Emplifi's UGC service. Compatible with Magento Open Source and Adobe Commerce, versions 2.3 - 2.4.
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
4. Run following commands from your root Magento installation directory:

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

![](https://files.readme.io/b27deee-ApiSettings.png "04_02_magento2_configuration.png")

1. Set **Enable** to *Yes*
2. Fill in **API Key**, **Private API Key**, and **Secret Key** with the API keys from your Emplifi Account. [Getting you API Keys](https://developers.pixlee.com/docs/getting-your-api-keys)
3. Click **Save Config** in the top right corner of the page to save your Account Settings.

### Exporting Products from Magento to Emplifi

#### Recurring Product Export Cron Job

The extension sets up a cron job that exports products to Emplifi on a daily basis. This will keep product
data up-to-date in Emplifi by exporting new product data and updated data for existing products to Emplifi.

#### Change Product Export Run Time

By default, products will be exported daily at 3am UTC. If this time causes an issue, you can change the time that the
product export runs by updating the cron schedule in Magento.

1.  Create a copy of the extension's crontab.xml in your Magento 2 directory on the server in the app/code/Pixlee/Pixlee/etc directory.

2.  Update the <schedule> time to the time you want the export to run. To change the hour in the day at which the 
products are exported, change the 3 in the schedule tag to the hour you want the export to run. The example below will 
run the export at one minute after 5am UTC.

    ```xml
    <job name="export_cronjob" instance="Pixlee\Pixlee\Cron\ExportCron" method="execute">
        <schedule>1 5 * * *</schedule>
    </job>
    ```

#### Manually Run Product Export

Products can be exported to Emplifi when required by using the Export Products button.
In the extension configuration **Products** section, click the **Export Products to Emplifi** button to export all
products for the currently selected configuration scope to your Emplifi account. This process can be repeated for each scope.

> ### 🚧 Note
> If the export times out, ensure the **max_execution_time** configuration for Magento must is set to at least 3600 seconds (1 hour).
> Magento sets the value to 5 hours by default.

### Embedding PDP Widget on Product Pages

After creating a PDP Widget in Emplifi, adding the widget ID to the configuration will add the widget to the product details pages.

1. Log in to [your account](https://app.pixlee.com) and navigate to the [Publish Center](https://app.pixlee.com/app#publish).

2. Click the "Publish New PDP Display" button and configure the display per the [documentation](https://docs.emplifi.io/platform/latest/home/publish-a-product-description-page-display-pdp-).
NOTE - It is recommended that you leave the "Load Priority" setting to "Low priority" when customizing.

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
   NOTE - It is recommended that you leave the "Load Priority" setting to "Low priority" when customizing.

3. After customizing, click the "Generate Embed Code" button and copy the value for **widgetId** from the snippet or get the ID from the Publish Center.

4. To enable the CDP widget, enter the **widgetId** from the previous step in the **CDP Widget ID** field under **Widget Settings**
   in the Magento store configuration and click **Save Config**.

5. To **customize the placement** of the PDP widget, modify _catalog_product_view.xml_. And use the move tag to move the widget block.

   For example, you can create a new `view/frontend/layout/catalog_category_view.xml` file in a custom module in the
   app/code directory and use the move tag to move the widget block.

   ```xml
   <move element="product.view.community_gallery" destination="category.product.list.additional" before="-" />
   ```

* * *

## Testing and Troubleshooting

To test that everything was implemented correctly, we need to check two things -

1.  Were all the products exported?
2.  Are API calls being successfully made to the Emplifi UGC API?

### Were all the products exported?

Before testing, please make sure that the Product Export job has at least run once. If you have already run the exports once, skip to step 3.

1.  In order to run it manually. Open Admin Panel > Stores > Configuration > Emplifi > UGC. Press the **Export Products to Emplifi** button to start the exports.

2.  Login to your [Emplifi](https://app.pixlee.com) account and navigate to [Products](https://app.pixlee.com/app#products) under the **Album** tab.

3.  You should see a list of products on this page.

    ![](https://files.readme.io/9546c5f-01_64_demandware_products.png "01_64_demandware_products.png")

4.  Try searching for a few products on this page that you know exist in your catalog.

5.  In case the list is empty or you were not able to search for a particular product, proceed to the next step. Otherwise, proceed straight to the next section [Are API calls being successfully made to the Emplifi UGC API?](#are-api-calls-being-successfully-made-to-the-emplifi-ugc-api)

6.  There can be several causes of failure to export products so first, we need to find out the exact cause of the failure. The Product Exports job logs the progress and all exceptions to the server logs. So the next step for us is to get the both **Magento server logs** and the **PHP (Apache or equivalent)** logs.

7.  The Magento server logs are usually located in **var/log** so navigate to that location and look for the file named **pixlee.log**.

8.  For the PHP logs location, it depends on your setup. For example, if you're using Apache the logs should be in **$Apache Root$/logs**. We're looking for the file named **php_error.log** in this directory.

9.  One of the most common issues that we encounter is a lack of allocated memory to the Magento or PHP/Apache server. Open the **php_error.log** file using your favorite text editor and search for a log entry similar to the following line

    Allowed memory size of 33554432 bytes exhausted (tried to allocate 43148176 bytes) in PHP

10.  If you found a similar looking error, please increase the allocated memory and run the product export job again.

11.  Another common issue we encounter is a low setting for the **max_execution_time**. Look for a log entry similar to the following

    Fatal error: Maximum execution time of 999 seconds exceeded in ...

12.  If you found a similar log entry please increase the **max_execution_time** setting inside your **php.ini** file to at least 3600. And then try exporting the products again.

13.  If at this point you're still not able to see any products exported to Emplifi, please contact us at support@emplifi.io and attach the both **pixlee.log** and **php_error.log** with the email.

### Are API calls being successfully made to the Emplifi UGC API?

API calls are made to Emplifi UGC API when a customer adds something to their cart and when they buy something on your store. We need to make sure that these calls are being made at the right time.

1.  Open your favorite browser and open a product page of your store. And click **Add to Cart**.

2.  Open the **pixlee.log** file located in **var/log** using your favorite text editor and scroll down to the very end.

3.  There should be an entry beginning with **AddToCart**

    ![](https://files.readme.io/0abe8fa-Screen_Shot_2019-10-10_at_7.36.19_PM.png "Screen Shot 2019-10-10 at 7.36.19 PM.png")

4.  If you found the **AddToCart** calls then your analytics were integrated correctly. If not, contact us at support@emplifi.io and attach the **pixlee.log** file with the email.

5.  Switch back to the browser and proceed to checkout and buy the product that you added to cart previously. Use a test payment method for the checkout.

6.  When you reach the order confirmation page switch open the **pixlee.log** file again and ensure that you are viewing the latest copy of the file.

7.  This time look for log entries beginning with **CheckoutSuccess**

    ![](https://files.readme.io/f02da02-Screen_Shot_2019-10-10_at_7.47.20_PM.png "Screen Shot 2019-10-10 at 7.47.20 PM.png")

8.  If you do not see the **CheckoutSuccess** calls like in the screenshot, please contact us at support@emplifi.io and attach the **pixlee.log** file with the email.

> ### 🚧 Disclaimer: Mobile Analytics
>
> Based on the design of Magento2, user agents are not passed in the add to cart and conversion events. This means that
> there is no current ability to split between mobile and desktop conversion data.

### RequireJS error on pages containing widgets

To verify that you are encountering this issue, do the following:

1.  Open your website on your browser and navigate to a page where a UGC widget should appear.

2.  Open the developer tools for your browser, navigate to the console tab and verify that you are seeing this error:

    ![](https://files.readme.io/d16d7f3-Screen_Shot_2019-10-16_at_1.23.55_PM.png "Screen Shot 2019-10-16 at 1.23.55 PM.png")

    > ### 🚧 Note
    >
    > The cause of this error is that the Publish Center only generates embed code in plain HTML. Whereas the extension
    > is compliant with RequireJS standards and expects scripts to be only embedded via RequireJS. This problem can be
    > resolved by creating a widget with [these steps here](https://developers.pixlee.com/docs/magento-2#section-installing-product-description-page-pdp-widgets)
    > instead of directly adding the widget embed code from the Publish Center. However, if you wish to keep the generated
    > embed code, follow the steps below.

3.  Find the generated embed code for the widget. It should look like this:

    ```html
        <div id="pixlee_container"></div>
        <script type="text/javascript">
          window.PixleeAsyncInit = function() {
            Pixlee.init({apiKey:'YOUR_API_KEY'});
            Pixlee.addSimpleWidget({widgetId:YOUR_WIDGET_ID});
          };
        </script>
        <script src="https://assets.pxlecdn.com/assets/pixlee_widget_1_0_0.js"></script>
    ```
4.  Use the **require** function to change the embed code using these steps:
*   Use the require function and add the **asset.pixlee.com** url as a parameter.
*   Move the JavaScript code in the **window.PixleeAsyncInit** function to the **require** function's callback.
*   Call the **Pixlee.resizeWidget()** function at the end of the callback.
*   The new code should look like this:

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
