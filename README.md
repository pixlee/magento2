# Pixlee Turnto Social UGC plugin for Magento 2

To view these docs online, navigate to:
https://developers.pixlee.com/docs/magento-2

Use this Magento 2 extension to connects to Emplifi's Pixlee TurnTo Social UGC service. Compatible with Magento Open Source and Adobe Commerce, versions 2.3.x - 2.4.6.
* * *

## Installation Instructions

### Install using Composer (recommended)

Follow the Adobe documentation to [Install an extension](https://experienceleague.adobe.com/docs/commerce-operations/installation-guide/tutorials/extensions.html?lang=en)

Composer Package [pixlee/magento2](https://packagist.org/packages/pixlee/magento2)

1. Run these commands in your root Magento installation directory for composer install:

    ```bash
    composer require pixlee/magento2
    bin/magento module:enable TurnTo_SocialCommerce --clear-static-content
    bin/magento setup:upgrade
    bin/magento setup:di:compile
    bin/magento cache:clean
    ```

2. Configure the module to connect to your Pixlee account. Please see the Setup section below.

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

5. Configure the module to connect to your Pixlee account. Please see the Setup section below.

* * *

## Setup

### Get Your Pixlee API Keys

To connect to Pixlee, you will need your **API Key** and **Secret Key** from [https://app.pixlee.com](https://app.pixlee.com).

1. Log in to [https://app.pixlee.com](https://app.pixlee.com)
2. Click on the [Settings](https://app.pixlee.com/app#settings/account_settings) link inside the top right hamburger menu.

   ![](https://files.readme.io/35a1ef3-01_09_pixlee_dashboard.png "01_09_pixlee_dashboard.png")

   The settings page should look like this:

   ![](https://files.readme.io/47d7746-01_10_account_settings.png "01_10_account_settings.png")

3. Click on **Pixlee API** on the left-hand navigation bar.

   From this page, record the values of **Account ID**, **Account API Key**, and **Account Secret Key** to be used in the Magento store configurations.

   ![](https://files.readme.io/e34dfe7-01_11_pixlee_api.png "01_11_pixlee_api.png")

### Configure the Pixlee Magento 2 Module

Select **Stores** > **Configuration** on the main menu.

The Pixlee configurations are managed at the website level. At the top of the page, select the desired website using the store view dropdown menu.

> ### 🚧
>
> Note
>
> If you keep the **Store View** as **Default Config** then the **Pixlee Tab** will not appear.

![](https://files.readme.io/ac7397d-04_10_magento2_store_view.png "04_10_magento2_store_view.png")

In the left Navigation panel, click **Pixlee** > **Existing Customers**.

![](https://files.readme.io/38d8841-04_02_magento2_configuration.png "04_02_magento2_configuration.png")

1. Set **Enable** to *Yes*
2. Fill in **API Key** and **Secret Key** with the User API Key and Account Secret Key from [https://app.pixlee.com](https://app.pixlee.com). (see Get Your Pixlee API Keys above)
3. Click **Save Config** in the top right corner of the page to save your Account Settings.

### Exporting Products from Magento 2 to Pixlee

#### Recurring Product Export Cron Job

The Magento 2 extension sets up a cron job that exports products to Pixlee on a daily basis from website 1. This will help ensure that product data is up-to-date in Pixlee by ensuring new products created in Magento 2 are exported to Pixlee daily.

> 📘 NOTE
>
> At this time, only website 1 exports products via the cron. Additional websites will have to be exported manually using the Export Products to Pixlee button in the Admin.

#### Update the time and frequency at which products are exported to Pixlee

Products will be exported on a daily basis at 3am UTC. If you wish to change the hour in which the products are exported do the following:

1.  In the Pixlee extension code in your Magento 2 instance, modify $MAGENTO_ROOT/app/code/Pixlee/Pixlee/etc/crontab.xml

2.  To change the hour in the day at which the products are exported, change the 3 in the schedule tag to any hour you like:

    ```xml
    <job name="export_cronjob" instance="Pixlee\Pixlee\Cron\ExportCron" method="execute">
        <schedule>1 3 * * *</schedule>
    </job>
    ```

#### Export Products to Pixlee button

Website products can be exported from Magento to Pixlee at any time using the **Export Products to Pixlee button**.
In the Pixlee **Existing Customer > API Settings**, click the **Export Products to Pixlee button** to export all products for the currently selected website only to your Pixlee account. This process can be repeated for each website.

![](https://files.readme.io/0ad06cc-04_03_magento2_export_pressed.png "04_03_magento2_export_pressed.png")

> ### 🚧
>
> Note
>
> If you have ever changed the **max_execution_time** variable for Magento, please ensure that it is set to at least 3600 seconds (1 hour). The default should be 18000 seconds, which is fine to leave alone.

### Embedding a PDP Widget on your Product Page

After creating a PDP Widget in Pixlee, it can be added product details page.

1. Go to [https://app.pixlee.com](https://app.pixlee.com), log in, and navigate to the **Publish** tab.

    (Alternatively, point your browser to [https://app.pixlee.com/app#publish](https://app.pixlee.com/app#publish) while logged in).

2. Click the "Install Product Displays" and it should present you with a lightbox that looks like following.

    ![](https://files.readme.io/ded63e5-Add_PDP_lightbox.png "Add PDP lightbox.png")

3. Customize the widget as you wish. At the end, press the "Generate Embed Code" button and you'll be presented with an embed code. Note - We recommend that you leave the "Load Priority" setting to "Low priority" when customizing.

    ![](https://files.readme.io/046e7a9-01_01_pdp_widget_result.png "01_01_pdp_widget_result.png")

    Copy the value for **widgetId** in the resulting code snippet. Fill in this value in the Widget ID field inside Admin > System > Configuration > Pixlee Account Configuration. This the same field that we skipped over in Step 10.

4. Now, to implement the Pixlee PDP widget, simply enter the value next to **widgetId** from the previous step in the **PDP Widget ID** field under the **PDP Widget Settings** section.

    Click **Save Config** on the top right corner of the page to save your Account Settings.

    ![](https://files.readme.io/eb8b0b0-04_04_magento2_pdp_widget_configuration.png "04_04_magento2_pdp_widget_configuration.png")

    With that, any product that has tagged photos in its Pixlee album should now have a widget gallery appear on its product description page.

    To further customize, you can re-publish your PDP widget using Pixlee's **Design Editor**, and use that resulting **widgetId** instead!

    ![](https://files.readme.io/f0dcdaa-04_05_magento2_pdp_widget_example.png "04_05_magento2_pdp_widget_example.png")

5. Furthermore, if you'd like to **customize the placement** of the PDP widget, modify _catalog_product_view.xml_.

    For example, in the Magento 2 sample store (Luma), it'd be in the following file:

    `$MAGENTO_ROOT/app/code/Pixlee/Pixlee/view/frontend/layout/catalog_product_view.xml`

    Where $MAGENTO_ROOT might be something like /var/www or /usr/share/nginx/html, **depending on your installation.**

### Embedding a CDP Widget on your Category Pages

The first three steps are exactly the same as embedding a PDP widget as they only involve getting a **widgetId** from the Control Panel.

1.  Go to [https://app.pixlee.com](https://app.pixlee.com), log in, and navigate to the **Publish** tab.

    (Alternatively, point your browser to [https://app.pixlee.com/app#publish](https://app.pixlee.com/app#publish) while logged in).

2.  Click the "Install Product Displays", and it should present you with a lightbox that looks like following.

    ![](https://files.readme.io/f7cc7cc-Add_PDP_lightbox.png "Add PDP lightbox.png")

3.  Customize the widget as you wish. At the end, press the "Generate Embed Code" button and you'll be presented with an embed code. Note - We recommend that you leave the "Load Priority" setting to "Low priority" when customizing.

    ![](https://files.readme.io/3f7f450-01_01_pdp_widget_result.png "01_01_pdp_widget_result.png")

    Copy the value for **widgetId** in the resulting code snippet.

4.  Now, to implement the Pixlee CDP widget, simply fill in this value in the **CDP Widget ID** field inside Admin > System > Configuration > Pixlee Account Configuration > CDP Widget ID.

    Click **Save Config** on the top right corner of the page to save your Account Settings.

    ![](https://files.readme.io/304bbf8-04_04_magento2_pdp_widget_configuration.png "04_04_magento2_pdp_widget_configuration.png")

    With that, any product that has tagged photos in its Pixlee album should now have a widget gallery appear on its product description page.

    To further customize, you can re-publish your PDP widget using Pixlee's **Design Editor**, and use that resulting **widgetId** instead!

    ![](https://files.readme.io/97edd93-04_06_magento2_cdp_widget_example.png "04_06_magento2_cdp_widget_example.png")

5.  Furthermore, if you'd like to **customize the placement** of the CDP widget, modify _catalog_category_view.xml_.

    For example, in the Magento 2 sample store (Luma), it'd be in the following file:

    `$MAGENTO_ROOT/app/code/Pixlee/Pixlee/view/frontend/layout/catalog_category_view.xml`

    Where $MAGENTO_ROOT might be something like /var/www or /usr/share/nginx/html, **depending on your installation.**

* * *

## Testing and Troubleshooting

To test that everything was implemented correctly, we need to check two things -

1.  Were all the products exported?
2.  Are API calls being successfully made to the Pixlee API?

### Were all the products exported?

Before testing, please make sure that the Pixlee Product Exports job has at least ran once. If you've already run the exports once, skip to step 3.

1.  In order to run it manually. Open Admin Panel > Stores > Configuration > Pixlee > Existing Customers. Press the **Export Products to Pixlee** button to start the exports.

    ![](https://files.readme.io/5315bbd-04_03_magento2_export_pressed.png "04_03_magento2_export_pressed.png")

2.  Login to [Pixlee](https://app.pixlee.com) and navigate to **Products** under the **Album** tab. [Alternatively, click this link.](https://app.pixlee.com/app#products).

3.  You should see a list of products on this page.

    ![](https://files.readme.io/9546c5f-01_64_demandware_products.png "01_64_demandware_products.png")

4.  Try searching for a few products on this page that you know exist in your catalog.

5.  In case the list is empty or you were not able to search for a particular product, proceed to the next step. Otherwise, proceed straight to the next section i.e. [Are API calls being successfully made to the Pixlee API?](#api-calls-tests)

6.  There can be several causes of failure to export products so first, we need to find out the exact cause of the failure. The Pixlee Product Exports job logs the progress and all exceptions to the server logs. So the next step for us is to get the both **Magento server logs** and the **PHP (Apache or equivalent)** logs.

7.  The Magento server logs are usually located at **$Magento_Root$/var/log** so navigate to that location and look for the file named **pixlee.log**.

8.  For the PHP logs location, it depends on your setup. For example, if you're using Apache the logs should be in **$Apache Root$/logs**. We're looking for the file named **php_error.log** in this directory.

9.  One of the most common issues that we encounter is a lack of allocated memory to the Magento or PHP/Apache server. Open the **php_error.log** file using your favorite text editor and search for a log entry similar to the following line

    Allowed memory size of 33554432 bytes exhausted (tried to allocate 43148176 bytes) in PHP

10.  If you found a similar looking error, please increase the allocated memory and run the product export job again.

11.  Another common issue we encounter is a low setting for the **max_execution_time**. Look for a log entry similar to the following

    Fatal error: Maximum execution time of XYZ seconds exceeded in ...

12.  If you found a similar log entry please increase the **max_execution_time** setting inside your **php.ini** file to at least 3600. And then try exporting the products again.

13.  If at this point you're still not able to see any products exported to Pixlee, please contact us at support@pixleeturnto.com and attach the both **pixlee.log** and **php_error.log** with the email.

### Are API calls being successfully made to the Pixlee API?

API calls are made to Pixlee API when a customer adds something to their cart and when they buy something on your store. We need to make sure that these calls are being made at the right time.

1.  Open your favorite browser and open a product page of your store. And click **Add to Cart**.

    ![](https://files.readme.io/4c7ccb4-04_07_magento2_product_page.png "04_07_magento2_product_page.png")

2.  Open the **pixlee.log** file located at **$Magento_Root$/var/log** using your favorite text editor and scroll down to the very end.

3.  There should be an entry beginning with **AddToCart**

    ![](https://files.readme.io/0abe8fa-Screen_Shot_2019-10-10_at_7.36.19_PM.png "Screen Shot 2019-10-10 at 7.36.19 PM.png")

4.  If you found the **AddToCart** calls then your analytics were integrated correctly. If not, contact us at support@pixleeturnto.com and attach the **pixlee.log** file with the email.

5.  Switch back to the browser and proceed to checkout and buy the product that you added to cart previously. Use a test payment method for the checkout.

6.  When you reach the order confirmation page switch open the **pixlee.log** file again and ensure that you are viewing the latest copy of the file.

7.  This time look for log entries beginning with **CheckoutSuccess**

    ![](https://files.readme.io/f02da02-Screen_Shot_2019-10-10_at_7.47.20_PM.png "Screen Shot 2019-10-10 at 7.47.20 PM.png")

8.  If you do not see the **CheckoutSuccess** calls like in the screenshot, please contact us at support@pixleeturnto.com and attach the **pixlee.log** file with the email.

> ### 🚧
>
> Disclaimer: Mobile Analytics
>
> Based on the design of Magento2, user agents are not passed along the add to cart and conversion events. This means that there is no current ability to split between mobile and desktop conversion data.

### RequireJS error on pages containing widgets

To verify that you are encountering this issue, do the following:

1.  Open your website on your browser and navigate to a page where a Pixlee widget should appear.

2.  Open the developer tools for your browser, navigate to the console tab and verify that you are seeing this error:

    ![](https://files.readme.io/d16d7f3-Screen_Shot_2019-10-16_at_1.23.55_PM.png "Screen Shot 2019-10-16 at 1.23.55 PM.png")

    > ### 🚧
    >
    > Note
    >
    > The cause of this error is that pixlee.com only generates embed codes in plain HTML. Whereas the Pixlee Magento extension is compliant with RequireJS standards and expects Pixlee scripts to be only embedded via RequireJS. This problem can be resolved by creating a widget with [these steps here](https://developers.pixlee.com/docs/magento-2#section-installing-product-description-page-pdp-widgets) instead of directly adding the widget embed code from pixlee.com. However, if you wish to keep the generated embed code, follow the steps below.

3.  Find the generated embed code for the widget. It should look like this:

    ![](https://files.readme.io/f1d47d5-Screen_Shot_2019-10-16_at_1.16.58_PM.png "Screen Shot 2019-10-16 at 1.16.58 PM.png")

    Here is a formatted version of the above code:

    ```html
        <div id="pixlee_container"></div>
        <script type="text/javascript">
          window.PixleeAsyncInit = function() {
            Pixlee.init({apiKey:'YOUR_API_KEY'});
            Pixlee.addSimpleWidget({widgetId:YOUR_WIDGET_ID});
          };
        </script>
        <script src="//assets.pxlecdn.com/assets/pixlee_widget_1_0_0.js"></script>
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
