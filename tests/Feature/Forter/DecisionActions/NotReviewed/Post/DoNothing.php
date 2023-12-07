<?php
/**
 * Forter Commercetools app
 *
 * @package  Forter Commercetools app
 * @author   Forter (https://www.forter.com/)
 * @author   Developer: Pniel Cohen (Trus)
 */

namespace Tests\Feature\Forter\DecisionActions\NotReviewed\Post;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use Tests\Feature\Forter\AbstractForterTest;

class DoNothing extends AbstractForterTest
{
    public const CONFIG = [
        'forter.post_order_validation_enabled' => true,
        'forter.decision_actions.not_reviewed.post' => 'DO_NOTHING',
    ];

    /**
     * @method test_forter_decision_actions_not_reviewed_post_do_nothing_credit_card
     */
    public function test_forter_decision_actions_not_reviewed_post_do_nothing_credit_card(): void
    {
        $this->checkForterPostDecisionActionsForPaymentMethod('credit_card', 'not reviewed');
    }

    /**
     * @method test_forter_decision_actions_not_reviewed_post_do_nothing_paypal
     */
    public function test_forter_decision_actions_not_reviewed_post_do_nothing_paypal(): void
    {
        $this->checkForterPostDecisionActionsForPaymentMethod('paypal', 'not reviewed');
    }

    /**
     * @method test_forter_decision_actions_not_reviewed_post_do_nothing_applepay
     */
    public function test_forter_decision_actions_not_reviewed_post_do_nothing_applepay(): void
    {
        $this->checkForterPostDecisionActionsForPaymentMethod('applepay', 'not reviewed');
    }
}
