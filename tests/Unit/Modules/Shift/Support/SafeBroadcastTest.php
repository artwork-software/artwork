<?php

namespace Tests\Unit\Modules\Shift\Support;

use Artwork\Modules\Shift\Support\SafeBroadcast;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Psr7\Request;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Test;
use RuntimeException;
use Tests\TestCase;

final class SafeBroadcastTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        SafeBroadcastTestEvent::$attempts = 0;
        SafeBroadcastTestEvent::$fail = false;
        SafeBroadcastTestEvent::$previous = null;
        SafeBroadcastTestEvent::$failureMessage = 'Pusher error: cURL error 7: Failed to connect to reverb port 8080.';
    }

    #[Test]
    public function a_transport_failure_is_reported_and_short_circuits_the_rest_of_the_request(): void
    {
        SafeBroadcastTestEvent::$fail = true;

        SafeBroadcast::send(new SafeBroadcastTestEvent());
        SafeBroadcast::send(new SafeBroadcastTestEvent());
        SafeBroadcast::send(new SafeBroadcastTestEvent());

        // Kein Fehler nach außen, und nach dem ersten Fehler kein weiterer Versuch
        // (hängender Server = Timeout je Versuch)
        $this->assertSame(1, SafeBroadcastTestEvent::$attempts);
    }

    #[Test]
    public function a_failure_of_a_single_event_does_not_short_circuit_the_other_broadcasts(): void
    {
        // z. B. Fehler in broadcastWith oder vom Server abgelehnte (zu große) Nutzlast – kein Transportproblem
        SafeBroadcastTestEvent::$fail = true;
        SafeBroadcastTestEvent::$failureMessage = 'Pusher error: Payload too large.';

        SafeBroadcast::send(new SafeBroadcastTestEvent());
        SafeBroadcast::send(new SafeBroadcastTestEvent());

        $this->assertSame(2, SafeBroadcastTestEvent::$attempts);
    }

    #[Test]
    public function a_connect_exception_deep_in_the_chain_short_circuits(): void
    {
        SafeBroadcastTestEvent::$fail = true;
        SafeBroadcastTestEvent::$failureMessage = 'Broadcast fehlgeschlagen';
        SafeBroadcastTestEvent::$previous = new ConnectException(
            'Verbindung abgelehnt',
            new Request('POST', 'http://reverb.test')
        );

        SafeBroadcast::send(new SafeBroadcastTestEvent());
        SafeBroadcast::send(new SafeBroadcastTestEvent());

        $this->assertSame(1, SafeBroadcastTestEvent::$attempts);
    }

    #[Test]
    public function the_short_circuit_is_reset_for_the_next_request_or_job(): void
    {
        SafeBroadcastTestEvent::$fail = true;
        SafeBroadcast::send(new SafeBroadcastTestEvent());

        // Octane/Queue-Worker: scoped Instanzen werden je Request/Job verworfen
        $this->app->forgetScopedInstances();
        SafeBroadcastTestEvent::$fail = false;
        SafeBroadcast::send(new SafeBroadcastTestEvent());

        $this->assertSame(2, SafeBroadcastTestEvent::$attempts);
    }

    #[Test]
    public function broadcasts_inside_a_transaction_are_sent_after_the_commit(): void
    {
        DB::transaction(function (): void {
            SafeBroadcast::send(new SafeBroadcastTestEvent());
            $this->assertSame(0, SafeBroadcastTestEvent::$attempts);
        });

        $this->assertSame(1, SafeBroadcastTestEvent::$attempts);
    }

    #[Test]
    public function broadcasts_of_a_rolled_back_transaction_are_dropped(): void
    {
        try {
            DB::transaction(static function (): void {
                SafeBroadcast::send(new SafeBroadcastTestEvent());
                throw new RuntimeException('Rollback');
            });
        } catch (RuntimeException) {
        }

        $this->assertSame(0, SafeBroadcastTestEvent::$attempts);
    }

    #[Test]
    public function a_failing_broadcast_after_commit_does_not_break_the_commit(): void
    {
        SafeBroadcastTestEvent::$fail = true;

        $result = DB::transaction(static function (): string {
            SafeBroadcast::send(new SafeBroadcastTestEvent());

            return 'gespeichert';
        });

        $this->assertSame('gespeichert', $result);
        $this->assertSame(1, SafeBroadcastTestEvent::$attempts);
    }
}
