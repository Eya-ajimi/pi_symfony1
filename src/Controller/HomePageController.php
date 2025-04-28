<?php

namespace App\Controller;

use App\Entity\Post;
use App\Entity\Commentaire;
use App\Entity\SousCommentaire;
use App\Form\PostType;
use App\Form\CommentType;
use App\Form\ReplyType;
use App\Repository\PostRepository;
use App\Repository\UtilisateurRepository;
use App\Repository\AtmRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Entity\Schedule;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
class HomePageController extends AbstractController
{
    //* impoort the mail interface to send teh mail 
    private MailerInterface $mailer;

    public function __construct(MailerInterface $mailer)
    {
        $this->mailer = $mailer;
    }
    #[Route('/home', name: 'app_home_page', methods: ['GET', 'POST'])]
    public function index(
        Request $request,
        PostRepository $postRepository,
        UtilisateurRepository $utilisateurRepo,
        EntityManagerInterface $em,
        AtmRepository $atmRepository
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_CLIENT');
         #$user = $this->getUser();
        $utilisateur = $this->getUser();

        if (!$utilisateur) {
            throw $this->createNotFoundException('Utilisateur avec ID 7 non trouvé');
        }

        // 1. Handle Post Creation
        $post = new Post();
        $postForm = $this->createForm(PostType::class, $post);
        $postForm->handleRequest($request);

        if ($postForm->isSubmitted() && $postForm->isValid()) {
            $post->setUtilisateur($utilisateur);
            $post->setDateCreation(new \DateTime());

            $em->persist($post);
            $em->flush();

            $this->addFlash('success', 'Post created successfully!');
            return $this->redirectToRoute('app_home_page');
        }

        $posts = $postRepository->findAllWithComments();

        // 2. Handle Comment Forms
        $submittedPostId = $request->request->get('comment_post_id');
        $commentForms = [];

        foreach ($posts as $p) {
            $comment = new Commentaire();
            $comment->setPost($p);
            $comment->setUtilisateur($utilisateur);
            $comment->setDateCreation(new \DateTime());

            $form = $this->createForm(CommentType::class, $comment, [
                'action' => $this->generateUrl('app_home_page')
            ]);

            if ((string) $p->getId() === $submittedPostId) {
                $form->handleRequest($request);
                if ($form->isSubmitted() && $form->isValid()) {
                    $em->persist($comment);
                    $em->flush();

                    $this->addFlash('success', 'Commentaire ajouté !');
                    return $this->redirectToRoute('app_home_page');
                }
            }

            $commentForms[$p->getId()] = $form;
        }

        // 3. Handle Reply Forms
        $submittedReplyId = $request->request->get('reply_comment_id');
        $replyForms = [];

        foreach ($posts as $p) {
            foreach ($p->getCommentaires() as $comment) {
                $reply = new SousCommentaire();
                $reply->setCommentaire($comment);
                $reply->setUtilisateur($utilisateur);
                $reply->setDateCreation(new \DateTime());

                $form = $this->createForm(ReplyType::class, $reply, [
                    'action' => $this->generateUrl('app_home_page')
                ]);

                if ((string) $comment->getId() === $submittedReplyId) {
                    $form->handleRequest($request);
                    if ($form->isSubmitted() && $form->isValid()) {
                        $em->persist($reply);
                
                        // Vérifie si l'auteur du reply == auteur du post
                        $post = $comment->getPost();
                        $postAuthor = $post->getUtilisateur();
                        $replyAuthor = $reply->getUtilisateur();
                        $commentAuthor = $comment->getUtilisateur();
                
                        if ($replyAuthor === $postAuthor && $replyAuthor !== $commentAuthor) {
                            $commentAuthor->setPoints($commentAuthor->getPoints() + 20);
                            $em->persist($commentAuthor);
                            $em->flush();
                        
                            // Creation de  colorful and designed email
                            $email = (new Email())
                                ->from('ajimieya1@gmail.com')
                                ->to($commentAuthor->getEmail())
                                ->subject('🎉 You earned 20 points! 🎉')
                                ->html(
                                    $this->renderView('home_page/win_notification.html.twig', [
                                        'name' => $commentAuthor->getNom(),
                                        'points' => 20,  // Points earned
                                    ])
                                )
                                ->text(
                                    "Hi " . $commentAuthor->getNom() . ",\n\n" . 
                                    "Congratulations! 🎉 Thanks to your participation, you have earned 20 points in InnoMall.\n\n" . 
                                    "Keep up the great work!"
                                );
                        
                            // Send the email
                            $this->mailer->send($email);
                        }
                        
                        
                
                        //dd($commentAuthor);
                        $em->flush();
                
                        $this->addFlash('success', 'Réponse ajoutée !');
                        return $this->redirectToRoute('app_home_page');
                    }
                }
                

                $replyForms[$comment->getId()] = $form;
            }
        }

        // 4. Handle Editing Post
        if ($request->isMethod('POST') && $request->request->has('post_id')) {
            $post = $postRepository->find($request->request->get('post_id'));
            if ($post) {
                $post->setContenu($request->request->get('content'));
                $em->flush();
                $this->addFlash('success', 'Post updated successfully');
                return $this->redirectToRoute('app_home_page');
            }
        }

        // 5. Handle Editing Comment
        if ($request->isMethod('POST') && $request->request->has('comment_id')) {
            $comment = $em->getRepository(Commentaire::class)->find($request->request->get('comment_id'));
            if ($comment) {
                $comment->setContenu($request->request->get('comment_content'));
                $em->flush();
                $this->addFlash('success', 'Commentaire modifié avec succès');
                return $this->redirectToRoute('app_home_page');
            }
        }

        // 6. Handle Editing Reply
        if ($request->isMethod('POST') && $request->request->has('reply_id')) {
            $reply = $em->getRepository(SousCommentaire::class)->find($request->request->get('reply_id'));
            if ($reply) {
                $reply->setContenu($request->request->get('reply_content'));
                $em->flush();
                $this->addFlash('success', 'Reply updated successfully');
                return $this->redirectToRoute('app_home_page');
            }
        }


           


        // 7. ATM & View
        $atms = $atmRepository->findAll();

        return $this->render('home_page/home.html.twig', [
            'posts' => $posts,
            'postForm' => $postForm->createView(),
            'commentForms' => array_map(fn($f) => $f->createView(), $commentForms),
            'replyForms' => array_map(fn($f) => $f->createView(), $replyForms),
            'edit_post_id' => $request->query->get('edit'),
            'edit_comment_id' => $request->query->get('edit_comment'),
            'edit_reply_id' => $request->query->get('edit_reply'),
            'atms' => $atms,
            //'shopData' => $shopData, 
            
        ]);
    }


 

}
