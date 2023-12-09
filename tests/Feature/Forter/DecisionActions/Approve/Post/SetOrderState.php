<?php
/**
 * Forter Commercetools app
 *
 * @package  Forter Commercetools app
 * @author   Forter (https://www.forter.com/)
 * @author   Developer: Pniel Cohen (Trus)
 */

namespace Tests\Feature\Forter\DecisionActions\Approve\Post;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use Tests\Feature\Forter\AbstractForterTest;

class SetOrderState extends AbstractForterTest
{
    public const CONFIG = [
        'forter.post_order_validation_enabled' => true,
        'forter.decision_actions.approve.post' => 'SET_ORDER_STATE:TestOrderState',
    ];

    /**
     * @method test_forter_decision_actions_approve_post_set_order_state_credit_card
     */
    public function test_forter_decision_actions_approve_post_set_order_state_credit_card(): void
    {
        $this->checkForterPostDecisionActionsForPaymentMethod('credit_card', 'approve');
    }

    /**
     * @method test_forter_decision_actions_approve_post_set_order_state_paypal
     */
    public function test_forter_decision_actions_approve_post_set_order_state_paypal(): void
    {
        $this->checkForterPostDecisionActionsForPaymentMethod('paypal', 'approve');
    }

    /**
     * @method test_forter_decision_actions_approve_post_set_order_state_applepay
     */
    public function test_forter_decision_actions_approve_post_set_order_state_applepay(): void
    {
        $this->checkForterPostDecisionActionsForPaymentMethod('applepay', 'approve');
    }
}
