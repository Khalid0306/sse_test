<?php

namespace App\Service;

use Lcobucci\JWT\Configuration;
use Lcobucci\JWT\Signer\Hmac\Sha256;
use Lcobucci\JWT\Signer\Key\InMemory;

class MercureJwtProvider
{
    private string $mercureSecret;
    private string $mercurePublicUrl;

    public function __construct(string $mercureSecret, string $mercurePublicUrl)
    {
        $this->mercureSecret = $mercureSecret;
        $this->mercurePublicUrl = $mercurePublicUrl;
    }

    /**
     * Génère un token JWT pour l'abonnement aux topics Mercure
     *
     * @param array $topics Liste des topics auxquels s'abonner
     * @param string|null $contractId ID du contrat
     * @param string|null $ownerApplication Application propriétaire
     * @return string Le token JWT encodé
     */
    public function generateSubscribeToken(
        array $topics = [],
        ?string $contractId = null,
        ?string $ownerApplication = null
    ): string {
        $configuration = Configuration::forSymmetricSigner(
            new Sha256(),
            InMemory::plainText($this->mercureSecret)
        );

        $builder = $configuration->builder();

        // Définir les topics d'abonnement
        $subscribeClaim = $topics;

        // Si pas de topics spécifiques, permettre l'abonnement basé sur contractId et ownerApplication
        if (empty($subscribeClaim)) {
            if ($contractId && $ownerApplication) {
                // Topic spécifique pour un contrat
                $subscribeClaim = ["realtime-event/{$ownerApplication}/contract/{$contractId}"];
            } elseif ($ownerApplication) {
                // Tous les contrats d'une application
                $subscribeClaim = ["realtime-event/{$ownerApplication}/contract/*"];
            } else {
                // Par défaut
                $subscribeClaim = ['*'];
            }
        }

        // Construire le token
        $now = new \DateTimeImmutable();
        $token = $builder
            ->issuedBy($this->mercurePublicUrl)
            ->issuedAt($now)
            ->expiresAt($now->modify('+1 hour')) // Token valide 1 heure
            ->withClaim('mercure', [
                'subscribe' => $subscribeClaim,
                'payload' => [] // Pas de payload custom pour la lecture
            ])
            ->getToken($configuration->signer(), $configuration->signingKey());

        return $token->toString();
    }

    /**
     * Génère un token JWT pour publier sur les topics Mercure (backend uniquement)
     *
     * @param array $topics Liste des topics sur lesquels publier
     * @return string Le token JWT encodé
     */
    public function generatePublishToken(array $topics = ['*']): string
    {
        $configuration = Configuration::forSymmetricSigner(
            new Sha256(),
            InMemory::plainText($this->mercureSecret)
        );

        $builder = $configuration->builder();
        $now = new \DateTimeImmutable();

        $token = $builder
            ->issuedBy($this->mercurePublicUrl)
            ->issuedAt($now)
            ->expiresAt($now->modify('+1 hour'))
            ->withClaim('mercure', [
                'publish' => $topics,
                'subscribe' => [],
                'payload' => []
            ])
            ->getToken($configuration->signer(), $configuration->signingKey());

        return $token->toString();
    }

    /**
     * Retourne l'URL du hub Mercure pour le front-end
     */
    public function getMercurePublicUrl(): string
    {
        return $this->mercurePublicUrl;
    }
}