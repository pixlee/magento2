# Notes
I have never worked with Magento before and haven't done much PHP in the past 3 years so most of the time spent was getting around Magento's framework and some of its concepts. I found Magento's documentation hard to navigate and ended up turning to StackOverflow most of the time.
I will admit I had a hard time testing my work and the magento store so I don't know if any of this actually works but was hoping to mostly show my logic around the tasks :)

# Step 3

  - Created an AddToCart Observer on `Observer/AddToCart.php` that retrieves information about the item added and calls Pixlee `postToApi` method
  - Registered the AddToCart observer on `etc/events.xml` for the checkout_cart_product_add_after event

# Step 4

  - Added a `getCategories($product)` method on `Helper/Data.php` that returns all the categories the product belongs to.
  - Changed the `createProduct` method on `Helper/Pixlee.php` to accept a `$categories` parameter.
