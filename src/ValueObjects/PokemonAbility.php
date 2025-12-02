<?php
namespace App\ValueObjects;

final class PokemonAbility
{
    private string $name;
    private bool $isHidden;

    public function __construct(string $name, bool $isHidden = false)
    {
        $this->name = trim($name);
        $this->isHidden = (bool)$isHidden;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function isHidden(): bool
    {
        return $this->isHidden;
    }

    public static function fromApiArray(array $abilityData): self
    {
        $name = $abilityData['ability']['name'] ?? '';
        $hidden = $abilityData['is_hidden'] ?? false;
        return new self($name, (bool)$hidden);
    }
}
