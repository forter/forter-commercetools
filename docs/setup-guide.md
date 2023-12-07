# Setup Guide

Please follow these steps in order to prepare, configure and setup this app:

* Clone this repository and host it on a server of your choice (any Linux based server that can run php will be suitable).
* If you plan on enabling Forter's pre-auth order validation, you'll need to setup a domain for the app, and make sure that `{YOUR-DOMAIN}/commercetools/api/extensions` is open to POST requests.
* Prepare a messaging service for receiving Commercetools notifications, and save its credentials (This version of the app supports only Amazon SQS. See this guide on [setting up Amazon SQS](https://docs.aws.amazon.com/AWSSimpleQueueService/latest/SQSDeveloperGuide/sqs-setting-up.html)).
* Create a Commercetools API-Client, add save its credentials (see [this guide](./how-to-get-commercetools-api-credentials.md)).
* Get your Forter API credentials (from your [Forter Portal settings page](https://portal.forter.com/app/onboarder/settings/general)).
* Set your app configurations (see [this guide](./configuration-guide.md)).
* Set your custom mapping if needed (see [this guide](./forter-schema-custom-mapping-guide.md)).
* From the app root dir, run the following commands:
```
composer install
php artisan key:generate
php artisan config:cache
php artisan route:cache
```
* From the app root dir, run the Forter app setup command: `php artisan forter:setup` (**It's highly recommended to run this command after every configuration change**. If you have an automated deployment script/tool, you can just add that close to the end, so it'll run on every deployment).
* Setup a cronjob to run Laravel's scheduler (`php artisan schedule:run`) every minute (see [this guide](https://laravel.com/docs/10.x/scheduling#running-the-scheduler).
* Check that everything works as expected and you're good to go :)

----

* [App configuration guide](./images/configuration-guide.md)

* [How to get Commercetools API Credentials](./how-to-get-commercetools-api-credentials.md)

* [Forter custom schema mapping guide](./forter-schema-custom-mapping-guide.md)

* [Frontend preparations guide](./docs/frontend-preparations.md)

* [Amazon SQS docs](https://docs.aws.amazon.com/AWSSimpleQueueService/latest/SQSDeveloperGuide/sqs-setting-up.html)

* [Forter docs](https://docs.forter.com/)

* [Laravel docs](https://laravel.com/docs/10.x)
