Problems Encountered

Milestone 1 : 
Problem - I was not able to access the Magento admin panel and Sample Luma store
Solution - Increasing the memory limits in the PHP.INI file solved the issue. Apparently it consumed all the memory while loading the web page, because
small amount of memory is allocated initially

Milestone 2:
Problem - Tried to save the configuration after inserting API and secret keys but didn't save the configuration. 
Solution - When debugged the code, found out that curl is not allowing insecure SSL and is not hitting the pixlee API to save the config.
Therefore set SSLVerifyHost and SSLVerifyPeer to false and it helped to hit the API URL from localhost.

Milestone 4: 
Problem - Found out that Export product information in admin panel is not working as regualr expression matching in Export.php is not correct and is
		  assigning wrong value to websiteId
Solution - corrected the regular expression and is assigning the correct value to website Id now.

Problem - Also, the URL it is hitting to send the product info may be down or is not working. When trying to POST the data, it is responding with 404.



Approach to the the problem:

1) Milestone 1 and Milestone 2 are pretty straight forward except for the two problems encountered as mentioned above. 
2) For Milestone 2 and Milestone 3, spent a lot of time understanding the structure of Magento and Pixlee directories. Going through the Pixlee 
extensions code helped me to find about event and observers. Then, a quick google search about Magento cart add events and observers documentation 
helped me to complete Milestone 3.
	To complete the Milestone 3,
	1) I first created an observer for 'checkout_cart_add_product_complete' event in the observer folder.
	2) Once the event dispatched the product information added to the cart, retrieved website Id from the Store Manager Interface and then
	3) Call PostProductInfoToAPI method by sending the product info and website Id as parameters. Before sending the product info, I used the 
	   getSecretKey() method from Data.php file sending website Id from the previous step as the paramters and then POSTed the data to the API.
	4) Logged the API response code in the system.log file 
 
3)Milestone 3 is pretty straight forward since we get a lot of product related data except its categories.
    To complete the Milestone 4
	1) Since we get the product information, we can get the its category Ids. We need to find model related to the category information.
	2) So, used the category model to get the category information of each category Id of the product
	3) Added it to array and returned it
	4) Modified createProduct in Pixlee.php accordingly so that it can accomodate category data to POST it to API
	

	
 
