<?php

namespace App\Controller;

use App\Entity\Post;
use App\Form\CommentType;
use App\Entity\SousCommentaire;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use App\Repository\UtilisateurRepository;
use App\Entity\Commentaire;  

#[Route('/post')]
class PostController extends AbstractController
{
    #[Route('/post/{id}/edit', name: 'post_edit', methods: ['POST'])]
    public function editPost(Request $request, Post $post, EntityManagerInterface $em): Response
    {
        // Check if current user is the owner of the post
        if ($post->getUtilisateur() !== $this->getUser()) {
            $this->addFlash('error', 'You can only edit your own posts');
            return $this->redirectToRoute('app_home_page');
        }

        $newContent = $request->request->get('content');
        
        if (empty($newContent)) {
            $this->addFlash('error', 'Le contenu ne peut pas être vide');
        } else {
            $post->setContenu($newContent);
            $em->flush();
            $this->addFlash('success', 'Post modifié avec succès');
        }
        
        return $this->redirectToRoute('app_home_page');
    }
    
    #[Route('/post/{id}/delete', name: 'post_delete', methods: ['GET'])]
    public function deletePost(Post $post, EntityManagerInterface $em): Response
    {
        // Check if current user is the owner of the post
        if ($post->getUtilisateur() !== $this->getUser()) {
            $this->addFlash('error', 'You can only delete your own posts');
            return $this->redirectToRoute('app_home_page');
        }

        $em->remove($post);
        $em->flush();

        $this->addFlash('success', 'Post supprimé avec succès');
        return $this->redirectToRoute('app_home_page');
    }

    #[Route('/comment/{id}/delete', name: 'comment_delete', methods: ['GET', 'POST'])]
    public function deleteComment(Commentaire $comment, EntityManagerInterface $em): Response
    {
        // Check if current user is the owner of the comment
        if ($comment->getUtilisateur() !== $this->getUser()) {
            $this->addFlash('error', 'You can only delete your own comments');
            return $this->redirectToRoute('app_home_page');
        }

        $em->remove($comment);
        $em->flush();
        $this->addFlash('success', 'Commentaire supprimé avec succès');
        return $this->redirectToRoute('app_home_page');
    }

    #[Route('/comment/{id}/add-reply', name: 'add_reply', methods: ['POST'])]
    public function addReply(
        Request $request,
        EntityManagerInterface $em,
        UtilisateurRepository $userRepo,
        int $id
    ): Response {
        $comment = $em->getRepository(Commentaire::class)->find($id);
        
        if (!$comment) {
            throw $this->createNotFoundException('Comment not found');
        }

        $content = $request->request->get('reply_content');
        
        if (!empty($content)) {
            $reply = new SousCommentaire();
            $reply->setContenu($content);
            $reply->setCommentaire($comment);
            $reply->setUtilisateur($this->getUser()); // Use current user
            $reply->setDateCreation(new \DateTime());
            
            $em->persist($reply);
            $em->flush();
            
            $this->addFlash('success', 'Reply added successfully');
        }
        
        return $this->redirectToRoute('app_home_page');
    }

    #[Route('/reply/{id}/delete', name: 'delete_reply', methods: ['GET', 'POST'])]
    public function deleteReply(SousCommentaire $reply, EntityManagerInterface $em): Response
    {
        // Check if current user is the owner of the reply
        if ($reply->getUtilisateur() !== $this->getUser()) {
            $this->addFlash('error', 'You can only delete your own replies');
            return $this->redirectToRoute('app_home_page');
        }

        $em->remove($reply);
        $em->flush();
        
        $this->addFlash('success', 'Reply deleted successfully');
        return $this->redirectToRoute('app_home_page');
    }
}