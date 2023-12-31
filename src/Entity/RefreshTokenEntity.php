<?php

declare(strict_types = 1);

namespace Zemkog\OAuth2ServerBundle\Entity;

use Zemkog\OAuth2ServerBundle\Repository\RefreshTokenRepository;
use DateTimeImmutable;
use Doctrine\DBAL\Types\Types;
use League\OAuth2\Server\Entities\AccessTokenEntityInterface;
use League\OAuth2\Server\Entities\RefreshTokenEntityInterface;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: RefreshTokenRepository::class)]
#[ORM\HasLifecycleCallbacks]
#[ORM\Table(name: 'refresh_token')]
class RefreshTokenEntity implements RefreshTokenEntityInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private int $id;

    #[ORM\ManyToOne(targetEntity: AccessTokenEntity::class, inversedBy: 'refreshTokens')]
    #[ORM\JoinColumn(name: 'access_token_id', referencedColumnName: 'id', nullable: false)]
    private AccessTokenEntity $accessToken;

    #[ORM\Column(type: Types::TEXT)]
    private string $token;

    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $is_revoke;

    #[ORM\Column(type: Types::DATETIMETZ_IMMUTABLE)]
    private DateTimeImmutable $expiry_at;

    #[ORM\Column(type: Types::DATETIMETZ_IMMUTABLE)]
    private DateTimeImmutable $created_at;

    #[ORM\Column(type: Types::DATETIMETZ_IMMUTABLE)]
    private DateTimeImmutable $updated_at;

    public function getIdentifier(): string
    {
        return $this->token;
    }

    public function setIdentifier($identifier): void
    {
        $this->token = $identifier;
    }

    public function getExpiryDateTime(): DateTimeImmutable
    {
        return $this->expiry_at;
    }

    public function setExpiryDateTime(DateTimeImmutable $dateTime): void
    {
        $this->expiry_at = $dateTime;
    }

    public function setAccessToken(AccessTokenEntityInterface|AccessTokenEntity $accessToken)
    {
        $this->accessToken = $accessToken;
    }

    public function getAccessToken(): AccessTokenEntity
    {
        return $this->accessToken;
    }

    /** @noinspection PhpUnused */
    #[ORM\PrePersist]
    public function setCreatedAt(): void
    {
        $this->created_at = new DateTimeImmutable();
    }

    /** @noinspection PhpUnused */
    #[ORM\PreUpdate]
    #[ORM\PrePersist]
    public function setUpdatedAt(): void
    {
        $this->updated_at = new DateTimeImmutable();
    }

    /**
     * @param bool $isRevoke
     */
    public function setIsRevoke(bool $isRevoke): void
    {
        $this->is_revoke = $isRevoke;
    }

    /**
     * @return bool
     */
    public function isRevoked(): bool
    {
        return $this->is_revoke;
    }
}
