<?php
namespace App\ValueObjects;

final class PokemonType
{
    private string $name;

    public function __construct(string $name)
    {
        $this->name = strtolower(trim($name));
    }

    public function name(): string
    {
        return $this->name;
    }

    public function displayName(): string
    {
        return ucfirst($this->name);
    }

    public static function fromApiArray(array $typeData): self
    {
        $name = $typeData['type']['name'] ?? '';
        return new self($name);
    }
}
