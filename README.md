# Development Setup

There are definitely ways you can get started with Magento 2 locally, but for a quick and easy way to get you up and running, I will recommend using MAMP.

1. Download and install MAMP locally.
2. Download Magento from it's official website and also "sample data" so that we have a sample store to work with during development.
3. You can easily find a tutorial online on how to get started with Magento using MAMP via Google.
4. Install the Pixlee extension. Use the integration docs at [developers.pixlee.com](https://developers.pixlee.com) for the same.

# Pushing Updates

1. Note the pushing an update simply means uploading a new version of the extension on a public URL that can be shared with customers. It doesn't mean pushing this version to marketplace. If you want to push this update to the marketplace, checkout the Wiki on how to do that.
2. Run `sh packager.sh <version>` after commiting your changes. Ensure that `<version>` is higher than the last number used. It should generate a new package, rename the file to `Pixlee_Magento2.zip`.
3. Log into S3 and navigate to assets.pixlee.com > magento.
4. Replace the current Pixlee_Magento2.zip with the one that we generated in step 2.
5. Ensure that this file is public.
6. bust fastly cache with

curl -XPOST -H "Fastly-Key:<FASTLY_API_KEY>" "https://api.fastly.com/service/6ZOYO75DiAyHoS7rTHgcqk/purge_all"

# Single-Store Mode

We have some customers who want to use the single-store mode in Magento 2. This mode is not currently supported in the master branch of our application. For those customers, send them the version of the app found in this branch: https://github.com/pixlee/magento2/tree/bug/CR-3530/kushal
