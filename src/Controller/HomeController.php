<?php

namespace App\Controller;

use App\Entity\Conversation;
use App\Entity\Message;
use App\Entity\User;
use App\Form\MessageType;
use App\Repository\ConversationRepository;
use App\Repository\MessageRepository;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class HomeController extends AbstractController
{
    #[Route('/', name: 'app_home')]
    public function index(UserRepository $userRepository): Response
    {
        return $this->render('home/index.html.twig', [
            'users' => $userRepository->findAll(),
            'conversations' => $this->getUser()->getConversations()
        ]);
    }

    #[Route('/send-message-to/{id}', name: 'app_create_conversation')]
    public function newConversation(User $recipient, Request $request, ConversationRepository $conversationRepository, MessageRepository $messageRepository): Response
    {
        $conversationsCurrentUser = $this->getUser()->getConversations();

        foreach ($conversationsCurrentUser as $conversation) {
            if ($conversation->getUsers()->contains($recipient)) {
                return $this->redirectToRoute('app_conversation', [
                    'id' => $conversation->getId(),
                ]);
            }
        }

        $conversation = new Conversation();

        $conversation->addUser($recipient);
        $conversation->addUser($this->getUser());

        $message = new Message();

        $form = $this->createForm(MessageType::class, $message); 
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $conversationRepository->add($conversation, true);

            $message->setAuthor($this->getUser());
            $message->setConversation($conversation);

            $messageRepository->add($message, true);

            $conversation->setLastMessage($message);
            $conversationRepository->add($conversation, true);

            return $this->redirectToRoute('app_conversation', [
                'id' => $conversation->getId(),
            ]);
        }

        return $this->render('home/new-conversation.html.twig', [
            'form' => $form->createView(),
            'recipient' => $recipient
        ]);
    }

    #[Route('/conversation/{id}', name: 'app_conversation')]
    public function conversation(Conversation $conversation, Request $request, MessageRepository $messageRepository, ConversationRepository $conversationRepository): Response
    {
        $conversationsCurrentUser = $this->getUser()->getConversations();
        if (!$conversationsCurrentUser->contains($conversation)) {
            return $this->redirectToRoute('app_home');
        }

        $message = new Message();

        $form = $this->createForm(MessageType::class, $message); 
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $message->setAuthor($this->getUser());
            $message->setConversation($conversation);
            $messageRepository->add($message, true);

            $conversation->setLastMessage($message);
            $conversationRepository->add($conversation, true);

            return $this->redirectToRoute('app_conversation', [
                'id' => $conversation->getId(),
            ]);
        }
        return $this->render('home/conversation.html.twig', [
            'messages' => $conversation->getMessages(),
            'form' => $form->createView()
        ]);
    }
}
