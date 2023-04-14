---
title: Pixlee TurnTo Social UGC Magento 2 Test Documentation
author: Pixlee TurnTo
date: 2023-04-14
---

# Test Add to Cart  #

+-------------------+----------------------------------------------+
| Test Case ID      | PIX-MAG-01                                   |
+-------------------+----------------------------------------------+
| Test Case Title   | Test the Add to Cart analytics feature       |
+-------------------+----------------------------------------------+
| Test Scenario ID  | PIX-MAG-01-S1                                |
+-------------------+----------------------------------------------+
| Author            | Pixlee Engineer                              |
+-------------------+----------------------------------------------+
| Magento Version   | 2.0.2                                        |
+-------------------+----------------------------------------------+
| Test Item         | Add to Cart button                           |
+-------------------+----------------------------------------------+
| Test Case Summary | Verify that the product analytics data was\  |
|                   | set to Pixlee when user adds product to cart |
+-------------------+----------------------------------------------+
| Preconditions     | Pixlee API credentials need to be filled\    |
|                   | on the Pixlee configuration page             |
+-------------------+----------------------------------------------+
| Postconditions    | Nil                                          |
+-------------------+----------------------------------------------+
| Execution Type    | Manual                                       |
+-------------------+----------------------------------------------+

+-------------+--------------------------------+---------------------------------+------------------------+-------------+---------+
| Step #      | Step                           | Expected Result                 | Actual Result          | Pass / Fail | Comment |
+=============+================================+=================================+========================+=============+=========+
| 1           | Go to the product page\        | Page shows product information\ | Product page loaded\   | Pass        |         |
|             | on the e-commerce site         | and 'Add to Cart' button        | with no error          |             |         |
+-------------+--------------------------------+---------------------------------+------------------------+-------------+---------+
| 2           | Add product to cart by\        | Product added and user gets\    | Product is added and\  | Pass        |         |
|             | clicking on the 'Add to Cart'\ | redirected to the shopping      | the shopping cart is\  |             |         |
|             | button cart page               |                                 | updated                |             |         |
+-------------+--------------------------------+---------------------------------+------------------------+-------------+---------+
| 3           | Verify that the analytics\     | Information of the product\     | Analytics information\ | Pass        |         |
|             | data is received on the\       | that was added in Step 2 is\    | is received on the \   |             |         |
|             | Pixlee analytics server        | received on the analytics\      | analytics server       |             |         |
|             |                                | server                          |                        |             |         |
+-------------+--------------------------------+---------------------------------+------------------------+-------------+---------+

<!-- Manually inserting the LaTeX \newpage command -->
\newpage

# Test Remove from Cart #

+-------------------+----------------------------------------------+
| Test Case ID      | PIX-MAG-02                                   |
+-------------------+----------------------------------------------+
| Test Case Title   | Test the Remove from cart analytics feature  |
+-------------------+----------------------------------------------+
| Test Scenario ID  | PIX-MAG-02-S1                                |
+-------------------+----------------------------------------------+
| Author            | Pixlee Engineer                              |
+-------------------+----------------------------------------------+
| Magento Version   | 2.0.2                                        |
+-------------------+----------------------------------------------+
| Test Item         | Remove from cart button                      |
+-------------------+----------------------------------------------+
| Test Case Summary | Verify that the product analytics data was\  |
|                   | set to Pixlee when user removes product from |
|                   | cart                                         |
+-------------------+----------------------------------------------+
| Preconditions     | Pixlee API credentials need to be filled\    |
|                   | on the Pixlee configuration page             |
+-------------------+----------------------------------------------+
| Postconditions    | Nil                                          |
+-------------------+----------------------------------------------+
| Execution Type    | Manual                                       |
+-------------------+----------------------------------------------+

