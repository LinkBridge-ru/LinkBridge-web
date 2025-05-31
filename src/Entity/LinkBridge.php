<?php

namespace App\Entity;

use App\Repository\LinkBridgeRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: LinkBridgeRepository::class)]
#[ORM\Table(name: "linkbridge")]
class LinkBridge
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255, unique: true)]
    private ?string $session_id = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $registeredAt = null;

	#[ORM\Column(unique: true)]
    private string|int|null $pin = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $url = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSessionId(): ?string
    {
        return $this->session_id;
    }

    public function setSessionId(string $session_id): static
    {
        $this->session_id = $session_id;

        return $this;
    }

    public function getRegisteredAt(): ?\DateTimeImmutable
    {
        return $this->registeredAt;
    }

    public function setRegisteredAt(\DateTimeImmutable $registeredAt): static
    {
        $this->registeredAt = $registeredAt;

        return $this;
    }

    public function getPin(): string|null
    {
        return $this->pin;
    }

    public function setPin(string|int $pin): static
    {
        $this->pin = $pin;

        return $this;
    }

    public function getUrl(): ?string
    {
        return $this->url;
    }

    public function setUrl(?string $url): static
    {
        $this->url = $url;

        return $this;
    }
}
