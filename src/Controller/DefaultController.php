<?php

namespace App\Controller;

use App\DTO\RegisterUserRequest;
use App\Service\UserService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class DefaultController extends AbstractController
{
    private RouterInterface $router;
    private ValidatorInterface $validator;
    private SerializerInterface $serializer;
    private UserService $userService;

    public function __construct(RouterInterface $router, ValidatorInterface $validator, SerializerInterface $serializer, UserService $userService)
    {
        $this->router = $router;
        $this->validator = $validator;
        $this->serializer = $serializer;
        $this->userService = $userService;
    }

    public function index()
    {
        return $this->render('default/index.html.twig');
    }

    public function ping() {
        return $this->json(['ping' => 'pong']);
    }

    public function register(Request $request) {
        $req = $this->serializer->deserialize($request->getContent(), RegisterUserRequest::class, 'json');
        $errors = $this->validator->validate($req);
        if (count($errors) > 0) {
            throw new BadRequestException((string) $errors);
        }

        $this->userService->registerUser($req);

        $targetUrl = $this->router->generate('home', ['reactRouting' => 'account']);
        return $this->json(['url' => $targetUrl]);
    }

    public function getAllPlans()
    {
        $plans = $this->userService->getAllPlans();
        return $this->json($plans);
    }

    public function getAllGoals()
    {
        $goals = $this->userService->getAllGoals();
        return $this->json($goals);
    }

    public function getGoalById(string $goalId)
    {
        $goal = $this->userService->getGoalById($goalId);
        return $this->json($goal);
    }

    public function getUserHandler(): Response {
        return $this->json($this->getUser());
    }
}
