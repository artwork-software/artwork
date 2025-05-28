<?php

namespace Artwork\Modules\Inventory\Notifications;

use Artwork\Core\Notifications\BaseNotification;
use Artwork\Modules\GeneralSettings\Models\GeneralSettings;
use Illuminate\Config\Repository;
use Illuminate\Notifications\Messages\MailMessage;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

class InventoryArticleNotification extends BaseNotification
{
    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function toMail(): MailMessage
    {
        $settings = app(GeneralSettings::class);
        $config = app(Repository::class);
        $systemMail = $config->get('mail.system_mail');
        $pageTitle = $settings->page_title !== '' ? $settings->page_title : $config->get('mail.fallback_page_title');
        return (new MailMessage())
            ->from(
                $settings->business_email !== '' ? $settings->business_email : $systemMail,
                $pageTitle
            )
            ->subject($this->notificationData->title)
            ->markdown(
                'emails.simple-mail',
                [
                    'notification' => $this->notificationData,
                    'pageTitle' => $pageTitle,
                ]
            );
    }
}
