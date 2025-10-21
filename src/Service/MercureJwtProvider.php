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
     * @return string Le token JWT encodé
     */
    public function generateSubscribeToken(array $topics): string
    {
        if (empty($topics)) {
            throw new \InvalidArgumentException('The topic list cannot be empty.');
        }

        $configuration = Configuration::forSymmetricSigner(
            new Sha256(),
            InMemory::plainText($this->mercureSecret)
        );

        $builder = $configuration->builder();
        $now = new \DateTimeImmutable();

        $token = $builder
            ->issuedBy($this->mercurePublicUrl)
            ->issuedAt($now)
            ->expiresAt($now->modify('+2 hour'))
            ->withClaim('mercure', [
                'subscribe' => $topics,
            ])
            ->getToken($configuration->signer(), $configuration->signingKey());

        return $token->toString();
    }


    /**
     * Génère un token JWT pour publier sur les topics Mercure (côté serveur uniquement)
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
            ->expiresAt($now->modify('+2 hour'))
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