+-------------+--------------------------------+---------------------------------+------------------------+-------------+---------+
| Step #      | Step                           | Expected Result                 | Actual Result          | Pass / Fail | Comment |
+=============+================================+=================================+========================+=============+=========+
| 1           | Go to the shopping cart\       | Display a page with a list of \ | Products in the\       | Pass        |         |
|             | on the e-commerce site         | products that are in the\       | shopping cart\         |             |         |
|             |                                | shopping cart                   | are shown              |             |         |
+-------------+--------------------------------+---------------------------------+------------------------+-------------+---------+
| 2           | Click on the trash can icon\   | Shopping cart refreshes and\    | Product is removed\    | Pass        |         |
|             | to remove the item from the\   | the product is removed          | and the shopping cart\ |             |         |
|             | shopping cart                  |                                 | is updated             |             |         |
+-------------+--------------------------------+---------------------------------+------------------------+-------------+---------+
| 3           | Verify that the analytics\     | Information of the product\     | Analytics information\ | Pass        |         |
|             | data is received on the\       | that was added in Step 2 is\    | is received on the \   |             |         |
|             | Pixlee analytics server        | received on the analytics\      | analytics server       |             |         |
|             |                                | server                          |                        |             |         |
+-------------+--------------------------------+---------------------------------+------------------------+-------------+---------+

<!-- Manually inserting the LaTeX \newpage command -->
\newpage

# Test Checkout Complete #

+-------------------+----------------------------------------------+
| Test Case ID      | PIX-MAG-03                                   |
+-------------------+----------------------------------------------+
| Test Case Title   | Test the Checkout analytics feature          |
+-------------------+----------------------------------------------+
| Test Scenario ID  | PIX-MAG-03-S1                                |
+-------------------+----------------------------------------------+
| Author            | Pixlee Engineer                              |
+-------------------+----------------------------------------------+
| Magento Version   | 2.0.2                                        |
+-------------------+----------------------------------------------+
| Test Item         | Checkout button                              |
+-------------------+----------------------------------------------+
| Test Case Summary | Verify that the product analytics data was\  |
|                   | set to Pixlee when user adds product to cart |
+-------------------+----------------------------------------------+
| Preconditions     | Pixlee API credentials need to be filled\    |
|                   | on the Pixlee configuration page             |
+-------------------+----------------------------------------------+
| Postconditions    | Nil                                          |
+-------------------+----------------------------------------------+
| Execution Type    | Manual                                       |
+-------------------+----------------------------------------------+

+-------------+--------------------------------+---------------------------------+------------------------+-------------+---------+
| Step #      | Step                           | Expected Result                 | Actual Result          | Pass / Fail | Comment |
+=============+================================+=================================+========================+=============+=========+
| 1           | Go to the shopping cart\       | Display a page with a list of \ | Products in the\       | Pass        |         |
|             | on the e-commerce site         | products that are in the\       | shopping cart\         |             |         |
|             |                                | shopping cart                   | are shown              |             |         |
+-------------+--------------------------------+---------------------------------+------------------------+-------------+---------+
| 2           | Click on the 'Proceed to\      | Page redirects to the\          | Checkout method page\  | Pass        |         |
|             | Checkout' button to begin\     | 'Checkout Method' page          | loaded with no error\  |             |         |
+-------------+--------------------------------+---------------------------------+------------------------+-------------+---------+
| 3           | Fill out the required\         | Redirects to the order\         | Order has been palced\ | Pass        |         |
|             | information and click on\      | success page                    | and success message\   |             |         |
|             | 'Place Order' button           |                                 | is shown               |             |         |
+-------------+--------------------------------+---------------------------------+------------------------+-------------+---------+
| 4           | Verify that the analytics\     | Information of the product\     | Analytics information\ | Pass        |         |
|             | data is received on the\       | that was added in Step 3 is\    | is received on the \   |             |         |
|             | Pixlee analytics server        | received on the analytics\      | analytics server       |             |         |
|             |                                | server                          |                        |             |         |
+-------------+--------------------------------+---------------------------------+------------------------+-------------+---------+

<!-- Manually inserting the LaTeX \newpage command -->
\newpage

# Test Add Product #

+-------------------+----------------------------------------------+
| Test Case ID      | PIX-MAG-04                                   |
+-------------------+----------------------------------------------+
| Test Case Title   | Test the Add Product feature                 |
+-------------------+----------------------------------------------+
| Test Scenario ID  | PIX-MAG-04-S1                                |
+-------------------+----------------------------------------------+
| Author            | Pixlee Engineer                              |
+-------------------+----------------------------------------------+
| Magento Version   | 2.0.2                                        |
+-------------------+----------------------------------------------+
| Test Item         | Add Product button                           |
+-------------------+----------------------------------------------+
| Test Case Summary | Verify that the product is created on\       |
|                   | Pixlee when user adds a new product          |
+-------------------+----------------------------------------------+
| Preconditions     | Pixlee API credentials need to be filled\    |
|                   | on the Pixlee configuration page             |
+-------------------+----------------------------------------------+
| Postconditions    | Nil                                          |
+-------------------+----------------------------------------------+
| Execution Type    | Manual                                       |
+-------------------+----------------------------------------------+

