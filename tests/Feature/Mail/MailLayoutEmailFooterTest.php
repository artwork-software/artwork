<?php

namespace Tests\Feature\Mail;

use Artwork\Modules\GeneralSettings\Models\GeneralSettings;
use Illuminate\Mail\Markdown;
use Illuminate\Notifications\Messages\MailMessage;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class MailLayoutEmailFooterTest extends TestCase
{
    private function setEmailFooter(string $emailFooter): void
    {
        $generalSettings = app(GeneralSettings::class);
        $generalSettings->email_footer = $emailFooter;
        $generalSettings->save();
    }

    /**
     * @return array<string, mixed>
     */
    private function mailData(): array
    {
        return (new MailMessage())->line('Hallo aus dem Test')->data();
    }

    #[Test]
    public function html_mail_shows_the_configured_footer_escaped_with_line_breaks(): void
    {
        $this->setEmailFooter("Kulturhaus <b>Beispiel</b> GmbH\nMusterstraße 1");

        $html = (string) app(Markdown::class)->render('notifications::email', $this->mailData());

        $this->assertMatchesRegularExpression(
            '#Kulturhaus &lt;b&gt;Beispiel&lt;/b&gt; GmbH<br\s*/?>\s*Musterstraße 1#',
            $html
        );
        $this->assertStringNotContainsString('<b>Beispiel</b>', $html);
    }

    #[Test]
    public function text_mail_shows_the_configured_footer(): void
    {
        $this->setEmailFooter("Kulturhaus Beispiel GmbH\nMusterstraße 1");

        $text = (string) app(Markdown::class)->renderText('notifications::email', $this->mailData());

        $this->assertStringContainsString("Kulturhaus Beispiel GmbH\nMusterstraße 1", $text);
    }

    #[Test]
    public function mail_without_configured_footer_has_no_footer_block(): void
    {
        $this->setEmailFooter('');

        $html = (string) app(Markdown::class)->render('notifications::email', $this->mailData());

        $this->assertStringNotContainsString('padding:25px 50px 0px', $html);
    }
}
