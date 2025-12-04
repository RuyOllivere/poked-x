<?php

use App\Pokemon; // Ensure the Pokemon class is imported

class PokemonRepository
{
    private PDO $connection;

    public function __construct(string $host = 'localhost', string $db = 'pokemon', string $user = 'root', string $pass = '')
    {
        try {
            // connect to db
            $dsn = "mysql:host=$host;charset=utf8mb4";
            $this->connection = new PDO($dsn, $user, $pass);

            $this->connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            // create db if not exists
            $this->connection->exec("CREATE DATABASE IF NOT EXISTS $db");
            $this->connection->exec("USE $db");

            // create table
            $this->createTable();


        } catch (PDOException $e) {
            throw new Exception("Database connection failed: " . $e->getMessage());
        }
    }

    private function createTable()
    {
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

    public function exists(int $id): bool
    {
        $stmt = $this->connection->prepare("SELECT COUNT(*) FROM pokemons WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetchColumn() > 0;
    }

    public function savePokemon(Pokemon $pokemon): bool
    {

        if ($this->exists($pokemon->id())) {
            return false; //Already exists
        }

        // May need ? for fields
        $sql = "INSERT INTO pokemons (id, name, types, abilities, height_m, height_cm, weight_kg, sprite, evolution_url, location_areas_url, location_areas, description, is_legendary)
                VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)";
        // VALUES (:id, :name, :types, :abilities, :height_m, :height_cm, :weight_kg, :sprite, :evolution_url, :location_areas_url, :location_areas, :description, :is_legendary)";
        $stats = $pokemon->stats();
        $stmt = $this->connection->prepare($sql);

        return $stmt->execute([
            $pokemon->id(),
            $pokemon->name(),
            json_encode($pokemon->typeNames()),
            json_encode($pokemon->abilityNames()),
            $stats->heightMeters(),
            $stats->heightCentimeters(),
            $stats->weightKilograms(),
            $pokemon->sprite(),
            $pokemon->evolutionUrl(),
            $pokemon->locationAreasUrl(),
            json_encode($pokemon->locationAreas()),
            $pokemon->description(),
            $pokemon->isLegendary()
        ]);
    }

    // can be null in case not found
    public function getPokemonById(int $id): ?array
    {
        $stmt = $this->connection->prepare("SELECT * FROM pokemons WHERE id = ?");
        $stmt->execute([$id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    public function getAllPokemons(int $limit = 50): array
    {
        $stmt = $this->connection->prepare("SELECT * FROM pokemons ORDER BY id LIMIT ?");
        $stmt->execute([$limit]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getByType(string $type): array
    {
        $stmt = $this->connection->prepare("SELECT * FROM pokemons WHERE types LIKE ? ORDER BY id");
        $stmt->execute(['%"' . $type . '"%']);
        // ["$type%"]
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function deletePokemon(int $id): bool
    {

        $stmt = $this->connection->prepare("DELETE FROM pokemons WHERE id = ?");
        return $stmt->execute([$id]);

    }

    public function countPokemons(): int
    {

        $stmt = $this->connection->prepare("SELECT COUNT(*) FROM pokemons");
        $stmt->execute();
        return (int) $stmt->fetchColumn();

    }

    public function getRankByStats(int $limit = 10): array
    {
        $stmt = $this->connection->prepare("SELECT * FROM pokemons ORDER BY (height_m + weight_kg) DESC LIMIT ?");
        $stmt->execute([$limit]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

}

class PokeApiService
{
    private const BASE_URL = "https://pokeapi.co/api/v2/";
    private HttpClientInterface $httpClient;

    public function __construct(HttpClientInterface $httpClient)
    {
        $this->httpClient = $httpClient;
    }

    public function getPokemon($identifier): ?Pokemon
    {

        $url = self::BASE_URL . "pokemon/" . strtolower($identifier);
        try {
            $data = $this->httpClient->get($url);

            return Pokemon::fromApiData($data);
            // return new Pokemon($data);

        } catch (Exception $e) {
            throw new Exception("Failed to fetch Pokemon data: " . $e->getMessage());
        }

    }

    public function getPokemonSpecies($id): ?array
    {
        $url = self::BASE_URL . "pokemon-species/" . strtolower($id);
        try {
            $data = $this->httpClient->get($url);
            return $data;
        } catch (Exception $e) {
            throw new Exception("Failed to fetch Pokemon species data: " . $e->getMessage());
        }
    }

}

class PokeFormatter
{

    public static function formatPokemonList(array $pokemons): array
    {
        $formatted = [];
        foreach ($pokemons as $p) {
            $formatted[] = [
                'id' => $p['id'],
                'name' => $p['name'],
                'types' => json_decode($p['types'], true),
                'abilities' => json_decode($p['abilities'], true),
                'height_m' => $p['height_m'],
                'weight_kg' => $p['weight_kg'],
                'sprite' => $p['sprite'],
            ];
        }
        return $formatted;
    }

}

class PokedexSystem
{
    private PokeApiService $apiService;
    private PokemonRepository $repository;

    public function __construct(PokeApiService $apiService, PokemonRepository $repository)
    {
        $this->apiService = $apiService;
        $this->repository = $repository;
    }

    public function fetchAndStorePokemon($identifier): ?Pokemon
    {
        $pokemon = $this->apiService->getPokemon($identifier);
        if ($pokemon) {
            $this->repository->savePokemon($pokemon);
            return $pokemon;
        }
        return null;
    }
}

?>