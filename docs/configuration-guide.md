# Configuration Guide

This guide will explain the available configuration options that are relevant to the `Forter Commercetools App` context.
- For an easy start, you may duplicate the file `.env.example` from the app's root dir, and name it `.env`.
- In addition or as an alternative to the `.env` file, environment variables may be set on the server level as well.
- For instructions on how to configure custom Forter schema mapping - see [this guide](./forter-schema-custom-mapping-guide.md).

Let's break down the required settings.

#### General Laravel App Settings
*These are the mandatory keys for this app, other common Laravel configurations may be added as needed.
| Configuration Key          | Description                                                | Type            |
| -------------------------- | ---------------------------------------------------------- | --------------- |
| APP_URL                    | App public URL (including https, without trailing slashes) | string          |
| APP_PORT                   | App Port (optional, if needed)                             | string/int/null |

#### Commercetools API Credentials (see [this guide](./how-to-get-commercetools-api-credentials.md))
| Configuration Key          | Description                  | Type   |
| -------------------------- | ---------------------------- | ------ |
| CTP_PROJECT_KEY            | Commercetools project key    | string |
| CTP_CLIENT_SECRET          | Commercetools client secret  | string |
| CTP_CLIENT_ID              | Commercetools client ID      | string |
| CTP_AUTH_URL               | Commercetools auth URL       | string |
| CTP_API_URL                | Commercetools API URL        | string |
| CTP_SCOPES                 | Commercetools scopes         | string |
| CTP_REGION                 | Commercetools region         | string |

#### Forter API Credentials (Copy from your [Forter Portal settings page](https://portal.forter.com/app/onboarder/settings/general))
| Configuration Key          | Description                  | Type   |
| -------------------------- | ---------------------------- | ------ |
| FORTER_SITE_ID             | Forter site ID               | string |
| FORTER_SECRET_KEY          | Forter secret key            | string |

#### Forter Order Validation Settings (pre/post auth)
| Configuration Key                                         | Description                                                                     | Type |
| --------------------------------------------------------- | ------------------------------------------------------------------------------- | ---- |
| FORTER_PRE_ORDER_VALIDATION_ENABLED                       | Enable/Disable pre-auth order validation                                        | bool |
| FORTER_POST_ORDER_VALIDATION_ENABLED                      | Enable/Disable post-auth order validation                                       | bool |
| FORTER_POST_ORDER_VALIDATION_REQUIRE_PAYMENT_TRANSACTION  | Require order to have at least one payment transaction for post-auth validation | bool |

#### Forter Decision Actions
| Configuration Key                        | Description                                                | Type   |
| ---------------------------------------- | ---------------------------------------------------------- | ------ |
| FORTER_DECISION_ACTION_APPROVE_PRE       | Action to take on pre-auth `approve` Forter decision       | string (see options below) |
| FORTER_DECISION_ACTION_APPROVE_POST      | Action to take on post-auth `approve` Forter decision      | string (see options below) |
| FORTER_DECISION_ACTION_DECLINE_PRE       | Action to take on pre-auth `decline` Forter decision       | string (see options below) |
| FORTER_DECISION_ACTION_DECLINE_POST      | Action to take on post-auth `decline` Forter decision      | string (see options below) |
| FORTER_DECISION_ACTION_NOT_REVIEWED_PRE  | Action to take on pre-auth `not reviewed` Forter decision  | string (see options below) |
| FORTER_DECISION_ACTION_NOT_REVIEWED_POST | Action to take on post-auth `not reviewed` Forter decision | string (see options below) |

##### Forter Decision Action Options:
* `DO_NOTHING` (or leave empty).
* `SET_ORDER_STATE:orderStateKey` (replace `orderStateKey` with the desired orderState. e.g., `SET_ORDER_STATE:Cancelled`).
* `BLOCK_ORDER_PLACE` (only available on pre-decline).

