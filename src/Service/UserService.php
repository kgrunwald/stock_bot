<?php

namespace App\Service;

use App\DTO\RegisterUserRequest;
use App\Repository\DbContext;
use App\Repository\SecretRepository;
use App\Security\SecurityService;
use GuzzleHttp\Promise\Utils;

class UserService
{
    private SecurityService $securityService;
    private DbContext $dbContext;
    private SecretRepository $secretRepo;

    public function __construct(SecurityService $security, DbContext $dbContext, SecretRepository $secretRepo)
    {
        $this->securityService = $security;
        $this->dbContext = $dbContext;
        $this->secretRepo = $secretRepo;
    }

    public function registerUser(RegisterUserRequest $request)
    {
        $user = $this->securityService->getUser();
        $user->setEmail($request->email);
        $user->setName($request->name);
        $this->dbContext->users->add($user);
        
        Utils::all([
            $this->dbContext->commitAsync(), 
            $this->secretRepo->addToken($user, $request->token)
        ])->wait();
    }

    public function getAllPlans(): array
    {
        return $this->dbContext->plans->getAll();
    }

    public function getAllGoals(): array
    {
        $user = $this->securityService->getUser();
        return $this->dbContext->goals->getAllGoalsForUser($user);
    }
}