<?php

namespace App\Controller;

use App\Entity\Book;
use App\Repository\BookRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api', name: 'api_')]
final class BooksController extends AbstractController
{
    public function __construct(
        private readonly BookRepository $bookRepository,
        private readonly SerializerInterface $serializer,
        private readonly ValidatorInterface $validator,
        private readonly EntityManagerInterface $entityManager
    ) {
    }

    #[Route('/books', name: 'book_list', methods: ['GET'])]
    public function index(Request $request): JsonResponse
    {
        $query = $request->getRequestUri();

        $title = $request->query->get('title');
        $author = $request->query->get('author');

        $books = $this->bookRepository->findByFilters($query, $title, $author);

        $json = $this->serializer->serialize($books, 'json', [
            'groups' => ['book:read']
        ]);

        return new JsonResponse($json, Response::HTTP_OK, [], true);
    }

    #[Route('/books/{id}', name: 'book_detail', methods: ['GET'])]
    public function getBook(int $id): JsonResponse
    {
        $book = $this->bookRepository->find($id);

        if (!$book) {
            return $this->json(['error' => 'Book not found'], Response::HTTP_NOT_FOUND);
        }

        return $this->json($book, Response::HTTP_OK, [], ['groups' => ['book:read']]);
    }

    #[Route('/books', name: 'book_create', methods: ['POST'])]
    #[IsGranted('ROLE_LIBRARIAN')]
    public function createBook(Request $request): JsonResponse
    {
        $book = $this->serializer->deserialize($request->getContent(), Book::class, 'json', [
            AbstractNormalizer::GROUPS => ['book:write']
        ]);

        $errors = $this->validator->validate($book);
        if (count($errors) > 0) {
            return $this->json($errors, Response::HTTP_BAD_REQUEST);
        }

        $this->entityManager->persist($book);
        $this->entityManager->flush();

        return $this->json($book, Response::HTTP_CREATED, [], ['groups' => ['book:read']]);
    }
}
