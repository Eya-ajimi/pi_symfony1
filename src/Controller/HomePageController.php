<?php

namespace App\Controller;

use App\Repository\PostRepository;
use App\Repository\UtilisateurRepository;
use App\Form\PostType;
use App\Form\CommentType;
use App\Entity\Post;
use App\Entity\Commentaire;
use App\Entity\SousCommentaire;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

use App\Repository\AtmRepository;

class HomePageController extends AbstractController
{
    #[Route('/home', name: 'app_home_page', methods: ['GET', 'POST'])]
    public function index(
        Request $request, 
        PostRepository $postRepository,
        UtilisateurRepository $utilisateurRepo,
        EntityManagerInterface $em,
        AtmRepository $atmRepository
    ): Response {
        // 1. Handle Post Creation
        $post = new Post();
        $postForm = $this->createForm(PostType::class, $post);
        $postForm->handleRequest($request);

        if ($postForm->isSubmitted() && $postForm->isValid()) {
            $utilisateur = $utilisateurRepo->find(9);
            if (!$utilisateur) {
                throw $this->createNotFoundException('Utilisateur avec ID 9 non trouvé');
            }

            $post->setUtilisateur($utilisateur);
            $post->setDateCreation(new \DateTime());

            $em->persist($post);
            $em->flush();

            $this->addFlash('success', 'Post created successfully!');
            return $this->redirectToRoute('app_home_page');
        }

        // 2. Handle Comment Forms (only handle one matching post form)
        $submittedPostId = $request->request->get('comment_post_id');
        $commentForms = [];

        foreach ($postRepository->findAll() as $p) {
            $comment = new Commentaire();
            $comment->setPost($p);
            $comment->setUtilisateur($utilisateurRepo->find(9));
            $comment->setDateCreation(new \DateTime());

            $form = $this->createForm(CommentType::class, $comment, [
                'action' => $this->generateUrl('app_home_page')
            ]);

            // Handle only the form submitted for this post
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

        // 3. Handle Editing Post
        if ($request->isMethod('POST') && $request->request->has('post_id')) {
            $post = $postRepository->find($request->request->get('post_id'));
            if ($post) {
                $post->setContenu($request->request->get('content'));
                $em->flush();
                $this->addFlash('success', 'Post updated successfully');
                return $this->redirectToRoute('app_home_page');
            }
        }

        // 4. Handle Editing Comment
        if ($request->isMethod('POST') && $request->request->has('comment_id')) {
            $comment = $em->getRepository(Commentaire::class)->find($request->request->get('comment_id'));
            if ($comment) {
                $comment->setContenu($request->request->get('comment_content'));
                $em->flush();
                $this->addFlash('success', 'Commentaire modifié avec succès');
                return $this->redirectToRoute('app_home_page');
            }
        }

        // 5. Handle Editing Reply
        if ($request->isMethod('POST') && $request->request->has('reply_id')) {
            $reply = $em->getRepository(SousCommentaire::class)->find($request->request->get('reply_id'));
            if ($reply) {
                $reply->setContenu($request->request->get('reply_content'));
                $em->flush();
                $this->addFlash('success', 'Reply updated successfully');
                return $this->redirectToRoute('app_home_page');
            }
        }
        $atms = $atmRepository->findAll();
        // 6. Get Posts with Comments
        $posts = $postRepository->findAllWithComments();

        return $this->render('home_page/home.html.twig', [
            'posts' => $posts,
            'postForm' => $postForm->createView(),
            'commentForms' => array_map(fn($form) => $form->createView(), $commentForms),
            'edit_post_id' => $request->query->get('edit'),
            'edit_comment_id' => $request->query->get('edit_comment'),
            'edit_reply_id' => $request->query->get('edit_reply'),
            'atms' => $atms,
        ]);
    }


   
}