#### Messaging Service Settings
| Configuration Key                         | Description                                                                         | Type   |
| --------------------------------------- | ------------------------------------------------------------------------------------- | ------ |
| FORTER_MESSAGING_SERVICE_PULL_ENABLED   | Messaging service pulling is enabled by default, but can be turned off if needed      | bool   |
| FORTER_MESSAGING_SERVICE_PULL_FREQUENCY | Set messaging service pull frequency in cron format ("* * * * *")                     | string |
| FORTER_MESSAGING_SERVICE_TYPE           | Messaging service to be used for CT subscriptions (currently only "sqs" is supported) | string |

#### Amazon SQS Credentials (see [this guide](https://docs.aws.amazon.com/AWSSimpleQueueService/latest/SQSDeveloperGuide/sqs-setting-up.html))
| Configuration Key          | Description                     | Type   |
| -------------------------------- | ------------------------- | ------ |
| FORTER_AWS_SQS_ACCESS_KEY_ID     | Amazon SQS access key ID     | string |
| FORTER_AWS_SQS_SECRET_ACCESS_KEY | Amazon SQS secret access key | string |
| FORTER_AWS_SQS_DEFAULT_REGION    | Amazon SQS region            | string |
| FORTER_AWS_SQS_QUEUE_URL         | Amazon SQS queue URL         | string |

*Requied when FORTER_MESSAGING_SERVICE_TYPE is set to "sqs" (currently the default)

#### Other Optional Settings
| Configuration Key               | Description                                                                        | Type |
| ------------------------------- | ---------------------------------------------------------------------------------- | ---- |
| FORTER_IS_ENABLED               | Enable/Disable the app's main activity if needed                                   | bool |
| FORTER_USE_ASYNC_QUEUE_FOR_JOBS | Use Laravel's queue for running jobs asynchronously (requires additional settings) | bool |

