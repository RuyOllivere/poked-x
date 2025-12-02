<?php
namespace App\ValueObjects;

final class PokemonStats
{
    private int $heightDecimeters;
    private int $weightHectograms;

    public function __construct(int $heightDecimeters, int $weightHectograms)
    {
        $this->heightDecimeters = $heightDecimeters;
        $this->weightHectograms = $weightHectograms;
    }

    public function heightMeters(): float
    {
        return $this->heightDecimeters / 10.0;
    }

    public function heightCentimeters(): int
    {
        return (int)round($this->heightDecimeters * 10);
    }

    public function weightKilograms(): float
    {
        return $this->weightHectograms / 10.0;
    }

    public static function fromApi(array $data): self
    {
        $h = isset($data['height']) ? (int)$data['height'] : 0;
        $w = isset($data['weight']) ? (int)$data['weight'] : 0;
        return new self($h, $w);
    }
}
