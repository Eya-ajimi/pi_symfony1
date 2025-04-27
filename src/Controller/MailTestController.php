<?php
// src/Controller/MailTestController.php
namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Annotation\Route;

class MailTestController extends AbstractController
{
    #[Route('/send-mail', name: 'app_send_mail')]
    public function sendMail(MailerInterface $mailer): Response
    {
        // 1) Compose l'e-mail
        $email = (new Email())
            ->from('Innomall.esprit@gmail.com')
            ->to('tunisieadcreatif@gmail.com')
            ->subject('Test SMTP Gmail – Symfony')
            ->text("Bonjour,\n\nCeci est un test d'envoi SMTP via Gmail dans Symfony 6.4.\n\nÀ bientôt !")
            ->html('<p>Bonjour,</p><p>Ceci est un <strong>test</strong> d\'envoi SMTP via Gmail dans Symfony 6.4.</p><p>À bientôt !</p>');

        // 2) Envoie
        $mailer->send($email);

        // 3) Retourne une réponse
        return new Response('E-mail envoyé avec succès via Gmail SMTP !');
    }
}
