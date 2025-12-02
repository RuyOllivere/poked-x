<?php

require_once __DIR__ . '/src/ValueObjects/PokemonType.php';
require_once __DIR__ . '/src/ValueObjects/PokemonAbility.php';
require_once __DIR__ . '/src/ValueObjects/PokemonStats.php';
require_once __DIR__ . '/src/Pokemon.php';

use App\Pokemon;

class PokemonFetcher {
    private $apiUrl = "https://pokeapi.co/api/v2/pokemon/";

    public function fetchPokemon($name) {
        $url = $this->apiUrl . strtolower($name);
        $response = file_get_contents($url);
        if ($response === FALSE) {
            return null;
        }
        return json_decode($response, true);
    }
    
    public function getPokemonData($name) {
        $data = $this->fetchPokemon($name);
        if ($data === null) {
          return null;
        }

        $pokemonInfo = [
            'name' => ucfirst($data['name']),
            'id' => $data['id'],
            'height' => $data['height'],
            'weight' => $data['weight'],
            'types' => array_map(function($type) {
                return $type['type']['name'];
            }, $data['types']),
            'sprite' => $data['sprites']['front_default'],
            'Location areas' => $data['location_area_encounters'],
            'abilities' => array_map(function($ability) {
                return $ability['ability']['name'];
            }, $data['abilities']),
            'location_areas_url' => $data['location_area_encounters'],
            'description' => $data['flavor_text_entries'][0]['flavor_text'] ?? [],
        ];
        if (!empty($data['species']['url'])) {
          $speciesJson = @file_get_contents($data['species']['url']);
          if ($speciesJson !== false) {
            $species = json_decode($speciesJson, true);
            if (is_array($species) && !empty($species['flavor_text_entries'])) {
              $data['flavor_text_entries'] = $species['flavor_text_entries'];
            }
          }
        }

        return Pokemon::fromApiData($data);
    }
}
$name = isset($_GET['pokemonName']) ? $_GET['pokemonName'] : null;
$pokemonData = null;

if ($name !== null) {
    $fetcher = new PokemonFetcher();
    $pokemonData = $fetcher->getPokemonData($name);
}


?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
      <div class="container">
    <div class="left-screen">
      <div class="left-screen__top">
        <div class="light-container">
          <div class="light light--blue">
          </div>
        </div>
        <div class="light light--red"></div>
        <div class="light light--yellow"></div>
        <div class="light light--green"></div>
      </div>
      <div class="left-screen__bottom">
        <div class="main-screen">
          <div class="main-screen__top-lights">
          </div>
          <div id="display" class="main-screen__display">
            <?php if ($pokemonData instanceof Pokemon && !empty($pokemonData->sprite())): ?>
                <div class="pokemon-image">
                    <img src="<?php echo htmlspecialchars($pokemonData->sprite(), ENT_QUOTES); ?>" alt="<?php echo htmlspecialchars($pokemonData->name() ?? 'Pokemon', ENT_QUOTES); ?>">
                </div>
                <div class="search-message" style="display:none;">Searching...</div>
            <?php elseif ($name !== null && $pokemonData === null): ?>
                <div class="not-found-message">Pokemon <br>Not Found</div>
            <?php else: ?>
                <div class="search-message">Search a Pokemon</div>
            <?php endif; ?>
          </div>
          <div class="main-screen__speaker-light"></div>
          <div class="main-screen__speaker">
            <div></div>
            <div></div>
            <div></div>
            <div></div>
          </div>
        </div>
      </div>
      <div class="left-screen__joint">
        <div class="joint"></div>
        <div class="joint"></div>
        <div class="joint"></div>
        <div class="joint"></div>
        <div class="joint__reflextion"></div>
      </div>
    </div>
    <div class="right-screen">
      <div class="right-screen__top">
        <div></div>
      </div>    
      <div class="right-screen__bottom">
        <div class="info-container">
            <form method="GET">
                <input id="search" type="text" class="info-input" placeholder="Search Pokemon Name or ID" name="pokemonName">
                <button id="search-btn" type="submit" class="info-btn">Search</button>
            </form>
          
          <section class="info-screen">
            <div id="species" class="info">
              <div class="label">Species:</div>
                <div class="desc"><?php echo ($pokemonData instanceof Pokemon) ? htmlspecialchars($pokemonData->name(), ENT_QUOTES) : '...'; ?></div>
            </div>
            <div id="type" class="info">
              <div class="label">Type:</div>
              <div class="desc"><?php echo ($pokemonData instanceof Pokemon) && !empty($pokemonData->types()) ? htmlspecialchars(implode(', ', $pokemonData->typeNames()), ENT_QUOTES) : '...'; ?></div>
            </div>
            <div id="height" class="info">
              <div class="label">Height:</div>
              <div class="desc"><?php echo ($pokemonData instanceof Pokemon) ? htmlspecialchars((string)$pokemonData->stats()->heightCentimeters(), ENT_QUOTES) / 100 : '...';?>m</div>
            </div>
            <div id="weight" class="info">
              <div class="label">Weight:</div>
              <div class="desc"><?php echo ($pokemonData instanceof Pokemon) ? htmlspecialchars((string)$pokemonData->stats()->weightKilograms(), ENT_QUOTES) : '...'; ?>kg</div>
            </div>
            <div id="evolution" class="info">
              <div class="label">Ability:</div>
              <div class="desc"><?php echo ($pokemonData instanceof Pokemon) && !empty($pokemonData->abilities()) ? htmlspecialchars(implode(', ', $pokemonData->abilityNames()), ENT_QUOTES) : '...'; ?></div>
            </div>
            <div id="bio" class="info">
              <div class="label">Description:</div>
              <div class="desc"><?php echo ($pokemonData instanceof Pokemon) ? htmlspecialchars($pokemonData->description() ?? '-', ENT_QUOTES) : '...'; ?></div>
            </div>

            <div id="Location" class="info">
              <div class="label">Location:</div>
              <div class="desc"><?php echo ($pokemonData instanceof Pokemon) ? htmlspecialchars($pokemonData->locationAreasUrl(), ENT_QUOTES) : '...'; ?></div>
            </div>

          </section>
        </div>
      </div>
    </div>
  </div>
</body>
</html>