<?php
/**
 * Forter Commercetools app
 *
 * @package  Forter Commercetools app
 * @author   Forter (https://www.forter.com/)
 * @author   Developer: Pniel Cohen (Trus)
 */

namespace Tests\Feature\Forter\DecisionActions\Decline\Pre;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use Tests\Feature\Forter\AbstractForterTest;

class BlockOrderPlace extends AbstractForterTest
{
    public const CONFIG = [
        'forter.pre_order_validation_enabled' => true,
        'forter.decision_actions.decline.pre' => 'BLOCK_ORDER_PLACE',
    ];

    /**
     * @method test_forter_decision_actions_decline_pre_block_order_place_credit_card
     */
    public function test_forter_decision_actions_decline_pre_block_order_place_credit_card(): void
    {
        $this->checkForterPreDecisionActionsForPaymentMethodBlockOrderPlace('credit_card', 'decline');
    }

    /**
     * @method test_forter_decision_actions_decline_pre_block_order_place_paypal
     */
    public function test_forter_decision_actions_decline_pre_block_order_place_paypal(): void
    {
        $this->checkForterPreDecisionActionsForPaymentMethodBlockOrderPlace('paypal', 'decline');
    }

    /**
     * @method test_forter_decision_actions_decline_pre_block_order_place_applepay
     */
    public function test_forter_decision_actions_decline_pre_block_order_place_applepay(): void
    {
        $this->checkForterPreDecisionActionsForPaymentMethodBlockOrderPlace('applepay', 'decline');
    }
}
