<?php

namespace App\Controller;

use App\Entity\Comment;
use App\Entity\Upload;
use App\Form\CommentType;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class CommentController extends AbstractController
{
    /**
     * @Route("/comment/{id}/delete", name="comment_delete", requirements={"id": "\d+"})
     */
    public function deleteComment(Comment $comment, EntityManagerInterface $entityManager, Upload $upload): Response
    {
        $author = $comment->getUser();
        if ($author !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }

        $entityManager->remove($comment);
        $entityManager->flush();

        return $this->redirectToRoute('picture_details', ['id' => $upload->getId()]);
    }

    /**
     * @Route("/comment/{id}/edit", name="comment_edit", requirements={"id": "\d+"})
     */
    public function editComment(Comment $comment, EntityManagerInterface $entityManager, Upload $upload, Request $request): Response
    {
        $author = $comment->getUser();
        if ($author !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }

        $upload = $comment->getUpload();

        $form = $this->createForm(CommentType::class, $comment);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid())
        {
            $entityManager = $this->getDoctrine()->getManager();

            $comment->setUpdatedAt(new DateTime());
            $entityManager->flush();

            return $this->redirectToRoute('picture_details', ['id' => $upload->getId()]);
        }

        return $this->render('comment/edit.html.twig', [
            'form' => $form->createView(),
            'comment' => $comment,
        ]);
    }
}
