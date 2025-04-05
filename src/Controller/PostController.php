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

   // src/Controller/PostController.php
   #[Route('/new', name: 'post_new', methods: ['POST'])]
   public function newPost(
       Request $request,
       EntityManagerInterface $em,
       UtilisateurRepository $utilisateurRepo
   ): Response {
       // Récupère le contenu du formulaire
       $content = $request->request->get('content');
       
       if (empty($content)) {
           return new Response("Le contenu ne peut pas être vide", 400);
       }

       // Crée le nouveau post
       $post = new Post();
       $post->setContenu($content);
       $post->setDateCreation(new \DateTime());
       
       // Utilisateur statique ID=1
       $utilisateur = $utilisateurRepo->find(1);
       if (!$utilisateur) {
           throw $this->createNotFoundException('Utilisateur avec ID 1 non trouvé');
       }
       $post->setUtilisateur($utilisateur);
       
       $em->persist($post);
       $em->flush();
       
       return $this->redirectToRoute('app_home_page');
   }



   #[Route('/post/{id}/edit', name: 'post_edit', methods: ['POST'])]
   public function editPost(Request $request, Post $post, EntityManagerInterface $em): Response
   {
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
    $em->remove($post);
    $em->flush();

    $this->addFlash('success', 'Post supprimé avec succès');
    return $this->redirectToRoute('app_home_page');
}
#ajouter comment

#[Route('/post/{id}/add-comment', name: 'post_add_comment', methods: ['POST'])]
public function addComment(
    Post $post,
    Request $request,
    EntityManagerInterface $em,
    UtilisateurRepository $userRepo
): Response {
    $content = $request->request->get('comment_content');
    
    if (!empty($content)) {
        $comment = new Commentaire();
        $comment->setContenu($content);
        $comment->setPost($post);
        $comment->setUtilisateur($userRepo->find(3)); // Utilisateur statique (à remplacer par $this->getUser() plus tard)
        $comment->setDateCreation(new \DateTime());
        
        $em->persist($comment);
        $em->flush();
        
        $this->addFlash('success', 'Commentaire ajouté !');
    }
    
    return $this->redirectToRoute('app_home_page');
}
    


#[Route('/comment/{id}/delete', name: 'comment_delete', methods: ['GET', 'POST'])]
public function deleteComment(Commentaire $comment, EntityManagerInterface $em): Response
{
    $em->remove($comment);
    $em->flush();
    $this->addFlash('success', 'Commentaire supprimé avec succès');
    return $this->redirectToRoute('app_home_page');
}


#sous_commenatire 

#[Route('/comment/{id}/add-reply', name: 'add_reply', methods: ['POST'])]
public function addReply(
    Request $request,
    EntityManagerInterface $em,
    UtilisateurRepository $userRepo,
    int $id  // Change parameter to ID
): Response {
    // Manually fetch the comment
    $comment = $em->getRepository(Commentaire::class)->find($id);
    
    if (!$comment) {
        throw $this->createNotFoundException('Comment not found');
    }

    $content = $request->request->get('reply_content');
    
    if (!empty($content)) {
        $reply = new SousCommentaire();
        $reply->setContenu($content);
        $reply->setCommentaire($comment);
        $reply->setUtilisateur($userRepo->find(1)); // Replace with current user
        $reply->setDateCreation(new \DateTime());
        
        $em->persist($reply);
        $em->flush();
        
        $this->addFlash('success', 'Reply added successfully');
    }
    
    return $this->redirectToRoute('app_home_page');
}

#[Route('/reply/{id}/delete', name: 'delete_reply',methods: ['GET', 'POST'])]
public function deleteReply(
    SousCommentaire $reply,
    EntityManagerInterface $em
): Response {
    $em->remove($reply);
    $em->flush();
    
    $this->addFlash('success', 'Reply deleted successfully');
    return $this->redirectToRoute('app_home_page');
}

}
