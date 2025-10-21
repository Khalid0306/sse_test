<?php

namespace App\Controller;

use App\Service\MercureJwtProvider;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/mercure')]
class MercureTokenController extends AbstractController
{
    #[Route('/token', name: 'mercure_token', methods: ['POST'])]
    public function getToken(Request $request, MercureJwtProvider $jwtProvider): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!is_array($data)) {
            return $this->json(['error' => 'Invalid JSON payload'], 400);
        }

        $topics = $data['topics'] ?? null;

        if (empty($topics) || !is_array($topics)) {
            return $this->json(['error' => 'Missing or invalid "topics" field'], 400);
        }

        // Génération du token d'abonnement
        $token = $jwtProvider->generateSubscribeToken($topics);

        return $this->json([
            'token' => $token,
            'mercureUrl' => $jwtProvider->getMercurePublicUrl(),
            'topics' => $topics,
        ]);
    }
}