<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\LoanRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api', name: 'api_')]
final class UsersController extends AbstractController
{
    public function __construct(
        private readonly LoanRepository $loanRepository
    ) {
    }

    #[Route('/users/{id}/loans', name: 'user_loans', methods: ['GET'])]
    public function getUserLoans(int $id): JsonResponse
    {
        /** @var User $currentUser */
        $currentUser = $this->getUser();

        if ($currentUser->getId() !== $id && !$this->isGranted('ROLE_LIBRARIAN')) {
            return $this->json(['error' => 'Access denied'], Response::HTTP_FORBIDDEN);
        }

        $loans = $this->loanRepository->findBy(['user' => $id]);

        return $this->json($loans, Response::HTTP_OK, [], ['groups' => ['loan:read']]);
    }
}
