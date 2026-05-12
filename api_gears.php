<?php

session_start();

require 'config.php';

/*
|--------------------------------------------------------------------------
| INTERFACE (DIP)
|--------------------------------------------------------------------------
*/

interface GearRepositoryInterface
{
    public function create($userId, $name, $type, $isGoreTex, $notes);
}

interface UserRepositoryInterface
{
    public function findById($id);
}


class GearRepository implements GearRepositoryInterface
{
    private $pdo;

    public function __construct($pdo)
    {
        $this->pdo = $pdo;
    }

    public function create($userId, $name, $type, $isGoreTex, $notes)
    {
        $stmt = $this->pdo->prepare("
            INSERT INTO gears 
            (user_id, name, type, is_gore_tex, notes) 
            VALUES (?, ?, ?, ?, ?)
        ");

        return $stmt->execute([
            $userId,
            $name,
            $type,
            $isGoreTex,
            $notes
        ]);
    }
}

class UserRepository implements UserRepositoryInterface
{
    private $pdo;

    public function __construct($pdo)
    {
        $this->pdo = $pdo;
    }

    public function findById($id)
    {
        $stmt = $this->pdo->prepare("
            SELECT id 
            FROM users 
            WHERE id = ?
        ");

        $stmt->execute([$id]);

        return $stmt->fetch();
    }
}

/*
|--------------------------------------------------------------------------
| SERVICE (SRP)
|--------------------------------------------------------------------------
*/

class AuthService
{
    private $userRepository;

    public function __construct(UserRepositoryInterface $userRepository)
    {
        $this->userRepository = $userRepository;
    }

    public function checkLogin()
    {
        if (!isset($_SESSION['user_id'])) {
            header("Location: index.php");
            exit;
        }

        $user = $this->userRepository->findById($_SESSION['user_id']);

        if (!$user) {
            session_destroy();

            header("Location: login.php?error=session_invalid");
            exit;
        }

        return $_SESSION['user_id'];
    }
}

class GearService
{
    private $gearRepository;

    public function __construct(GearRepositoryInterface $gearRepository)
    {
        $this->gearRepository = $gearRepository;
    }

    public function createGear($data)
    {
        return $this->gearRepository->create(
            $data['user_id'],
            $data['name'],
            $data['type'],
            $data['is_gore_tex'],
            $data['notes']
        );
    }
}


class GearController
{
    private $authService;
    private $gearService;

    public function __construct(
        AuthService $authService,
        GearService $gearService
    ) {
        $this->authService = $authService;
        $this->gearService = $gearService;
    }

    public function store()
    {
        $userId = $this->authService->checkLogin();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {

            $data = [
                'user_id' => $userId,
                'name' => htmlspecialchars($_POST['name']),
                'type' => htmlspecialchars($_POST['type']),
                'is_gore_tex' => isset($_POST['is_gore_tex']) ? 1 : 0,
                'notes' => htmlspecialchars($_POST['notes'])
            ];

            $this->gearService->createGear($data);

            header("Location: dashboard_customer.php");
            exit;
        }
    }
}


$userRepository = new UserRepository($pdo);
$gearRepository = new GearRepository($pdo);

$authService = new AuthService($userRepository);
$gearService = new GearService($gearRepository);

$controller = new GearController(
    $authService,
    $gearService
);

$controller->store();
?>
