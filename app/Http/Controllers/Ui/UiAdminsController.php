<?php

declare(strict_types=1);

namespace App\Http\Controllers\Ui;

use App\Domain\Admin\Reader\AdminProfileReaderInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use RuntimeException;
use Slim\Views\Twig;

readonly class UiAdminsController
{
    public function __construct(
        private Twig $view,
        private AdminProfileReaderInterface $profileReader
    ) {
    }

    public function index(Request $request, Response $response): Response
    {
        return $this->view->render($response, 'pages/admins.twig');
    }

    /**
     * @param array<string, string> $args
     */
    public function profile(Request $request, Response $response, array $args): Response
    {
        if (!isset($args['id']) || !ctype_digit((string)$args['id'])) {
            throw new RuntimeException('Invalid admin id');
        }

        $adminId = (int) $args['id'];

        $profile = $this->profileReader->getProfile($adminId);

        return $this->view->render(
            $response,
            'pages/admins_profile.twig',
            $profile
        );
    }
}
