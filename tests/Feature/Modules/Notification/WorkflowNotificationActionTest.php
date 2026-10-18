<?php

namespace Tests\Feature\Modules\Notification;

use Artwork\Modules\Notification\Enums\NotificationEnum;
use Artwork\Modules\User\Models\User;
use Artwork\Modules\Workflow\Actions\ActionResolver;
use Artwork\Modules\Workflow\Models\WorkflowInstance;
use Illuminate\Notifications\ChannelManager;
use Illuminate\Support\Facades\Notification;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

/**
 * Workflow-Aktion „notification“: rief ein nicht existierendes sendToUsers() auf verketteten
 * void-Settern auf – ein \Error, den WorkflowService (catch \Exception) nicht abfing.
 */
final class WorkflowNotificationActionTest extends FeatureTestCase
{
    #[Test]
    public function the_workflow_action_notifies_every_given_user_in_their_language(): void
    {
        Notification::swap(new ChannelManager($this->app));
        $single = User::factory()->create(['language' => 'de']);
        $listed = User::factory()->create(['language' => 'en']);
        $action = app(ActionResolver::class)->resolve('notification');
        $parameters = ['message' => 'Bitte Dienstplan prüfen', 'user_id' => $single->id, 'user_ids' => [$listed->id]];

        $this->assertTrue($action->canExecute(new WorkflowInstance(), $parameters));
        $action->execute(new WorkflowInstance(), $parameters);

        foreach ([$single, $listed] as $recipient) {
            $data = $recipient->notifications()->sole()->data;
            $this->assertSame(NotificationEnum::NOTIFICATION_NEW_SHIFT_COMMIT_WORKFLOW_REQUEST->value, $data['type']);
            $this->assertSame('Bitte Dienstplan prüfen', $data['description'][1]['title']);
            $this->assertSame([], $data['buttons']);
        }
        $this->assertSame('Workflow-Benachrichtigung', $single->notifications()->sole()->data['title']);
        $this->assertSame('Workflow notification', $listed->notifications()->sole()->data['title']);
    }
}
