<?php

namespace App\Tests\Controller;

use App\Controller\UsersController;
use App\Entity\User;
use App\Repository\LoanRepository;
use App\Tests\WebTestCase;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\User\UserInterface;

class UsersControllerTest extends WebTestCase
{
    public function testAccessDeniedIfUserIdDoesNotMatchAndNoLibrarianRole()
    {
        $mockUser = $this->createMock(User::class);
        $mockUser->method('getId')->willReturn(1);

        $loanRepo = $this->createMock(LoanRepository::class);

        $security = $this->createMock(Security::class);
        $security->method('getUser')->willReturn($mockUser);
        $security->method('isGranted')->with('ROLE_LIBRARIAN')->willReturn(false);

        $controller = new UsersController($loanRepo, $security);

        $response = $controller->getUserLoans(2);

        $this->assertEquals(Response::HTTP_FORBIDDEN, $response->getStatusCode());
        $this->assertStringContainsString('Access denied', $response->getContent());
    }

    public function testReturnLoansIfUserIsOwner()
    {
        $loanRepo = $this->createMock(LoanRepository::class);
        $loanRepo->expects($this->once())
            ->method('findBy')
            ->with(['user' => 1])
            ->willReturn(['loan1', 'loan2']);

        $user = $this->createMock(User::class);

        $controller = new class($loanRepo, $user) extends UsersController {
            public function __construct($loanRepo, private $user) {
                parent::__construct($loanRepo);
            }

            protected function getUser(): ?UserInterface
            {
                $user = $this->user;
                $user->method('getId')->willReturn(1);
                return $user;
            }

            protected function isGranted(mixed $attribute, $subject = null): bool
            {
                return false;
            }
        };

        $response = $controller->getUserLoans(1);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $this->assertJsonStringEqualsJsonString(
            json_encode(['loan1', 'loan2']),
            $response->getContent()
        );
    }

    public function testReturnLoansIfUserIsLibrarian()
    {
        $loanRepo = $this->createMock(LoanRepository::class);
        $loanRepo->expects($this->once())
            ->method('findBy')
            ->with(['user' => 5])
            ->willReturn(['loanA']);

        $user = $this->createMock(User::class);

        $controller = new class($loanRepo, $user) extends UsersController {
            public function __construct($loanRepo, private $user) {
                parent::__construct($loanRepo);
            }

            protected function getUser(): ?UserInterface
            {
                $user = $this->user;
                $user->method('getId')->willReturn(1);
                return $user;
            }

            protected function isGranted(mixed $attribute, $subject = null): bool
            {
                return $attribute === 'ROLE_LIBRARIAN';
            }
        };

        $response = $controller->getUserLoans(5);

        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $this->assertJsonStringEqualsJsonString(
            json_encode(['loanA']),
            $response->getContent()
        );
    }
}
