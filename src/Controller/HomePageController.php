<?php

namespace App\Controller;

use App\Repository\PostRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Entity\Commentaire;
use App\Entity\SousCommentaire;

class HomePageController extends AbstractController
{
    #[Route('/home', name: 'app_home_page', methods: ['GET', 'POST'])]
    public function index(
        Request $request, 
        PostRepository $postRepository,
        EntityManagerInterface $em
    ): Response {
        // Handle post editing
        if ($request->isMethod('POST') && $request->request->has('post_id')) {
            $post = $postRepository->find($request->request->get('post_id'));
            if ($post) {
                $post->setContenu($request->request->get('content'));
                $em->flush();
                $this->addFlash('success', 'Post updated successfully');
                return $this->redirectToRoute('app_home_page');
            }
        }

        // Handle comment editing
        if ($request->isMethod('POST') && $request->request->has('comment_id')) {
            $comment = $em->getRepository(Commentaire::class)->find($request->request->get('comment_id'));
            if ($comment) {
                $comment->setContenu($request->request->get('comment_content'));
                $em->flush();
                $this->addFlash('success', 'Commentaire modifié avec succès');
                return $this->redirectToRoute('app_home_page');
            }
        }

        // Handle reply editing
        if ($request->isMethod('POST') && $request->request->has('reply_id')) {
            $reply = $em->getRepository(SousCommentaire::class)->find($request->request->get('reply_id'));
            if ($reply) {
                $reply->setContenu($request->request->get('reply_content'));
                $em->flush();
                $this->addFlash('success', 'Reply updated successfully');
                return $this->redirectToRoute('app_home_page');
            }
        }

        // Optimized post retrieval with comments
        $posts = $postRepository->findAllWithComments();
        
        return $this->render('home_page/home.html.twig', [
            'posts' => $posts,
            'edit_post_id' => $request->query->get('edit'),
            'edit_comment_id' => $request->query->get('edit_comment'),
            'edit_reply_id' => $request->query->get('edit_reply')
        ]);
    }
}