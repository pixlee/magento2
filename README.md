# List of Changes

## Milestone 3

- Created a `Pixlee.postAnalytics` method to send data to the analytics end-point.
- Created an `AddToCartObserver` class that operates on the `checkout_cart_product_add_after` event, gets data about the product added to the cart, and then uses the `postAnalytics` method to send those data to Pixlee.
- Added an entry in the Pixlee events.xml to hook the `AddToCartObserver` into the `checkout_cart_product_add_after` event.

## Milestone 4

- Created a `Data.getCategories` method that takes a product and returns an array of categories to which a product belongs.
- There was no `getCategories` stub or any calls to `getCategories` in the demo code, so I added a call in the `Data.getExtraFields` method, which should ensure the list of categories gets sent to Pixlee as extra data. I couldn't see anywhere else in `Data.exportProductToPixlee` or `Pixlee.createProduct` where this belonged, so there may be a more appropriate place for this.

# Summary

This was a fun little coding exercise. It's pretty much the same thing I've done when I've written plugins for open source projects without much documentation — you have to explore existing plugins, see how they do things, read the project's code, and figure out the most seamless way to interact with it. I spent the majority of my time getting Magento running and configuring my development environment, but once that was off the ground everything was pretty straightforward.

I found Pixlee's `ValidateCredentialsObserver` easily enough but I wasn't sure how Magento knew how to link that observer to an event, so I consulted their developer documentation and found out about the `events.xml` file. Then I had to go digging to find the exact event I needed. This was a bit trickier because Magento has about a million likely-looking modules, but it wasn't long before I narrowed it down and found the `checkout_cart_product_add_after` event in `\Magento\Checkout\Model\Cart.addProduct`. This also listed the variables associated with this event, and it wasn't hard to get the data I needed from them to post to the API.

The next milestone went much the same way — I read through the code and found the `Product` class had methods that would help me get a list of a product's categories. There were some nice little spanners in the works, like certain methods only being defined on a parent class, or the default list of categories not including the category names, but again it was just a matter of reading the code to understand where everything comes from.
