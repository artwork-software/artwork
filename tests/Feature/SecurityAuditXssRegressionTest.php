<?php

namespace Tests\Feature;

use Artwork\Core\FileHandling\Naming\StoredFileName;
use Artwork\Modules\Chat\Models\Chat;
use Artwork\Modules\Project\Models\Comment;
use Artwork\Modules\Project\Models\Component;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\Project\Models\ProjectComponentValue;
use Artwork\Modules\User\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;

/**
 * Sicherheits-Audit 21.09.2026, Sofortmaßnahme 2 (Stored-XSS-Klasse).
 *
 * Nutzertext wird als Rohtext gespeichert (kein nl2br, kein HTML-Fragment) und vom Frontend
 * per {{ }} + white-space: pre-line gerendert. Die Datei-Endung auf der Platte folgt dem
 * erkannten Inhalt, nie dem Client-Namen; HTML/SVG/XML landen niemals unter /storage.
 */
final class SecurityAuditXssRegressionTest extends FeatureTestCase
{
    private const PAYLOAD = "<img src=x onerror=1>\nzweite Zeile";

    // Kleinstes gültiges PNG (1x1, transparent) - erkannt als image/png.
    private const PNG_1X1_BASE64 =
        'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg==';

    /**
     * Laravels Test-Fake (Illuminate\Http\Testing\File) rät den MIME-Typ aus dem NAMEN, nicht
     * aus dem Inhalt - genau das Verhalten, das hier geprüft werden soll, wäre damit unsichtbar.
     * Deshalb echte UploadedFile-Instanzen aus Temp-Dateien (Sniffing per finfo wie im Betrieb).
     */
    private function realUpload(string $clientName, string $content): UploadedFile
    {
        $path = tempnam(sys_get_temp_dir(), 'xss-audit-');
        file_put_contents($path, $content);

        return new UploadedFile($path, $clientName, null, null, true);
    }

    private function pngBytes(): string
    {
        return base64_decode(self::PNG_1X1_BASE64);
    }

    #[Test]
    public function comment_text_is_stored_as_raw_text_without_nl2br(): void
    {
        $this->actingAsAdmin();
        $project = Project::factory()->create();

        $this->post(route('comments.store'), [
            'project_id' => $project->id,
            'text' => self::PAYLOAD,
        ])->assertSessionHasNoErrors();

        $comment = Comment::query()->where('project_id', $project->id)->firstOrFail();

        $this->assertSame(self::PAYLOAD, $comment->text);
        $this->assertStringNotContainsString('<br', $comment->text);
    }

    #[Test]
    public function project_component_text_value_is_stored_as_raw_text_without_nl2br(): void
    {
        $this->actingAsAdmin();
        $project = Project::factory()->create();
        $component = Component::create([
            'name' => 'Text',
            'type' => 'TextArea',
            'data' => [],
            'permission_type' => 'allSeeAndEdit',
        ]);

        $response = $this->patch(route('project.tab.component.update', [
            'project' => $project->id,
            'component' => $component->id,
        ]), ['data' => ['text' => self::PAYLOAD]]);
        $this->assertContains($response->status(), [200, 302]);

        $value = ProjectComponentValue::query()
            ->where('project_id', $project->id)
            ->where('component_id', $component->id)
            ->firstOrFail();

        $this->assertSame(self::PAYLOAD, $value->data['text']);
        $this->assertStringNotContainsString('<br', $value->data['text']);
    }

    #[Test]
    public function chat_message_round_trips_as_raw_text_without_nl2br(): void
    {
        $member = User::factory()->create();
        $chat = new Chat(['is_group' => true]);
        $chat->created_by = $member->id;
        $chat->save();
        $chat->users()->attach($member->id);

        $this->actingAs($member);

        $this->postJson(route('chat-system.send-message', $chat), ['message' => self::PAYLOAD])
            ->assertSuccessful();

        $messages = $this->getJson(route('chat-system.get-chat-messages', $chat))
            ->assertOk()
            ->json('messages.data');

        $this->assertCount(1, $messages);
        $this->assertSame(self::PAYLOAD, $messages[0]['message']);
        $this->assertStringNotContainsString('<br', $messages[0]['message']);
    }

