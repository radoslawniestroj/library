<?php

namespace App\Repository;

use App\Config\BookStatus;
use App\Entity\Book;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Book>
 */
class BookRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Book::class);
    }

    /**
     * @return Book[]
     */
    public function findByFilters(string $query, ?string $title, ?string $author): array
    {
        $filters = [];
        if (str_contains($query, '?')) {
            $queryPart = explode('?', $query)[1];
            $pairs = explode('&', $queryPart);

            foreach ($pairs as $pair) {
                [$key, $value] = explode('=', $pair) + [null, null];
                if ($key && $value) {
                    $filters[$key] = urldecode($value);
                }
            }
        }

        $qb = $this->createQueryBuilder('b');

        foreach ($filters as $field => $value) {
            $qb->andWhere("b.$field LIKE :$field")
                ->setParameter($field, "%$value%");
        }

        return $qb->getQuery()->getResult();
    }
}
