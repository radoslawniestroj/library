<?php

namespace App\Controller;

use App\Dto\LoanRequestDto;
use App\Repository\BookRepository;
use App\Repository\LoanRepository;
use App\Service\LoanService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api', name: 'api_')]
class LoansController extends AbstractController
{
    public function __construct(
        private readonly LoanRepository $loanRepository,
        private readonly BookRepository $bookRepository,
        private readonly LoanService $loanService,
        private readonly SerializerInterface $serializer,
        private readonly ValidatorInterface $validator
    ) {
    }

    #[Route('/loans', name: 'loan_create', methods: ['POST'])]
    public function createLoan(Request $request): JsonResponse
    {
        $dto = $this->serializer->deserialize($request->getContent(), LoanRequestDto::class, 'json');

        $errors = $this->validator->validate($dto);
        if (count($errors) > 0) {
            return $this->json($errors, Response::HTTP_BAD_REQUEST);
        }

        $user = $this->getUser();
        $book = $this->bookRepository->find($dto->bookId);

        if (!$book) {
            return $this->json(['error' => 'Book not found'], Response::HTTP_NOT_FOUND);
        }

        try {
            $loan = $this->loanService->borrowBook($user, $book);
        } catch (\RuntimeException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }

        return $this->json($loan, Response::HTTP_CREATED, [], ['groups' => ['loan:read']]);
    }

    #[Route('/loans/{id}/return', name: 'loan_return', methods: ['PUT'])]
    public function returnLoan(int $id): JsonResponse
    {
        $loan = $this->loanRepository->find($id);
        $user = $this->getUser();

        if (!$loan) {
            return $this->json(['error' => 'Loan not found'], Response::HTTP_NOT_FOUND);
        }

        if ($loan->getUser() !== $user && !$this->isGranted('ROLE_LIBRARIAN')) {
            return $this->json(['error' => 'Access denied'], Response::HTTP_FORBIDDEN);
        }

        try {
            $this->loanService->returnBook($loan);
        } catch (\RuntimeException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }

        return $this->json($loan, Response::HTTP_OK, [], ['groups' => ['loan:read']]);
    }
}
