<?php

namespace Tests\Unit;

use App\Models\PurchaseRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\TestCase;

class StateMachineTest extends TestCase
{
    use RefreshDatabase;

    private function makePurchaseRequest(string $status = 'draft'): PurchaseRequest
    {
        $user = User::factory()->create();

        return PurchaseRequest::create([
            'number'       => 'PR-' . uniqid(),
            'requested_by' => $user->id,
            'status'       => $status,
            'priority'     => 'normal',
        ]);
    }

    public function test_draft_can_transition_to_pending()
    {
        $pr = $this->makePurchaseRequest('draft');
        $this->assertTrue($pr->canTransitionTo('pending'));

        $pr->transitionTo('pending');
        $this->assertEquals('pending', $pr->status);
    }

    public function test_draft_cannot_transition_to_approved()
    {
        $pr = $this->makePurchaseRequest('draft');
        $this->assertFalse($pr->canTransitionTo('approved'));

        $this->expectException(InvalidArgumentException::class);
        $pr->transitionTo('approved');
    }

    public function test_pending_can_transition_to_approved()
    {
        $pr = $this->makePurchaseRequest('pending');
        $this->assertTrue($pr->canTransitionTo('approved'));

        $pr->transitionTo('approved');
        $this->assertEquals('approved', $pr->status);
    }

    public function test_pending_can_transition_to_rejected()
    {
        $pr = $this->makePurchaseRequest('pending');
        $this->assertTrue($pr->canTransitionTo('rejected'));

        $pr->transitionTo('rejected');
        $this->assertEquals('rejected', $pr->status);
    }

    public function test_approved_can_transition_to_converted()
    {
        $pr = $this->makePurchaseRequest('approved');
        $this->assertTrue($pr->canTransitionTo('converted'));

        $pr->transitionTo('converted');
        $this->assertEquals('converted', $pr->status);
    }

    public function test_rejected_can_transition_back_to_draft()
    {
        $pr = $this->makePurchaseRequest('rejected');
        $this->assertTrue($pr->canTransitionTo('draft'));

        $pr->transitionTo('draft');
        $this->assertEquals('draft', $pr->status);
    }

    public function test_converted_is_final_state()
    {
        $pr = $this->makePurchaseRequest('converted');

        $this->assertFalse($pr->canTransitionTo('draft'));
        $this->assertFalse($pr->canTransitionTo('pending'));
        $this->assertFalse($pr->canTransitionTo('approved'));
        $this->assertFalse($pr->canTransitionTo('rejected'));
    }

    public function test_transition_to_and_save_persists_status()
    {
        $pr = $this->makePurchaseRequest('draft');
        $pr->transitionToAndSave('pending');

        $pr->refresh();
        $this->assertEquals('pending', $pr->status);
    }

    public function test_require_status_returns_true_when_met()
    {
        $pr = $this->makePurchaseRequest('draft');
        $this->assertTrue($pr->requireStatus('draft'));
        $this->assertTrue($pr->requireStatus(['draft', 'pending']));
    }

    public function test_require_status_returns_message_when_not_met()
    {
        $pr = $this->makePurchaseRequest('draft');
        $result = $pr->requireStatus('approved');
        $this->assertIsString($result);
        $this->assertStringContainsString('approved', $result);
    }

    public function test_full_purchase_request_lifecycle()
    {
        $pr = $this->makePurchaseRequest('draft');

        // draft → pending
        $pr->transitionTo('pending');
        $this->assertEquals('pending', $pr->status);

        // pending → approved
        $pr->transitionTo('approved');
        $this->assertEquals('approved', $pr->status);

        // approved → converted
        $pr->transitionTo('converted');
        $this->assertEquals('converted', $pr->status);
    }

    public function test_rejection_and_resubmission_flow()
    {
        $pr = $this->makePurchaseRequest('draft');

        // draft → pending → rejected → draft → pending → approved
        $pr->transitionTo('pending');
        $pr->transitionTo('rejected');
        $this->assertEquals('rejected', $pr->status);

        $pr->transitionTo('draft');
        $this->assertEquals('draft', $pr->status);

        $pr->transitionTo('pending');
        $pr->transitionTo('approved');
        $this->assertEquals('approved', $pr->status);
    }
}
