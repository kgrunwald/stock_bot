<?php

namespace App\Service;

use App\DTO\RegisterUserRequest;
use App\Entity\Goal;
use App\Entity\User;
use App\Repository\DbContext;
use App\Repository\SecretRepository;
use App\Security\SecurityService;
use GuzzleHttp\Promise\Utils;

class UserService
{
    private SecurityService $securityService;
    private DbContext $dbContext;
    private SecretRepository $secretRepo;
    private ?User $user;

    public function __construct(SecurityService $security, DbContext $dbContext, SecretRepository $secretRepo)
    {
        $this->securityService = $security;
        $this->dbContext = $dbContext;
        $this->secretRepo = $secretRepo;
        $this->user = $this->securityService->getUser();
    }

    public function registerUser(RegisterUserRequest $request)
    {
        $this->user->setEmail($request->email);
        $this->user->setName($request->name);
        $this->dbContext->users->add($this->user);
        
        Utils::all([
            $this->dbContext->commitAsync(), 
            $this->secretRepo->addToken($this->user, $request->token)
        ])->wait();
    }

    public function getAllPlans(): array
    {
        return $this->dbContext->plans->getAll();
    }

    public function getAllGoals(): array
    {
        return $this->dbContext->goals->getUserGoals($this->user);
    }

    public function getGoalById(string $goalId): ?Goal
    {
        return $this->dbContext->goals->getUserGoalById($this->user, $goalId);
    }
}