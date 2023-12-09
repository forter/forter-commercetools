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

class DoNothing extends AbstractForterTest
{
    public const CONFIG = [
        'forter.pre_order_validation_enabled' => true,
        'forter.decision_actions.decline.pre' => 'DO_NOTHING',
    ];

    /**
     * @method test_forter_decision_actions_decline_pre_do_nothing_credit_card
     */
    public function test_forter_decision_actions_decline_pre_do_nothing_credit_card(): void
    {
        $this->checkForterPreDecisionActionsForPaymentMethodDoNothing('credit_card', 'decline');
    }

    /**
     * @method test_forter_decision_actions_decline_pre_do_nothing_paypal
     */
    public function test_forter_decision_actions_decline_pre_do_nothing_paypal(): void
    {
        $this->checkForterPreDecisionActionsForPaymentMethodDoNothing('credit_card', 'decline');
    }

    /**
     * @method test_forter_decision_actions_decline_pre_do_nothing_applepay
     */
    public function test_forter_decision_actions_decline_pre_do_nothing_applepay(): void
    {
        $this->checkForterPreDecisionActionsForPaymentMethodDoNothing('credit_card', 'decline');
    }
}