*See this guide for instructions on [how to run Laravel's queue worker](https://laravel.com/docs/10.x/queues#running-the-queue-worker) if needed, and additional queue settings.

##### Custom Recommendation Handlers
If there's a need to use a custom handler for a Forter recommendation, you may duplicate one of the example classes under `\App\Lib\Forter\RecommendationHandlers\Custom`, and follow the instructions on the comments for a quick start.

---

#### Sample Configuration File (`.env`)

```
##################################
###  Forter Commercetools App  ###
##################################

## App ##
APP_NAME="Forter Commercetools App"
APP_ENV=local
APP_KEY=
APP_DEBUG=true
APP_URL=https://{app-domain}
#APP_PORT=

CACHE_DRIVER=file
FILESYSTEM_DISK=local

## Logging ##
LOG_CHANNEL=daily
LOG_DEPRECATIONS_CHANNEL=null
LOG_LEVEL=debug

#################################
##  Commercetools Credentials  ##
#################################

CTP_PROJECT_KEY=
CTP_CLIENT_SECRET=
CTP_CLIENT_ID=
CTP_AUTH_URL=
CTP_API_URL=
CTP_SCOPES="keep this value wrapped with quotes"
CTP_REGION=

##########################
##  Forter Credentials  ##
##########################

FORTER_SITE_ID=
FORTER_SECRET_KEY=

#################################################################
##  Forter Order Validation Settings (pre/post authorization)  ##
#################################################################

# Enable/Disable pre-auth order validation
FORTER_PRE_ORDER_VALIDATION_ENABLED=true

# Enable/Disable post-auth order validation
FORTER_POST_ORDER_VALIDATION_ENABLED=true

# Require order to have at least one payment transaction for post-auth validation
FORTER_POST_ORDER_VALIDATION_REQUIRE_PAYMENT_TRANSACTION=true

#########################################################################
##  Forter Decision Action Options:                                    ##
##    DO_NOTHING (or leave empty)                                      ##
##    SET_ORDER_STATE:orderStateKey (e.g., SET_ORDER_STATE:Cancelled)  ##
##    BLOCK_ORDER_PLACE (only available on pre-decline)                ##
#########################################################################

FORTER_DECISION_ACTION_APPROVE_PRE=SET_ORDER_STATE:Confirmed
FORTER_DECISION_ACTION_APPROVE_POST=SET_ORDER_STATE:Confirmed
FORTER_DECISION_ACTION_DECLINE_PRE=SET_ORDER_STATE:Cancelled
FORTER_DECISION_ACTION_DECLINE_POST=SET_ORDER_STATE:Cancelled
FORTER_DECISION_ACTION_NOT_REVIEWED_PRE=DO_NOTHING
FORTER_DECISION_ACTION_NOT_REVIEWED_POST=DO_NOTHING

#########################
##  Messaging Service  ##
#########################

# Enable/Disable messaging service
FORTER_MESSAGING_SERVICE_PULL_ENABLED=true
FORTER_MESSAGING_SERVICE_PULL_FREQUENCY="* * * * *"
FORTER_MESSAGING_SERVICE_TYPE=sqs

## Forter Amazon SQS Credentials ##
FORTER_AWS_SQS_ACCESS_KEY_ID=
FORTER_AWS_SQS_SECRET_ACCESS_KEY=
FORTER_AWS_SQS_DEFAULT_REGION=
FORTER_AWS_SQS_QUEUE_URL=

#==============================================================================#

###############################
##  Other Optional Settings  ##
###############################

# Enable/Disable the app's main activity if needed
FORTER_IS_ENABLED=true

# Use Laravel's queue for running jobs asynchronously (see Laravel docs for configuration instructions)
FORTER_USE_ASYNC_QUEUE_FOR_JOBS=false

# Pre-auth decline BLOCK_ORDER_PLACE error message
FORTER_PRE_DECLINE_BLOCK_ORDER_PLACE_ERROR_MSG="We are sorry, but we could not process your order at this time."

## Forter Recommendation handlers ##
#FORTER_RECOMMENDATION_HANDLER_ROUTING_RECOMMENDED="\\App\\Lib\\Custom\\ForterRecommendationHandlers\\RoutingRecommendedHandler"
#FORTER_RECOMMENDATION_HANDLER_ID_VERIFICATION_REQUIRED="\\App\\Lib\\Custom\\ForterRecommendationHandlers\\IdVerificationRequiredHandler"
#FORTER_RECOMMENDATION_HANDLER_MONITOR_POTENTIAL_COUPON_ABUSE="\\App\\Lib\\Custom\\ForterRecommendationHandlers\\MonitorPotentialCouponAbuseHandler"
#FORTER_RECOMMENDATION_HANDLER_MONITOR_POTENTIAL_SELLER_COLLUSION'="\\App\\Lib\\Custom\\ForterRecommendationHandlers\\MonitorPotentialSellerCollusionHandler"
#FORTER_RECOMMENDATION_HANDLER_MONITOR_POTENTIAL_REFUND_ABUSE="\\App\\Lib\\Custom\\ForterRecommendationHandlers\\MonitorPotentialRefundAbuseHandler"
#FORTER_RECOMMENDATION_HANDLER_DECLINE_CHANEL_ABUSER="\\App\\Lib\\Custom\\ForterRecommendationHandlers\\DeclineChanelAbuserHandler)"
#FORTER_RECOMMENDATION_HANDLER_BORDERLINE="\\App\\Lib\\Custom\\ForterRecommendationHandlers\\BorderlineHandler"

## Enable/Disable (all) recommendation handlers
#FORTER_RECOMMENDATION_HANDLERS_ENABLED=true

## Forter Recommendation messages ##
FORTER_RECOMMENDATION_MSG_ROUTING_RECOMMENDED="Re-route"
FORTER_RECOMMENDATION_MSG_ID_VERIFICATION_REQUIRED="ID Verification"
FORTER_RECOMMENDATION_MSG_MONITOR_POTENTIAL_COUPON_ABUSE="Coupon Abuse"
FORTER_RECOMMENDATION_MSG_MONITOR_POTENTIAL_SELLER_COLLUSION="Seller Collusion"
FORTER_RECOMMENDATION_MSG_MONITOR_POTENTIAL_REFUND_ABUSE="Refund Abuse"
FORTER_RECOMMENDATION_MSG_DECLINE_CHANEL_ABUSER="Channel Abuse"
FORTER_RECOMMENDATION_MSG_BORDERLINE="Borderline"
```