    #[Test]
    public function png_content_with_html_client_name_is_stored_with_png_extension(): void
    {
        $file = $this->realUpload('x.html', $this->pngBytes());

        $name = StoredFileName::forUpload($file);

        $this->assertMatchesRegularExpression(StoredFileName::PATTERN, $name);
        $this->assertStringEndsWith('.png', $name);
    }

    #[Test]
    public function html_content_with_image_client_name_never_keeps_a_renderable_extension(): void
    {
        $file = $this->realUpload(
            'x.png',
            "<!DOCTYPE html>\n<html><head><title>x</title></head><body><script>alert(1)</script></body></html>"
        );

        $this->assertStringEndsWith('.bin', StoredFileName::forUpload($file));
    }

    #[Test]
    public function svg_and_xml_content_is_rewritten_to_bin(): void
    {
        $svg = $this->realUpload(
            'logo.svg',
            '<?xml version="1.0" encoding="UTF-8"?>' . "\n"
            . '<svg xmlns="http://www.w3.org/2000/svg" width="1" height="1"><script>alert(1)</script></svg>'
        );
        $xml = $this->realUpload('data.xml', '<?xml version="1.0" encoding="UTF-8"?>' . "\n" . '<root><a/></root>');

        $this->assertStringEndsWith('.bin', StoredFileName::forUpload($svg));
        $this->assertStringEndsWith('.bin', StoredFileName::forUpload($xml));
    }

    #[Test]
    public function unrecognisable_content_with_html_client_name_is_rewritten_to_bin(): void
    {
        // Nullbytes: finfo meldet application/octet-stream -> Client-Endung greift, ist aber gesperrt.
        $file = $this->realUpload('x.html', str_repeat("\0", 64));

        $this->assertStringEndsWith('.bin', StoredFileName::forUpload($file));
    }

    #[Test]
    public function client_extension_matching_the_detected_type_is_kept(): void
    {
        $file = $this->realUpload('photo.PNG', $this->pngBytes());

        $this->assertStringEndsWith('.png', StoredFileName::forUpload($file));
    }

    #[Test]
    public function user_photo_upload_rejects_non_image_content(): void
    {
        Storage::fake('public');
        $user = $this->actingAsAdmin();

        $this->post(route('user.update.photo', $user), [
            'photo' => $this->realUpload('x.png', "<!DOCTYPE html>\n<html><body><script>alert(1)</script></body></html>"),
        ])->assertSessionHasErrors('photo');

        $this->assertNull($user->fresh()->profile_photo_path);
    }

    #[Test]
    public function user_photo_upload_accepts_a_real_png(): void
    {
        Storage::fake('public');
        $user = $this->actingAsAdmin();

        $this->post(route('user.update.photo', $user), [
            'photo' => $this->realUpload('avatar.png', $this->pngBytes()),
        ])->assertSessionHasNoErrors();

        $path = $user->fresh()->profile_photo_path;
        $this->assertNotNull($path);
        $this->assertStringEndsWith('.png', $path);
        Storage::disk('public')->assertExists($path);
    }

    #[Test]
    public function key_visual_upload_rejects_non_image_content(): void
    {
        $this->actingAsAdmin();
        $project = Project::factory()->create();

        $this->post(route('projects_key_visual.update', $project), [
            'keyVisual' => $this->realUpload('x.jpg', "<!DOCTYPE html>\n<html><body><script>alert(1)</script></body></html>"),
        ])->assertSessionHasErrors('keyVisual');

        $this->assertNull($project->fresh()->key_visual_path);
    }
}
