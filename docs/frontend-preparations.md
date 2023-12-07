# Frontend Preparations Guide

* Login to your Forter Portal account and [get the JS snippet](https://portal.forter.com/app/onboarder/task/add-sandbox-js?path=trusted-conversions%26front-end-integration-phase%26javascript).
* Load Forter's JS snippet on every page of your store front, as instructed.
* Add a local script on every page, that'll listen and wait for the `ftr:tokenReady` event and send it to your site backend. This token (may be referred to as `forterToken` or `forterTokenCookie`) should be saved on a cart custom-field, and later on an order custom-field respectively.
```
document.addEventListener('ftr:tokenReady', function(evt) {
    var token = evt.detail;
    // send this token to your back-end and save on the cart/order custom-fields.
});
```
* Additionally, the customer's IP and User-Agent should be collected and saved as well (required for Forter's order validation mapping. Use `customerIP` and `customerUserAgent` as field names, or map your existing custom fields containing this information).