+-------------+--------------------------------+---------------------------------+------------------------+-------------+---------+
| Step #      | Step                           | Expected Result                 | Actual Result          | Pass / Fail | Comment |
+=============+================================+=================================+========================+=============+=========+
| 1           | Log in to the Magento Admin\   | User logged in to the Magento\  | Magento Admin page\    | Pass        |         |
|             | Panel                          | Admin Page and redirected to\   | loaded with no error   |             |         |
|             |                                | the dashboard page              |                        |             |         |
+-------------+--------------------------------+---------------------------------+------------------------+-------------+---------+
| 2           | Navigate to the 'Manage\       | Shows the New Product form\     | New product form\      | Pass        |         |
|             | Products' page and click on\   | prompting the user to enter\    | loaded with no error   |             |         |
|             | 'Add Product'                  | product information             |                        |             |         |
+-------------+--------------------------------+---------------------------------+------------------------+-------------+---------+
| 3           | Enter the product information\ | Shows 'You save the product'\   | Product is created\    | Pass        |         |
|             | and click on the 'Save' button | notification with a green\      | and success message\   |             |         |
|             |                                | checkmark                       | is displayed           |             |         |
+-------------+--------------------------------+---------------------------------+------------------------+-------------+---------+
| 4           | Log in to Pixlee control\      | A list of products is displayed | Products are\          | Pass        |         |
|             | panel and navigate to the\     |                                 | displayed with no\     |             |         |
|             | products page                  |                                 | error                  |             |         |
+-------------+--------------------------------+---------------------------------+------------------------+-------------+---------+
| 5           | Search for the product that\   | Product exists in search\       | The new product is\    | Pass        |         |
|             | was just created, by name or\  | result list                     | found                  |             |         |
|             | by SKU                         |                                 |                        |             |         |
+-------------+--------------------------------+---------------------------------+------------------------+-------------+---------+

<!-- Manually inserting the LaTeX \newpage command -->
\newpage

# Test Update Product #

+-------------------+----------------------------------------------+
| Test Case ID      | PIX-MAG-05                                   |
+-------------------+----------------------------------------------+
| Test Case Title   | Test the Update Product feature              |
+-------------------+----------------------------------------------+
| Test Scenario ID  | PIX-MAG-05-S1                                |
+-------------------+----------------------------------------------+
| Author            | Pixlee Engineer                              |
+-------------------+----------------------------------------------+
| Magento Version   | 2.0.2                                        |
+-------------------+----------------------------------------------+
| Test Item         | Save Product button                          |
+-------------------+----------------------------------------------+
| Test Case Summary | Verify that the product is updated on\       |
|                   | Pixlee when user updates an existing product |
+-------------------+----------------------------------------------+
| Preconditions     | Pixlee API credentials need to be filled\    |
|                   | on the Pixlee configuration page             |
+-------------------+----------------------------------------------+
| Postconditions    | Nil                                          |
+-------------------+----------------------------------------------+
| Execution Type    | Manual                                       |
+-------------------+----------------------------------------------+

