<?php
namespace App;

use App\ValueObjects\PokemonType;
use App\ValueObjects\PokemonAbility;
use App\ValueObjects\PokemonStats;

final class Pokemon
{
    private int $id;
    private string $name;
    /** @var PokemonType[] */
    private array $types;
    /** @var PokemonAbility[] */
    private array $abilities;
    private PokemonStats $stats;
    private ?string $sprite;
    private ?string $evolutionUrl;
    private ?string $locationAreasUrl;
        /** @var string[] */
        private array $locationAreas;
    private ?string $description;
    private bool $isLegendary;

    public function __construct(int $id, string $name, array $types, array $abilities, PokemonStats $stats, ?string $sprite = null, ?string $evolutionUrl = null, ?string $locationAreasUrl = null, ?string $description = null, bool $isLegendary = false, array $locationAreas = [])
    {
        $this->id = $id;
        $this->name = $name;
        $this->types = $types;
        $this->abilities = $abilities;
        $this->stats = $stats;
        $this->sprite = $sprite;
        $this->evolutionUrl = $evolutionUrl;
        $this->locationAreasUrl = $locationAreasUrl;
        $this->description = $description;
        $this->locationAreas = $locationAreas;
        $this->isLegendary = $isLegendary;
    }

    public function id(): int { return $this->id; }
    public function name(): string { return $this->name; }
    public function types(): array { return $this->types; }
    public function abilities(): array { return $this->abilities; }
    public function stats(): PokemonStats { return $this->stats; }
    public function sprite(): ?string { return $this->sprite; }
    public function evolutionUrl(): ?string { return $this->evolutionUrl; }
    public function locationAreasUrl(): ?string { return $this->locationAreasUrl; }
    public function isLegendary(): bool { return $this->isLegendary; }

    public function typeNames(): array
    {
        return array_map(fn(PokemonType $t) => $t->displayName(), $this->types);
    }

    public function abilityNames(): array
    {
        return array_map(fn(PokemonAbility $a) => $a->name() . ($a->isHidden() ? ' (hidden)' : ''), $this->abilities);
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'types' => $this->typeNames(),
            'abilities' => $this->abilityNames(),
            'height_m' => $this->stats()->heightMeters(),
            'height_cm' => $this->stats()->heightCentimeters(),
            'weight_kg' => $this->stats()->weightKilograms(),
            'sprite' => $this->sprite,
            'evolution_url' => $this->evolutionUrl,
            'location_areas_url' => $this->locationAreasUrl,
            'location_areas' => $this->locationAreas,
            'description' => $this->description,
            'is_legendary' => $this->isLegendary,
        ];
    }

    public static function fromApiData(array $data): self
    {
        $id = (int)($data['id'] ?? 0);
        $name = ucfirst($data['name'] ?? '');
        $types = [];

        foreach ($data['types'] ?? [] as $t) {
            $types[] = PokemonType::fromApiArray($t);
        }

        $abilities = [];
        foreach ($data['abilities'] ?? [] as $a) {
            $abilities[] = PokemonAbility::fromApiArray($a);
        }
    
        $stats = PokemonStats::fromApi($data);
        $sprite = $data['sprites']['front_default'] ?? null;
        $evolutionUrl = $data['species']['url'] ?? null;
        $locationAreasUrl = $data['location_area_encounters'] ?? null;
        $locationAreas = $data['location_areas'] ?? [];

        $description = null;
        if (!empty($data['flavor_text_entries']) && is_array($data['flavor_text_entries'])) {
            $english = array_filter($data['flavor_text_entries'], function($entry) {
                return isset($entry['language']['name']) && $entry['language']['name'] === 'en';
            });

            if (!empty($english)) {
                $preferred = null;
                foreach ($english as $entry) {
                    if (isset($entry['version']['name']) && $entry['version']['name'] === 'red') {
                        $preferred = $entry;
                        break;
                    }
                }
                $chosen = $preferred ?? reset($english);
                if (isset($chosen['flavor_text'])) {
                    $txt = $chosen['flavor_text'];
                    $txt = preg_replace('/[\x00-\x1F\x7F]+/u', ' ', $txt);
                    $txt = preg_replace('/\s+/u', ' ', $txt);
                    $description = trim($txt);
                }
            }
        }



        return new self($id, $name, $types, $abilities, $stats, $sprite, $evolutionUrl, $locationAreasUrl, $description, (bool)($data['is_legendary'] ?? false), $locationAreas);
    }

    public function description(): ?string { return $this->description; }
    public function locationAreas(): array { return $this->locationAreas; }
}

class PokemonRepository{
    private PDO $connection;

    public function __construct(string $host = 'localhost', string $db = 'pokemon', string $user = 'root', string $pass = ''){
        try{
            // connect to db
            $dsn = "mysql:host=$host;charset=utf8mb4";
            $this->connection = new PDO($dsn, $user, $pass);

            $this->connection_aborted->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            // create db if not exists
            $this->connection->exec("CREATE DATABASE IF NOT EXISTS $db");
            $this->connection->exec("USE $db");

            // create table
            $this->connection->createTable();


        } catch (PDOException $e){
            throw new Exception("Database connection failed: " . $e->getMessage());
        }
    }

    private function createTable(){
        $sql = "CREATE TABLE IF NOT EXISTS pokemons (
            id INT PRIMARY KEY,
            name VARCHAR(100),
            types TEXT,
            abilities TEXT,
            height_m FLOAT,
            height_cm INT,
            weight_kg FLOAT,
            sprite VARCHAR(255),
            evolution_url VARCHAR(255),
            location_areas_url VARCHAR(255),
            location_areas TEXT,
            description TEXT,
            is_legendary BOOLEAN
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
        $this->connection->exec($sql);
    }


}
