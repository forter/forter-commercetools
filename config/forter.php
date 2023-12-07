<?php
/**
 * Forter Commercetools app
 */

return [
    // Enable/Disable the app's activity (if needed)
    'is_enabled' => env('FORTER_IS_ENABLED', true),

    // Basic Settings
    'site_id' => env('FORTER_SITE_ID'),
    'secret_key' => env('FORTER_SECRET_KEY'),
    'api_version' => env('FORTER_API_VERSION', '10.1'),
    'extver' => env('FORTER_EXTVER', '2.1'),

    // Order Validation Settings
    'pre_order_validation_enabled' => env('FORTER_PRE_ORDER_VALIDATION_ENABLED', true),
    'post_order_validation_enabled' => env('FORTER_POST_ORDER_VALIDATION_ENABLED', true),

    // Require order to have at least one payment with at least one transaction for post-auth validation
    'post_order_validation_require_payment_transaction' => env('FORTER_POST_ORDER_VALIDATION_REQUIRE_PAYMENT_TRANSACTION', true),

    /**
     * Decision Actions:
     * * DO_NOTHING (or leave empty)
     * * SET_ORDER_STATE:orderStateKey (e.g., SET_ORDER_STATE:Cancelled)
     * * BLOCK_ORDER_PLACE (only available on pre-decline)
     */
    'decision_actions' => [
        'approve' => [
            'pre' => env('FORTER_DECISION_ACTION_APPROVE_PRE', 'SET_ORDER_STATE:Confirmed'),
            'post' => env('FORTER_DECISION_ACTION_APPROVE_POST', 'SET_ORDER_STATE:Confirmed'),
        ],
        'decline' => [
            'pre' => env('FORTER_DECISION_ACTION_DECLINE_PRE', 'SET_ORDER_STATE:Cancelled'),
            'post' => env('FORTER_DECISION_ACTION_DECLINE_POST', 'SET_ORDER_STATE:Cancelled'),
        ],
        'not_reviewed' => [
            'pre' => env('FORTER_DECISION_ACTION_NOT_REVIEWED_PRE', 'DO_NOTHING'),
            'post' => env('FORTER_DECISION_ACTION_NOT_REVIEWED_POST', 'DO_NOTHING'),
        ],
    ],

    // Pre-auth decline BLOCK_ORDER_PLACE error message
    'pre_decline_block_order_place_error_msg' => env('FORTER_PRE_DECLINE_BLOCK_ORDER_PLACE_ERROR_MSG', 'We are sorry, but we could not process your order at this time.'),

    /**
     * Recommendation handlers (optional)
     * If there's a need to use a custom handler for a Forter recommendation,
     * you may duplicate one of the example classes under `\App\Lib\Forter\RecommendationHandlers\Custom`, and follow the instructions on the comments for a quick start.
     */
    'recommendation_handlers' => [
        'ROUTING_RECOMMENDED' => env('FORTER_RECOMMENDATION_HANDLER_ROUTING_RECOMMENDED', \App\Lib\Forter\RecommendationHandlers\Custom\RoutingRecommendedHandler::class),
        'ID_VERIFICATION_REQUIRED' => env('FORTER_RECOMMENDATION_HANDLER_ID_VERIFICATION_REQUIRED', \App\Lib\Forter\RecommendationHandlers\Custom\IdVerificationRequiredHandler::class),
        'MONITOR_POTENTIAL_COUPON_ABUSE' => env('FORTER_RECOMMENDATION_HANDLER_MONITOR_POTENTIAL_COUPON_ABUSE', \App\Lib\Forter\RecommendationHandlers\Custom\MonitorPotentialCouponAbuseHandler::class),
        'MONITOR_POTENTIAL_SELLER_COLLUSION' => env('FORTER_RECOMMENDATION_HANDLER_MONITOR_POTENTIAL_SELLER_COLLUSION', \App\Lib\Forter\RecommendationHandlers\Custom\MonitorPotentialSellerCollusionHandler::class),
        'MONITOR_POTENTIAL_REFUND_ABUSE' => env('FORTER_RECOMMENDATION_HANDLER_MONITOR_POTENTIAL_REFUND_ABUSE', \App\Lib\Forter\RecommendationHandlers\Custom\MonitorPotentialRefundAbuseHandler::class),
        'DECLINE_CHANEL_ABUSER' => env('FORTER_RECOMMENDATION_HANDLER_DECLINE_CHANEL_ABUSER', \App\Lib\Forter\RecommendationHandlers\Custom\DeclineChanelAbuserHandler::class),
        'BORDERLINE' => env('FORTER_RECOMMENDATION_HANDLER_BORDERLINE', \App\Lib\Forter\RecommendationHandlers\Custom\BorderlineHandler::class),
    ],

    // Enable/Disable (all) recommendation handlers
    'recommendation_handlers_enabled' => env('FORTER_RECOMMENDATION_HANDLERS_ENABLED', true),

    // Recommendation translations
    'recommendation_keys_messages_map' => [
        'ROUTING_RECOMMENDED' => env('FORTER_RECOMMENDATION_MSG_ROUTING_RECOMMENDED', 'Re-route'),
        'ID_VERIFICATION_REQUIRED' => env('FORTER_RECOMMENDATION_MSG_ID_VERIFICATION_REQUIRED', 'ID Verification'),
        'MONITOR_POTENTIAL_COUPON_ABUSE' => env('FORTER_RECOMMENDATION_MSG_MONITOR_POTENTIAL_COUPON_ABUSE', 'Coupon Abuse'),
        'MONITOR_POTENTIAL_SELLER_COLLUSION' => env('FORTER_RECOMMENDATION_MSG_MONITOR_POTENTIAL_SELLER_COLLUSION', 'Seller Collusion'),
        'MONITOR_POTENTIAL_REFUND_ABUSE' => env('FORTER_RECOMMENDATION_MSG_MONITOR_POTENTIAL_REFUND_ABUSE', 'Refund Abuse'),
        'DECLINE_CHANEL_ABUSER' => env('FORTER_RECOMMENDATION_MSG_DECLINE_CHANEL_ABUSER', 'Channel Abuse'),
        'BORDERLINE' => env('FORTER_RECOMMENDATION_MSG_BORDERLINE', 'Borderline'),
    ],

    //========================================================================//

    // MESSAGING SERVICE

    // Messaging service is enabled by default, but can be turned off if needed
    'messaging_service_pull_enabled' => env('FORTER_MESSAGING_SERVICE_PULL_ENABLED', true),

    // Set messaging service pull frequency in cron format (if not defined, will run every minute)
    'messaging_service_pull_frequency' => env('FORTER_MESSAGING_SERVICE_PULL_FREQUENCY', '* * * * *'),

    // Messaging service to be used for CT subscriptions (should match the name on 'messaging_services')
    'messaging_service_type' => env('FORTER_MESSAGING_SERVICE_TYPE', 'sqs'),

    // Messaging services
    'messaging_services' => [
        'sqs' => [
            'key' => env('FORTER_AWS_SQS_ACCESS_KEY_ID', env('AWS_ACCESS_KEY_ID')),
            'secret' => env('FORTER_AWS_SQS_SECRET_ACCESS_KEY', env('AWS_SECRET_ACCESS_KEY')),
            'queue_url' => env('FORTER_AWS_SQS_QUEUE_URL'),
            'region' => env('FORTER_AWS_SQS_DEFAULT_REGION', env('AWS_DEFAULT_REGION', 'us-east-1')),
        ],
        // Support for other services may be added in the future
    ],

    //========================================================================//

    // ADVANCED

    // If not defined, a secret would be generated from Commercetools & Forter secrets.
    'api_extensions_basic_auth_secret' => env('CTP_API_EXTENSIONS_BASIC_AUTH_SECRET', ''),

    // Use Laravel's queue for spreading jobs into separate async processes (see Laravel docs for configuration instructions)
    'use_async_queue_for_jobs' => env('FORTER_USE_ASYNC_QUEUE_FOR_JOBS', false),

    //========================================================================//

    // TESTING

    'test' => [
        // When enabled missing order/payment/transaction data will be pupulated with test data before processing
        'missing_data_mocking_enabled' => env('FORTER_TEST_MISSING_DATA_MOCKING_ENABLED', false),

        'approve_order_id' => env('FORTER_TEST_APPROVE_ORDER_ID', ''),
        'decline_order_id' => env('FORTER_TEST_DECLINE_ORDER_ID', ''),
        'notreviewed_order_id' => env('FORTER_TEST_NOT_REVIEWED_ORDER_ID', ''),
    ]
];