+-------------+--------------------------------+---------------------------------+------------------------+-------------+---------+
| Step #      | Step                           | Expected Result                 | Actual Result          | Pass / Fail | Comment |
+=============+================================+=================================+========================+=============+=========+
| 1           | Log in to the Magento Admin\   | User logged in to the Magento\  | Magento Admin page\    | Pass        |         |
|             | Panel                          | Admin Page and redirected to\   | loaded with no error   |             |         |
|             |                                | the dashboard page              |                        |             |         |
+-------------+--------------------------------+---------------------------------+------------------------+-------------+---------+
| 2           | Navigate to the 'Manage\       | Shows the New Product with\     | Magento product page\  | Pass        |         |
|             | Products' page and click on\   | pre-filled information about\   | loaded with\           |             |         |
|             | one of the existing products   | the product                     | pre-filled product\    |             |         |
|             |                                |                                 | information            |             |         |
+-------------+--------------------------------+---------------------------------+------------------------+-------------+---------+
| 3           | Update product information\    | Shows 'You save the product'\   | Product is saved\      | Pass        |         |
|             | and click on the 'Save' button | notification with a green\      | and success message\   |             |         |
|             |                                | checkmark                       | is displayed           |             |         |
+-------------+--------------------------------+---------------------------------+------------------------+-------------+---------+
| 4           | Log in to Pixlee control\      | A list of products is displayed | Products are\          | Pass        |         |
|             | panel and navigate to the\     |                                 | displayed with no\     |             |         |
|             | products page                  |                                 | error                  |             |         |
+-------------+--------------------------------+---------------------------------+------------------------+-------------+---------+
| 5           | Search for the product that\   | Product has been updated with\  | The new product is\    | Pass        |         |
|             | was just updated, by name or\  | the changes in step 3           | updated with the\      |             |         |
|             | by SKU                         |                                 | latest changes         |             |         |
+-------------+--------------------------------+---------------------------------+------------------------+-------------+---------+

<!-- Manually inserting the LaTeX \newpage command -->
\newpage

# Test Export Product #

+-------------------+----------------------------------------------+
| Test Case ID      | PIX-MAG-06                                   |
+-------------------+----------------------------------------------+
| Test Case Title   | Test the Export Products feature             |
+-------------------+----------------------------------------------+
| Test Scenario ID  | PIX-MAG-06-S1                                |
+-------------------+----------------------------------------------+
| Author            | Pixlee Engineer                              |
+-------------------+----------------------------------------------+
| Magento Version   | 2.0.2                                        |
+-------------------+----------------------------------------------+
| Test Item         | Pixlee Account Configuration page\           |
|                   | 'Export Products to Pixlee button'           |
+-------------------+----------------------------------------------+
| Test Case Summary | Verify that the products are exported to\    |
|                   | Pixlee when user updates exports products    |
+-------------------+----------------------------------------------+
| Preconditions     | Pixlee API credentials need to be filled\    |
|                   | on the Pixlee configuration page             |
+-------------------+----------------------------------------------+
| Postconditions    | Nil                                          |
+-------------------+----------------------------------------------+
| Execution Type    | Manual                                       |
+-------------------+----------------------------------------------+

+-------------+--------------------------------+---------------------------------+------------------------+-------------+---------+
| Step #      | Step                           | Expected Result                 | Actual Result          | Pass / Fail | Comment |
+=============+================================+=================================+========================+=============+=========+
| 1           | Log in to the Magento Admin\   | User logged in to the Magento\  | Magento Admin page\    | Pass        |         |
|             | Panel                          | Admin Page and redirected to\   | loaded with no error   |             |         |
|             |                                | the dashboard page              |                        |             |         |
+-------------+--------------------------------+---------------------------------+------------------------+-------------+---------+
| 2           | Navigate to the Pixlee\        | Shows configuration page with\  | Configuration page\    | Pass        |         |
|             | Account Configuration page     | API Credentials and 'Export\    | loaded                 |             |         |
|             |                                | Products to Pixlee' button      |                        |             |         |
+-------------+--------------------------------+---------------------------------+------------------------+-------------+---------+
| 3           | Click on the 'Export Products\ | Popup loading spinner\          | Loading spinner shown\ | Pass        |         |
|             | to Pixlee' button to export\   | indicating that products are\   | and success message\   |             |         |
|             | the products                   | being exported. Once export\    | shown after exporting\ |             |         |
|             |                                | is done, system indicates that\ | is done                |             |         |
|             |                                | 'All your products have been\   |                        |             |         |
|             |                                | exported to Pixlee'             |                        |             |         |
+-------------+--------------------------------+---------------------------------+------------------------+-------------+---------+
| 4           | Log in to Pixlee control\      | A list of products is\          | Exported products are\ | Pass        |         |
|             | panel and navigate to the\     | displayed and is synced with\   | shown on Pixlee\       |             |         |
|             | products page                  | the products exported in step 3 | products page          |             |         |
+-------------+--------------------------------+---------------------------------+------------------------+-------------+---------+
