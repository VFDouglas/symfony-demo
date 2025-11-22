<?php

namespace App\Controller;

use Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route(path: '/api/v1/jwt/{token}', name: 'api_jwt_check', requirements: ['token' => '.+'], methods: ['GET'])]
final class JWTController extends AbstractController
{
    public function __construct(
        #[Autowire(env: 'JWT_SECRET')] private readonly string $jwtSecret
    ) {
    }

    public function __invoke(Request $request, string $token): JsonResponse
    {
        if (!$token) {
            return new JsonResponse(['error' => 'Token missing'], 400);
        }

        try {
            $payload = $this->verifyJwt($token, $this->jwtSecret);

            return new JsonResponse([
                'valid'   => true,
                'payload' => $payload
            ]);
        } catch (Exception $e) {
            return new JsonResponse([
                'valid' => false,
                'error' => $e->getMessage()
            ], 401);
        }
    }

    private function base64UrlDecode(string $data): string
    {
        $remainder = strlen($data) % 4;
        if ($remainder) {
            $data .= str_repeat('=', 4 - $remainder);
        }

        return base64_decode(strtr($data, '-_', '+/'));
    }

    /**
     * @throws Exception
     */
    private function verifyJwt(string $jwt, string $secret): array
    {
        $parts = explode('.', $jwt);
        if (count($parts) !== 3) {
            throw new Exception('Invalid token format');
        }

        [$headerB64, $payloadB64, $signatureB64] = $parts;

        $header    = json_decode($this->base64UrlDecode($headerB64), true);
        $payload   = json_decode($this->base64UrlDecode($payloadB64), true);
        $signature = $this->base64UrlDecode($signatureB64);

        if ($header['alg'] !== 'HS256') {
            throw new Exception('Unsupported algorithm');
        }

        $expectedSignature = hash_hmac('sha256', $headerB64 . '.' . $payloadB64, $secret, true);

        if (!hash_equals($expectedSignature, $signature)) {
            throw new Exception('Invalid signature');
        }

        if (isset($payload['exp']) && time() >= $payload['exp']) {
            throw new Exception('Token expired');
        }

        return $payload;
    }
}
