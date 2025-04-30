<?php
namespace App\Service;

use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Twig\Environment;

class DiscountNotificationService
{
    private $mailer;
    private $twig;

    public function __construct(MailerInterface $mailer, Environment $twig)
    {
        $this->mailer = $mailer;
        $this->twig = $twig;
    }

    public function sendDiscountNotification(string $productName, float $discountPercentage, string $recipientEmail)
    {
        $email = (new Email())
            ->from('Innomall.esprit@gmail.com')
            ->to($recipientEmail)
            ->subject('New Discount Available!')
            ->html($this->twig->render('emails/discount_notification.html.twig', [
                'productName' => $productName,
                'discountPercentage' => $discountPercentage
            ]));

        $this->mailer->send($email);
    }
}